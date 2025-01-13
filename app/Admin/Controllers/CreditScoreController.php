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
        // Uncomment the line below to inspect the data
        // dd($processedSaccos);

        // After inspecting the data, remove or comment out the dd() line and uncomment the return statement below:
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
                'totalMeetings' => 0,
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

        // Total Meetings
        $totalMeetings = Meeting::where('sacco_id', $sacco->id)->count();

        // Total Member Names (Average Meeting Attendance)
        $meetings = $sacco->meetings;
        $allMemberNames = [];
        foreach ($meetings as $meeting) {
            $membersJson = $meeting->members;
            $attendanceData = json_decode($membersJson, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($attendanceData['presentMembersIds']) && is_array($attendanceData['presentMembersIds'])) {
                    foreach ($attendanceData['presentMembersIds'] as $member) {
                        if (isset($member['name'])) {
                            $allMemberNames[] = $member['name'];
                        }
                    }
                }
            }
        }

        $meetingCount = count($meetings);
        // Calculate average number of members present per meeting
        $totalMembersPerMeeting = [];
        foreach ($meetings as $meeting) {
            $membersData = $meeting->members;
            // Handle both JSON string and array cases
            $attendanceData = is_string($membersData) ? json_decode($membersData, true) : $membersData;

            if (isset($attendanceData['presentMembersIds'])) {
                $totalMembersPerMeeting[] = count($attendanceData['presentMembersIds']);
            }
        }

        $averageAttendanceRounded = $meetingCount > 0 ?
            round(array_sum($totalMembersPerMeeting) / $meetingCount, 2) : 0;

        // $meetingCount = count($meetings);
        // $totalPresent = count(array_unique($allMemberNames));
        // $averageAttendance = $meetingCount > 0 ? ($totalPresent / $meetingCount) * 100 : 0;
        // $averageAttendanceRounded = round($averageAttendance, 2);

        // Total Loans
        $numberOfLoans = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN')
            ->count();

        // Total Loan Amount (Principal)
        $totalPrincipal = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN')
            ->sum('amount');

        // Total Interest
        $totalInterest = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN_INTEREST')
            ->sum('amount');

        // Total Loan Repayments
        $totalLoanRepayments = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN_REPAYMENT')
            ->sum('amount');

        // Loans to Males
        $numberOfLoansToMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->count();

        // Total Loans Disbursed to Males
        $totalDisbursedToMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->sum('transactions.amount');

        // Loans to Females
        $numberOfLoansToWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->count();

        // Total Loans Disbursed to Females
        $totalDisbursedToWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->sum('transactions.amount');

        // Loans to Youth
        $numberOfLoansToYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->count();

        // Total Loans Disbursed to Youth
        $totalDisbursedToYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->sum('transactions.amount');

        // Number of Savings Accounts
        $numberOfSavingsAccounts = User::where('sacco_id', $sacco->id)
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();

        // Total Savings Balance
        $totalSavingsBalance = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->sum('transactions.amount');

        // Calculate monthly savings
        $cycle = Cycle::find($activeCycleId); // Replace with your actual cycle retrieval method

        $monthlyTransactions = $sacco->transactions()
            ->whereBetween('created_at', [$cycle->start_date, $cycle->end_date])
            ->where('type', 'SHARE')
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get();

        $totalMonths = $monthlyTransactions->count();
        $totalSavings = $monthlyTransactions->sum('total');
        $average_monthly_savings = $totalMonths > 0 ? abs($totalSavings / $totalMonths) : 0;

        // Savings to Males
        $savingsAccountsForMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->distinct('source_user_id')
            ->count('source_user_id');

        // Total Savings Balance for Males
        $totalSavingsBalanceForMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->sum('transactions.amount');

        // Savings to Females
        $savingsAccountsForWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Female')
            ->distinct('source_user_id')
            ->count('source_user_id');

        // Total Savings Balance for Females
        $totalSavingsBalanceForWomen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Female')
            ->sum('transactions.amount');

        // Savings to Youth
        $savingsAccountsForYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->distinct('source_user_id')
            ->count('source_user_id');

        // Total Savings Balance for Youth
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

        // Calculate credit score based on actual performance
        // $creditScore = min(300, round(
        //     ($totalLoanRepayments / max(1, abs($totalPrincipal))) * 100 + // Repayment Rate
        //     ($savingsCreditMobilization * 100) + // Savings Credit Mobilization
        //     (($averageAttendance / max(1, $numberOfMembers)) * 100) // Attendance Rate
        // ));

        // Make the max loan amount API call with actual values

        // Prepare prediction request data
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

        // Optional: Log the request data for debugging
        Log::info('Credit Score API Request Data:', $requestData);

        // Call the external API to get the credit score
        try {
            $response = Http::withOptions(['verify' => false])
    ->post('https://vslacreditplus-fte2h3dhc5hcc0e5.canadacentral-01.azurewebsites.net/predict', $requestData);

            $result = $response->json();
            // dd($result);

            if (isset($result['credit_score'])) {
                $creditScoreValue = $result['credit_score'];
                $creditScoreDescription = $this->getCreditScoreDescription($creditScoreValue);
            } else {
                // Handle the case where 'credit_score' is not present in the response
                Log::error('Credit Score API response missing "credit_score":', $result);
                $creditScoreValue = null;
                $creditScoreDescription = 'Unable to calculate credit score at this time.';
            }
        } catch (\Exception $e) {
            // Log the exception message for debugging
            Log::error('Credit Score API Calculation Error: ' . $e->getMessage());
            $creditScoreValue = null;
            $creditScoreDescription = 'Unable to calculate credit score at this time.';
        }

        $maxLoanAmountResponse = Http::withOptions(['verify' => false])
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post('https://vslacreditplus-fte2h3dhc5hcc0e5.canadacentral-01.azurewebsites.net/max_loan_amount', [
            "credit_score" => $creditScoreValue *0.8,
            "average_savings" => $average_monthly_savings
        ]);

        $resp = $maxLoanAmountResponse->json();
        // dd($average_monthly_savings);

            if ($maxLoanAmountResponse->successful()) {
                $maxLoanAmountData = $maxLoanAmountResponse->json();
                $maxLoanAmount = $maxLoanAmountData['max_loan_amount'] ?? null;
            } else {
                $statusCode = $maxLoanAmountResponse->status();
                $errorMessage = $maxLoanAmountResponse->body();
                Log::error("Max Loan Amount API error: Status Code: $statusCode, Message: $errorMessage");
                // You might choose to handle this differently based on your application's needs
                $maxLoanAmount = null;
            };

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

            // Additional Data (if needed)
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
