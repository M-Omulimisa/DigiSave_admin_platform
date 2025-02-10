<?php

namespace App\Admin\Controllers;

use App\Models\Cycle;
use App\Models\Meeting;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CreditScoreController extends AdminController
{
    protected $title = 'Credit Scores';
    protected $apiEndpoint = 'https://vslascore-gjh5e5frbbdjeza9.canadacentral-01.azurewebsites.net';

    /**
     * Build a grid interface.
     *
     * @return \Illuminate\View\View
     */
    protected function grid()
    {
        $admin = Admin::user();
        $adminId = $admin->id;

        // Get the SACCO data based on permissions
        $query = Sacco::query();

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)
                    ->pluck('sacco_id')
                    ->toArray();
                $query->whereIn('id', $saccoIds);
            }
        }

        $saccos = $query->whereNotIn('status', ['deleted', 'inactive'])
            ->whereHas('users', function ($query) {
                $query->whereIn('position_id', function ($subQuery) {
                    $subQuery->select('id')
                        ->from('positions')
                        ->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                })
                ->whereNotNull('phone_number')
                ->whereNotNull('name');
            })
            ->whereHas('meetings')
            ->with(['users', 'meetings', 'transactions'])
            ->get();

        // Map the saccos to include all required data
        $processedSaccos = $saccos->map(function ($sacco) {
            return $this->prepareSaccoData($sacco);
        });

        // Return the view with processed SACCO data
        return view('admin.sacco.analysis', [
            'saccos' => $processedSaccos,
            'sacco' => $processedSaccos->first()
        ]);
    }

    /**
     * Safe number formatting helper
     *
     * @param mixed $value
     * @return float
     */
    private function safeNumberFormat($value)
    {
        if (is_string($value)) {
            $value = floatval($value);
        }

        if ($value === null || !is_numeric($value)) {
            return 0;
        }

        return abs($value);
    }

    /**
     * Recursively convert objects to arrays.
     *
     * @param mixed $data
     * @return mixed
     */
    private function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            return array_map([$this, 'objectToArray'], $data);
        }
        return $data;
    }

    /**
     * Calculate meeting attendance statistics
     *
     * @param \App\Models\Sacco $sacco
     * @return array
     */
    private function calculateMeetingStats($sacco)
    {
        $meetings = $sacco->meetings;
        $allMemberNames = [];
        $totalMembersPerMeeting = [];

        foreach ($meetings as $meeting) {
            $membersData = $meeting->members;
            $attendanceData = is_string($membersData) ? json_decode($membersData, true) :
                            (is_array($membersData) ? $membersData :
                            (is_object($membersData) ? $this->objectToArray($membersData) : []));

            if (json_last_error() === JSON_ERROR_NONE &&
                isset($attendanceData['presentMembersIds']) &&
                is_array($attendanceData['presentMembersIds'])) {

                foreach ($attendanceData['presentMembersIds'] as $member) {
                    if (is_object($member)) {
                        $member = (array)$member;
                    }
                    if (isset($member['name'])) {
                        $allMemberNames[] = $member['name'];
                    }
                }

                $totalMembersPerMeeting[] = count($attendanceData['presentMembersIds']);
            }
        }

        $meetingCount = count($meetings);
        $averageAttendance = $meetingCount > 0 ?
            round(array_sum($totalMembersPerMeeting) / $meetingCount, 0) : 0;

        return [
            'totalMeetings' => count($meetings),
            'averageAttendance' => $averageAttendance
        ];
    }

    /**
     * Calculate credit score using API
     *
     * @param array $data
     * @return array
     */
    private function calculateCreditScore($data)
    {
        try {
            $response = Http::withOptions(['verify' => false])
                ->post("{$this->apiEndpoint}/predict", $data);

            $result = $response->json();

            if (isset($result['credit_score'])) {
                $creditScoreValue = $result['credit_score'];
                $creditScoreDescription = $this->getCreditScoreDescription($creditScoreValue);
            } else {
                Log::error('Credit Score API response missing "credit_score":', $result);
                $creditScoreValue = null;
                $creditScoreDescription = 'Unable to calculate credit score at this time.';
            }

            // Calculate max loan amount
            $maxLoanAmount = $this->calculateMaxLoanAmount($creditScoreValue, $data['savings_per_member']);

            return [
                'score' => $creditScoreValue,
                'description' => $creditScoreDescription,
                'maxLoanAmount' => $maxLoanAmount
            ];
        } catch (\Exception $e) {
            Log::error('Credit Score API Calculation Error: ' . $e->getMessage());
            return [
                'score' => null,
                'description' => 'Unable to calculate credit score at this time.',
                'maxLoanAmount' => null
            ];
        }
    }

    /**
     * Calculate maximum loan amount
     *
     * @param float|null $creditScore
     * @param float $averageSavings
     * @return float|null
     */
    private function calculateMaxLoanAmount($creditScore, $averageSavings)
    {
        if ($creditScore === null) {
            return null;
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->apiEndpoint}/max_loan_amount", [
                    "credit_score" => $creditScore * 0.8,
                    "average_savings" => $averageSavings
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['max_loan_amount'] ?? null;
            }

            Log::error("Max Loan Amount API error: Status Code: {$response->status()}, Message: {$response->body()}");
            return null;
        } catch (\Exception $e) {
            Log::error('Max Loan Amount Calculation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepare SACCO data for processing.
     *
     * @param \App\Models\Sacco $sacco
     * @return array
     */
    private function prepareSaccoData($sacco)
    {
        // Get active cycle
        $activeCycle = Cycle::where('sacco_id', $sacco->id)
            ->where('status', 'Active')
            ->first();

        if (!$activeCycle) {
            return $this->getEmptySaccoData($sacco);
        }

        $activeCycleId = $activeCycle->id;

        // Calculate member demographics
        $memberStats = $this->calculateMemberStats($sacco);

        // Calculate meeting statistics
        $meetingStats = $this->calculateMeetingStats($sacco);

        // Calculate loan statistics
        $loanStats = $this->calculateLoanStats($sacco, $activeCycleId);

        // Calculate savings statistics
        $savingsStats = $this->calculateSavingsStats($sacco, $activeCycleId, $memberStats['total']);

        // Prepare credit score request data
        $requestData = $this->prepareCreditScoreRequest($loanStats, $savingsStats, $sacco);

        // Calculate credit score
        $creditScore = $this->calculateCreditScore($requestData);

        return [
            // Basic Information
            'id' => $sacco->id,
            'name' => $sacco->name,
            'created_at' => $sacco->created_at,
            'status' => $sacco->status,

            // Member Demographics
            'totalMembers' => $memberStats['total'],
            'maleMembers' => $memberStats['male'],
            'femaleMembers' => $memberStats['female'],
            'youthMembers' => $memberStats['youth'],

            // Meeting Statistics
            'totalMeetings' => $meetingStats['totalMeetings'],
            'averageAttendance' => $meetingStats['averageAttendance'],

            // Loan Statistics
            'loanStats' => $loanStats,

            // Savings Statistics
            'savingsStats' => $savingsStats,

            // Credit Score
            'creditScore' => [
                'score' => $creditScore['score'],
                'description' => $creditScore['description']
            ],

            // Maximum Loan Amount
            'maxLoanAmount' => $creditScore['maxLoanAmount']
        ];
    }

    /**
     * Calculate member statistics
     *
     * @param \App\Models\Sacco $sacco
     * @return array
     */
    private function calculateMemberStats($sacco)
    {
        $baseQuery = User::where('sacco_id', $sacco->id)
            ->where(function ($query) {
                $query->whereNull('user_type')
                      ->orWhere('user_type', '<>', 'Admin');
            });

        return [
            'total' => $this->safeNumberFormat($baseQuery->count()),
            'male' => $this->safeNumberFormat($baseQuery->where('sex', 'Male')->count()),
            'female' => $this->safeNumberFormat($baseQuery->where('sex', 'Female')->count()),
            'youth' => $this->safeNumberFormat($baseQuery->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 35')->count())
        ];
    }

    /**
     * Calculate loan statistics
     *
     * @param \App\Models\Sacco $sacco
     * @param int $cycleId
     * @return array
     */
    private function calculateLoanStats($sacco, $cycleId)
    {
        $baseQuery = $sacco->transactions()->where('cycle_id', $cycleId);

        return [
            'total' => $this->safeNumberFormat($baseQuery->where('type', 'LOAN')->count()),
            'principal' => $this->safeNumberFormat($baseQuery->where('type', 'LOAN')->sum('amount')),
            'interest' => $this->safeNumberFormat($baseQuery->where('type', 'LOAN_INTEREST')->sum('amount')),
            'repayments' => $this->safeNumberFormat($baseQuery->where('type', 'LOAN_REPAYMENT')->sum('amount')),
            'male' => $this->calculateGenderLoanStats($sacco, $cycleId, 'Male'),
            'female' => $this->calculateGenderLoanStats($sacco, $cycleId, 'Female'),
            'youth' => $this->calculateYouthLoanStats($sacco, $cycleId)
        ];
    }

    /**
     * Calculate gender-specific loan statistics
     *
     * @param \App\Models\Sacco $sacco
     * @param int $cycleId
     * @param string $gender
     * @return array
     */
    private function calculateGenderLoanStats($sacco, $cycleId, $gender)
    {
        $query = $sacco->transactions()
            ->where('cycle_id', $cycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', $gender);

        return [
            'count' => $this->safeNumberFormat($query->count()),
            'amount' => $this->safeNumberFormat($query->sum('transactions.amount'))
        ];
    }

    /**
     * Calculate youth loan statistics
     *
     * @param \App\Models\Sacco $sacco
     * @param int $cycleId
     * @return array
     */
    private function calculateYouthLoanStats($sacco, $cycleId)
    {
        $query = $sacco->transactions()
            ->where('cycle_id', $cycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35');

        return [
            'count' => $this->safeNumberFormat($query->count()),
            'amount' => $this->safeNumberFormat($query->sum('transactions.amount'))
        ];
    }

    /**
     * Calculate savings statistics
     *
     * @param \App\Models\Sacco $sacco
     * @param int $cycleId
     * @param int $totalMembers
     * @return array
     */
    private function calculateSavingsStats($sacco, $cycleId, $totalMembers)
    {
        $baseQuery = $sacco->transactions()
            ->where('cycle_id', $cycleId)
            ->where('type', 'SHARE');

        $totalBalance = $this->safeNumberFormat($baseQuery->sum('amount'));
        $averageSavings = $totalMembers > 0 ? $totalBalance / $totalMembers : 0;

        return [
            'totalAccounts' => $this->safeNumberFormat($totalMembers),
            'totalBalance' => $totalBalance,
            'averageSavings' => $this->safeNumberFormat($averageSavings),
            'male' => $this->calculateGenderSavingsStats($sacco, $cycleId, 'Male'),
            'female' => $this->calculateGenderSavingsStats($sacco, $cycleId, 'Female'),
            'youth' => $this->calculateYouthSavingsStats($sacco, $cycleId)
        ];
    }

    /**
     * Calculate gender-specific savings statistics
     *
     * @param \App\Models\Sacco $sacco
     * @param int $cycleId
     * @param string $gender
     * @return array
     */
    private function calculateGenderSavingsStats($sacco, $cycleId, $gender)
    {
        $query = $sacco->transactions()
            ->where('cycle_id', $cycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', $gender);

        return [
            'accounts' => $this->safeNumberFormat($query->distinct('source_user_id')->count('source_user_id')),
            'balance' => $this->safeNumberFormat($query->sum('transactions.amount'))
        ];
    }

    /**
     * Calculate youth savings statistics
     *
     * @param \App\Models\Sacco $sacco
     * @param int $cycleId
     * @return array
     */
    private function calculateYouthSavingsStats($sacco, $cycleId)
    {
        $query = $sacco->transactions()
            ->where('cycle_id', $cycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35');

        return [
            'accounts' => $this->safeNumberFormat($query->distinct('source_user_id')->count('source_user_id')),
            'balance' => $this->safeNumberFormat($query->sum('transactions.amount'))
        ];
    }

    /**
     * Prepare data for credit score API request
     *
     * @param array $loanStats
     * @param array $savingsStats
     * @param \App\Models\Sacco $sacco
     * @return array
     */
    private function prepareCreditScoreRequest($loanStats, $savingsStats, $sacco)
    {
        $monthsSinceCreation = $sacco->created_at->diffInMonths(now());

        // Calculate savings credit mobilization
        $savingsCreditMobilization = $monthsSinceCreation > 0 ?
            ($savingsStats['totalBalance'] / ($monthsSinceCreation * 4)) : 0;

        // Calculate youth support rate
        $youthSupportRate = $loanStats['principal'] > 0 ?
            ($loanStats['youth']['amount'] / $loanStats['principal']) : 0;

        // Calculate fund savings credit status
        $fundSavingsCreditStatus = $loanStats['principal'] > 0 ?
            ($loanStats['repayments'] / $loanStats['principal']) * 100 : 0;

        return [
            "number_of_loans" => $loanStats['total'],
            "total_principal" => $loanStats['principal'],
            "total_interest" => $loanStats['interest'],
            "total_principal_paid" => $loanStats['repayments'],
            "total_interest_paid" => $loanStats['interest'], // Assuming all interest is paid
            "number_of_savings_accounts" => $savingsStats['totalAccounts'],
            "total_savings_balance" => $savingsStats['totalBalance'],
            "total_principal_outstanding" => $loanStats['principal'] - $loanStats['repayments'],
            "total_interest_outstanding" => 0, // Calculated as needed
            "number_of_loans_to_men" => $loanStats['male']['count'],
            "total_disbursed_to_men" => $loanStats['male']['amount'],
            "total_savings_accounts_for_men" => $savingsStats['male']['accounts'],
            "number_of_loans_to_women" => $loanStats['female']['count'],
            "total_disbursed_to_women" => $loanStats['female']['amount'],
            "total_savings_accounts_for_women" => $savingsStats['female']['accounts'],
            "total_savings_balance_for_women" => $savingsStats['female']['balance'],
            "number_of_loans_to_youth" => $loanStats['youth']['count'],
            "total_disbursed_to_youth" => $loanStats['youth']['amount'],
            "total_savings_balance_for_youth" => $savingsStats['youth']['balance'],
            "savings_per_member" => $savingsStats['averageSavings'],
            "youth_support_rate" => $youthSupportRate,
            "savings_credit_mobilization" => $savingsCreditMobilization,
            "fund_savings_credit_status" => $fundSavingsCreditStatus
        ];
    }

    /**
     * Get empty SACCO data structure
     *
     * @param \App\Models\Sacco $sacco
     * @return array
     */
    private function getEmptySaccoData($sacco)
    {
        $totalMeetings = Meeting::where('sacco_id', $sacco->id)->count();

        return [
            'id' => $sacco->id,
            'name' => $sacco->name,
            'created_at' => $sacco->created_at,
            'status' => $sacco->status,
            'totalMembers' => 0,
            'maleMembers' => 0,
            'femaleMembers' => 0,
            'youthMembers' => 0,
            'totalMeetings' => $totalMeetings,
            'averageAttendance' => 0.0,
            'loanStats' => [
                'total' => 0,
                'principal' => 0,
                'interest' => 0,
                'repayments' => 0,
                'male' => ['count' => 0, 'amount' => 0],
                'female' => ['count' => 0, 'amount' => 0],
                'youth' => ['count' => 0, 'amount' => 0]
            ],
            'savingsStats' => [
                'totalAccounts' => 0,
                'totalBalance' => 0.0,
                'averageSavings' => 0.0,
                'male' => ['accounts' => 0, 'balance' => 0.0],
                'female' => ['accounts' => 0, 'balance' => 0.0],
                'youth' => ['accounts' => 0, 'balance' => 0.0]
            ],
            'creditScore' => [
                'score' => null,
                'description' => 'No active cycle found. Unable to calculate credit score.'
            ],
            'maxLoanAmount' => null
        ];
    }

    /**
     * Get credit score description based on score value
     *
     * @param int|null $score
     * @return string
     */
    private function getCreditScoreDescription($score)
    {
        if ($score === null) {
            return "Unable to calculate credit score at this time.";
        }

        if ($score >= 80) {
            return "Excellent credit standing. The group demonstrates strong savings culture and reliable loan repayment history.";
        } elseif ($score >= 60) {
            return "Good credit standing. The group shows consistent savings and satisfactory loan management.";
        } elseif ($score >= 40) {
            return "Fair credit standing. There's room for improvement in savings and loan repayment patterns.";
        } else {
            return "Needs improvement. The group should focus on increasing savings and improving loan repayment rates.";
        }
    }
}
