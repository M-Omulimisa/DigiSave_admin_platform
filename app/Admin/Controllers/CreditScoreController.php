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
                    ->pluck('sacco_id')->toArray();
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

        // Dump the processed data and stop execution for inspection
        // dd($processedSaccos);

        return view('admin.sacco.analysis', [
            'saccos' => $processedSaccos,  // All SACCOs data
            'sacco' => $processedSaccos->first()  // First SACCO for backward compatibility
        ]);
    }

    /**
     * Prepare SACCO data for processing.
     *
     * @param \App\Models\Sacco $sacco
     * @return array
     */
    private function prepareSaccoData($sacco)
    {
        // Fetch the active cycle associated with the Sacco
        $activeCycle = Cycle::where('sacco_id', $sacco->id)
            ->where('status', 'Active')
            ->first();

        $totalMeetings = Meeting::where('sacco_id', $sacco->id)->count();

        if ($activeCycle == null) {
            // If no active cycle is found, return minimal data with error in creditScore
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
                    'youth' => ['count' => 0, 'amount' => 0],
                ],
                'savingsStats' => [
                    'totalAccounts' => 0,
                    'totalBalance' => 0.0,
                    'averageSavings' => 0.0,
                    'male' => ['accounts' => 0, 'balance' => 0.0],
                    'female' => ['accounts' => 0, 'balance' => 0.0],
                    'youth' => ['accounts' => 0, 'balance' => 0.0],
                ],
                'creditScore' => [
                    'score' => null,
                    'description' => 'No active cycle found. Unable to calculate credit score.'
                ]
            ];
        }

        $activeCycleId = $activeCycle->id;

        // Total Group Members
        $numberOfMembers = User::where('sacco_id', $sacco->id)
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();

        // Number of Male Members
        $numberOfMen = User::where('sacco_id', $sacco->id)
            ->where('sex', 'Male')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();

        // Number of Female Members
        $numberOfWomen = User::where('sacco_id', $sacco->id)
            ->where('sex', 'Female')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();

        // Number of Youth Members
        $numberOfYouth = User::where('sacco_id', $sacco->id)
            ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 35')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();

        $totalMeetings = Meeting::where('sacco_id', $sacco->id)->count();

        // --- Meeting Attendance & Average ---
        $meetings = $sacco->meetings;
        $allMemberNames = [];

        foreach ($meetings as $meeting) {
            $membersData = $meeting->members;

            // Use appropriate conversion based on type.
            if (is_string($membersData)) {
                $attendanceData = json_decode($membersData, true);
            } elseif (is_array($membersData)) {
                $attendanceData = $membersData;
            } elseif (is_object($membersData)) {
                // Recursively convert object to array.
                $attendanceData = json_decode(json_encode($membersData), true);
            } else {
                $attendanceData = [];
            }

            // Check if decoding is successful and presentMembersIds exists.
            if (
                json_last_error() === JSON_ERROR_NONE &&
                isset($attendanceData['presentMembersIds']) &&
                is_array($attendanceData['presentMembersIds'])
            ) {
                foreach ($attendanceData['presentMembersIds'] as $member) {
                    // Each $member is presumably an array with 'name'
                    if (isset($member['name'])) {
                        $allMemberNames[] = $member['name'];
                    }
                }
            }
        }

        $meetingCount = count($meetings);
        $totalMembersPerMeeting = [];

        foreach ($meetings as $meeting) {
            $membersData = $meeting->members;

            if (is_string($membersData)) {
                $attendanceData = json_decode($membersData, true);
            } elseif (is_array($membersData)) {
                $attendanceData = $membersData;
            } elseif (is_object($membersData)) {
                $attendanceData = json_decode(json_encode($membersData), true);
            } else {
                $attendanceData = [];
            }

            if (
                json_last_error() === JSON_ERROR_NONE &&
                isset($attendanceData['presentMembersIds']) &&
                is_array($attendanceData['presentMembersIds'])
            ) {
                $totalMembersPerMeeting[] = count($attendanceData['presentMembersIds']);
            }
        }

        $averageAttendanceRounded = $meetingCount > 0
            ? round(array_sum($totalMembersPerMeeting) / $meetingCount, 0)
            : 0;

        // Loan and Savings Calculations
        $numberOfLoans = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN')
            ->count();

        $totalPrincipal = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN')
            ->sum('amount');

        $totalInterest = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN_INTEREST')
            ->sum('amount');

        $totalLoanRepayments = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN_REPAYMENT')
            ->sum('amount');

        $numberOfLoansToMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->count();

        $totalDisbursedToMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->sum('transactions.amount');

        $numberOfLoansToWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->count();

        $totalDisbursedToWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->sum('transactions.amount');

        $numberOfLoansToYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->count();

        $totalDisbursedToYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->sum('transactions.amount');

        $numberOfSavingsAccounts = User::where('sacco_id', $sacco->id)
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();

        $totalSavingsBalance = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->sum('transactions.amount');

        // Calculate monthly savings
        $cycle = Cycle::find($activeCycleId);

        $monthlyTransactions = $sacco->transactions()
            ->whereBetween('created_at', [$cycle->start_date, $cycle->end_date])
            ->where('type', 'SHARE')
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get();

        $totalMonths = $monthlyTransactions->count();
        $totalSavings = $monthlyTransactions->sum('total');
        $average_monthly_savings = $totalMonths > 0 ? abs($totalSavings / $totalMonths) : 0;

        $savingsAccountsForMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->distinct('source_user_id')
            ->count('source_user_id');

        $totalSavingsBalanceForMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->sum('transactions.amount');

        $savingsAccountsForWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Female')
            ->distinct('source_user_id')
            ->count('source_user_id');

        $totalSavingsBalanceForWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Female')
            ->sum('transactions.amount');

        $savingsAccountsForYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->distinct('source_user_id')
            ->count('source_user_id');

        $totalSavingsBalanceForYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->sum('transactions.amount');

        $averageSavingsPerMember = $numberOfMembers > 0 ? abs($totalSavingsBalance / $numberOfMembers) : 0;

        $totalLoans = abs($sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN')
            ->sum('amount'));

        // Calculate Interest Paid
        $calculatedInterestPaid = $totalLoanRepayments * 0.20; // Assuming 20% interest rate
        $totalInterestPaid = min($calculatedInterestPaid, abs($totalInterest));

        // Calculate Principal Paid
        $totalPrincipalPaid = abs($totalLoanRepayments) - abs($totalInterestPaid);

        // Total Principal Outstanding
        $totalPrincipalOutstanding = abs($totalLoans) - abs($totalPrincipalPaid);

        // Outstanding Interest
        $outstandingInterest = abs($totalInterest) - abs($totalInterestPaid);

        $monthsSinceCreation = $sacco->created_at->diffInMonths(now());

        // Calculating savings_credit_mobilization
        $savingsCreditMobilization = $monthsSinceCreation > 0 ? ($totalSavingsBalance / ($monthsSinceCreation * 4)) : 0;

        // Youth Support Rate
        $youthSupportRate = $totalPrincipal > 0 ? ($totalDisbursedToYouth / abs($totalPrincipal)) : 0;
        $youthSupportRate = ($youthSupportRate < 0.001) ? 0 : $youthSupportRate;

        // Fund Savings Credit Status
        $fundSavingsCreditStatus = abs($totalPrincipal) > 0 ? ($totalPrincipalPaid / abs($totalPrincipal)) : 0;
        $fundSavingsCreditStatus = (int)($fundSavingsCreditStatus * 100);

        // Prepare prediction request data for the Credit Score API call
        $requestData = [
            "number_of_loans" => $numberOfLoans,
            "total_principal" => abs($totalPrincipal),
            "total_interest" => abs($totalInterest),
            "total_principal_paid" => abs($totalPrincipalPaid),
            "total_interest_paid" => abs($totalInterestPaid),
            "number_of_savings_accounts" => $numberOfSavingsAccounts,
            "total_savings_balance" => abs($totalSavingsBalance),
            "total_principal_outstanding" => abs($totalPrincipalOutstanding),
            "total_interest_outstanding" => abs($outstandingInterest),
            "number_of_loans_to_men" => $numberOfLoansToMen,
            "total_disbursed_to_men" => abs($totalDisbursedToMen),
            "total_savings_accounts_for_men" => $savingsAccountsForMen,
            "number_of_loans_to_women" => $numberOfLoansToWomen,
            "total_disbursed_to_women" => abs($totalDisbursedToWomen),
            "total_savings_accounts_for_women" => $savingsAccountsForWomen,
            "total_savings_balance_for_women" => abs($totalSavingsBalanceForWomen),
            "number_of_loans_to_youth" => $numberOfLoansToYouth,
            "total_disbursed_to_youth" => abs($totalDisbursedToYouth),
            "total_savings_balance_for_youth" => abs($totalSavingsBalanceForYouth),
            "savings_per_member" => abs($averageSavingsPerMember),
            "youth_support_rate" => $youthSupportRate,
            "savings_credit_mobilization" => $savingsCreditMobilization,
            "fund_savings_credit_status" => $fundSavingsCreditStatus
        ];

        Log::info('Credit Score API Request Data:', $requestData);

        try {
            $response = Http::withOptions(['verify' => false])
                ->post('https://vslacreditplus-fte2h3dhc5hcc0e5.canadacentral-01.azurewebsites.net/predict', $requestData);

            $result = $response->json();

            if (isset($result['credit_score'])) {
                $creditScoreValue = $result['credit_score'];
                $creditScoreDescription = $this->getCreditScoreDescription($creditScoreValue);
            } else {
                Log::error('Credit Score API response missing "credit_score":', $result);
                $creditScoreValue = null;
                $creditScoreDescription = 'Unable to calculate credit score at this time.';
            }
        } catch (\Exception $e) {
            Log::error('Credit Score API Calculation Error: ' . $e->getMessage());
            $creditScoreValue = null;
            $creditScoreDescription = 'Unable to calculate credit score at this time.';
        }

        $maxLoanAmountResponse = Http::withOptions(['verify' => false])
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('https://vslacreditplus-fte2h3dhc5hcc0e5.canadacentral-01.azurewebsites.net/max_loan_amount', [
                "credit_score" => $creditScoreValue * 0.8,
                "average_savings" => $average_monthly_savings
            ]);

        if ($maxLoanAmountResponse->successful()) {
            $maxLoanAmountData = $maxLoanAmountResponse->json();
            $maxLoanAmount = $maxLoanAmountData['max_loan_amount'] ?? null;
        } else {
            $statusCode = $maxLoanAmountResponse->status();
            $errorMessage = $maxLoanAmountResponse->body();
            Log::error("Max Loan Amount API error: Status Code: $statusCode, Message: $errorMessage");
            $maxLoanAmount = null;
        }

        return [
            // Basic Information
            'id' => $sacco->id,
            'name' => $sacco->name,
            'created_at' => $sacco->created_at,
            'status' => $sacco->status,

            // Membership Demographics
            'totalMembers' => $numberOfMembers,
            'maleMembers' => $numberOfMen,
            'femaleMembers' => $numberOfWomen,
            'youthMembers' => $numberOfYouth,

            // Meeting Statistics
            'totalMeetings' => $totalMeetings,
            'averageAttendance' => $averageAttendanceRounded,

            // Loan Statistics
            'loanStats' => [
                'total' => $numberOfLoans,
                'principal' => abs($totalPrincipal),
                'interest' => abs($totalInterest),
                'repayments' => abs($totalLoanRepayments),
                'male' => [
                    'count' => $numberOfLoansToMen,
                    'amount' => abs($totalDisbursedToMen)
                ],
                'female' => [
                    'count' => $numberOfLoansToWomen,
                    'amount' => abs($totalDisbursedToWomen)
                ],
                'youth' => [
                    'count' => $numberOfLoansToYouth,
                    'amount' => abs($totalDisbursedToYouth)
                ]
            ],

            // Savings Statistics
            'savingsStats' => [
                'totalAccounts' => $numberOfSavingsAccounts,
                'totalBalance' => abs($totalSavingsBalance),
                'averageSavings' => abs($averageSavingsPerMember),
                'male' => [
                    'accounts' => $savingsAccountsForMen,
                    'balance' => abs($totalSavingsBalanceForMen)
                ],
                'female' => [
                    'accounts' => $savingsAccountsForWomen,
                    'balance' => abs($totalSavingsBalanceForWomen)
                ],
                'youth' => [
                    'accounts' => $savingsAccountsForYouth,
                    'balance' => abs($totalSavingsBalanceForYouth)
                ]
            ],

            // Credit Score
            'creditScore' => [
                'score' => $creditScoreValue,
                'description' => $creditScoreDescription
            ],

            // Additional Data
            'maxLoanAmount' => $maxLoanAmount
        ];
    }

    /**
     * Get the description based on the credit score.
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
