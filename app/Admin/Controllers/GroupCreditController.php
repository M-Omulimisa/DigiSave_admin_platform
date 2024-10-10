<?php

namespace App\Admin\Controllers;

use App\Models\Sacco;
use App\Models\Cycle;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Meeting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroupCreditController extends AdminController
{
    protected $title = 'Sacco Credit Details';

    /**
     * Display the credit details for a specific Sacco.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        // Get sacco_id from request
        $saccoId = request()->get('sacco_id');

        // Fetch the Sacco
        $sacco = Sacco::find($saccoId);
        if (!$sacco) {
            return $content
                ->header('Error')
                ->description('Sacco not found.');
        }

        // Fetch Sacco Details
        $saccoDetails = $this->getSaccoDetails($sacco);

        // Pass the data to the view
        return $content
            ->header('Credit Details for ' . ucwords(strtolower($sacco->name)))
            ->body(view('admin.group_credit', compact('saccoDetails')));
    }

    /**
     * Retrieve detailed credit information for the Sacco.
     *
     * @param Sacco $sacco
     * @return array
     */
    protected function getSaccoDetails(Sacco $sacco)
    {
        // Fetch the active cycle associated with the Sacco
        $activeCycle = Cycle::where('sacco_id', $sacco->id)
            ->where('status', 'Active')
            ->first();

        if (!$activeCycle) {
            return [
                'error' => 'No active cycle found for this Sacco.',
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
        $totalPresent = count(array_unique($allMemberNames));
        $averageAttendance = $meetingCount > 0 ? $totalPresent / $meetingCount : 0;
        $averageAttendanceRounded = round($averageAttendance);

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

        // Savings to Males
        $savingsAccountsForMen = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->count();

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
            ->count();

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
            ->count();

        // Total Savings Balance for Youth
        $totalSavingsBalanceForYouth = $sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->sum('transactions.amount');

        // Average Monthly Savings by Admin Members
        $adminSavings = $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('cycle_id', $activeCycleId)
            ->where('transactions.type', 'SHARE')
            ->where('users.user_type', 'Admin')
            ->selectRaw('SUM(transactions.amount) as total_savings, MONTH(transactions.created_at) as month, YEAR(transactions.created_at) as year')
            ->groupBy('month', 'year')
            ->get();

        // Calculate the total number of months where savings were made
        $numberOfMonths = $adminSavings->count();

        $totalSavingsByAdmin = $adminSavings->sum('total_savings');

        $averageMonthlySavingsByAdmin = $numberOfMonths > 0 ? $totalSavingsByAdmin / $numberOfMonths : 0;

        // Format the average monthly savings
        $average_monthly_savings = abs($averageMonthlySavingsByAdmin);

        $averageSavingsPerMember = $numberOfMembers > 0 ? $totalSavingsBalance / $numberOfMembers : 0;

        $average_monthly_savings = abs($averageMonthlySavingsByAdmin); // No number_format here

        // Call External API for Max Loan Amount
        $maxLoanAmountResponse = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post('https://vsla-credit-scoring-bde4afgbgyesgheu.canadacentral-01.azurewebsites.net/max_loan_amount', [
            "Multiplier" => 6,
            "Average Monthly Savings" => $average_monthly_savings
        ]);

        if ($maxLoanAmountResponse->successful()) {
            $maxLoanAmountData = $maxLoanAmountResponse->json();
            $maxLoanAmount = $maxLoanAmountData['max_loan_amount'];
        } else {
            // Capture the error details
            $statusCode = $maxLoanAmountResponse->status();
            $errorMessage = $maxLoanAmountResponse->body();

            // Log the error for debugging
            Log::error("Max Loan Amount API error: Status Code: $statusCode, Message: $errorMessage");

            // Handle the error if the max loan amount call failed
            $maxLoanAmount = 0;
        }

        $totalLoans = abs($sacco->transactions()
            ->where('cycle_id', $activeCycleId)
            ->where('type', 'LOAN')
            ->sum('amount'));

        // Calculate Total Principal Paid
        $calculatedInterestPaid = $totalLoanRepayments * 0.20;
        $totalInterestPaid = min($calculatedInterestPaid, $totalInterest); // Ensure you don't pay more interest than owed

        // Calculate Principal Paid (remaining after interest is paid)
        $totalPrincipalPaid = $totalLoanRepayments - $totalInterestPaid;

        // Total Principal Outstanding (Principal minus what has been repaid)
        $totalPrincipalOutstanding = $totalLoans - $totalPrincipalPaid;

        // Outstanding Interest (Interest minus what has been repaid)
        $outstandingInterest = $totalInterest - $totalInterestPaid;

        $monthsSinceCreation = $sacco->created_at->diffInMonths(now());

        // Calculating savings_credit_mobilization
        $savingsCreditMobilization = $monthsSinceCreation > 0 ? $totalSavingsBalance / ($monthsSinceCreation * 4) : 0;

        $youthSupportRate = $totalPrincipal > 0 ? $totalDisbursedToYouth / $totalPrincipal : 0;
        $youthSupportRate = ($youthSupportRate < 0.001) ? 0 : $youthSupportRate; // Avoid extremely small values being formatted as zero

        $cleanTotalPrincipal = str_replace(',', '', $totalPrincipal);
        $numericTotalPrincipal = floatval($cleanTotalPrincipal);

        $fundSavingsCreditStatus = abs($numericTotalPrincipal) > 0 ? $totalPrincipalPaid / abs($numericTotalPrincipal) : 0 ;

        // Format to three decimal places for consistency in the response
        $fundSavingsCreditStatusFormatted = $fundSavingsCreditStatus;

        // Make the prediction API call
        $predictionResponse = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withoutVerifying()->post('https://vsla-credit-scoring-bde4afgbgyesgheu.canadacentral-01.azurewebsites.net/predict', [
            "number_of_loans" => $numberOfLoans,
            "total_principal" => abs($totalPrincipal),
            "total_interest" => abs($totalInterest),
            "total_principal_paid" => $totalPrincipalPaid,
            "total_interest_paid" => $totalInterestPaid,
            "number_of_savings_accounts" => $numberOfSavingsAccounts,
            "total_savings_balance" => abs($totalSavingsBalance),
            "total_principal_outstanding" => $totalPrincipalOutstanding,
            "total_interest_outstanding" => $outstandingInterest,
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
        ]);

        if ($predictionResponse->successful()) {
            $predictionData = $predictionResponse->json();
        } else {
            // Capture the error details
            $statusCode = $predictionResponse->status();
            $errorMessage = $predictionResponse->body();

            // Log the error for debugging
            Log::error("Prediction API error: Status Code: $statusCode, Message: $errorMessage");

            // Handle the error if the prediction call failed
            $predictionData = [
                'error' => "Prediction API call failed. Status Code: $statusCode, Message: $errorMessage"
            ];
        }

        // Format some values
        $youthSupportRateFormatted = number_format($youthSupportRate, 3);
        $fundSavingsCreditStatusFormatted = number_format($fundSavingsCreditStatus, 3);

        // Prepare the request data (for display purposes)
        $saccoDetails = [
            "number_of_loans" => $numberOfLoans,
            "total_principal" => number_format(abs($totalPrincipal), 2, '.', ','),
            "total_interest" => number_format(abs($totalInterest), 2, '.', ','),
            "total_loan_repayments" => number_format(abs($totalLoanRepayments), 2, '.', ','),
            "average_monthly_savings" => $average_monthly_savings,
            "number_of_members" => $numberOfMembers,
            "number_of_men" => $numberOfMen,
            "number_of_women" => $numberOfWomen,
            "number_of_youth" => $numberOfYouth,
            "total_meetings" => $totalMeetings,
            "max_loan_amount" => $maxLoanAmount,
            "prediction_response" => $predictionData,
            "average_meeting_attendance" => $averageAttendanceRounded,
            "number_of_loans_to_men" => $numberOfLoansToMen,
            "total_disbursed_to_men" => number_format(abs($totalDisbursedToMen), 2, '.', ','),
            "number_of_loans_to_women" => $numberOfLoansToWomen,
            "total_disbursed_to_women" => number_format(abs($totalDisbursedToWomen), 2, '.', ','),
            "number_of_loans_to_youth" => $numberOfLoansToYouth,
            "total_disbursed_to_youth" => number_format(abs($totalDisbursedToYouth), 2, '.', ','),
            "number_of_savings_accounts" => $numberOfSavingsAccounts,
            "total_savings_balance" => number_format(abs($totalSavingsBalance), 2, '.', ','),
            "savings_accounts_for_men" => $savingsAccountsForMen,
            "total_savings_balance_for_men" => number_format(abs($totalSavingsBalanceForMen), 2, '.', ','),
            "savings_accounts_for_women" => $savingsAccountsForWomen,
            "total_savings_balance_for_women" => number_format(abs($totalSavingsBalanceForWomen), 2, '.', ','),
            "savings_accounts_for_youth" => $savingsAccountsForYouth,
            "total_savings_balance_for_youth" => number_format(abs($totalSavingsBalanceForYouth), 2, '.', ','),
            "average_savings_per_member" => number_format(abs($averageSavingsPerMember), 2, '.', ','),
            'savings_credit_mobilization' => $savingsCreditMobilization,
            'youth_support_rate' => $youthSupportRateFormatted,
            'fund_savings_credit_status' => $fundSavingsCreditStatusFormatted,
            "total_principal_paid" => number_format(abs($totalPrincipalPaid), 2, '.', ','),
            "total_interest_paid" => number_format(abs($totalInterestPaid), 2, '.', ','),
            "total_principal_outstanding" => number_format(abs($totalPrincipalOutstanding), 2, '.', ','),
            "total_interest_outstanding" => number_format(abs($outstandingInterest), 2, '.', ','),
        ];

        return $saccoDetails;
    }
}
