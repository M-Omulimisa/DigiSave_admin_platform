<?php

namespace App\Admin\Controllers;

use App\Models\Meeting;
use App\Models\MemberPosition;
use App\Models\OrgAllocation;
use App\Models\Transaction;
use App\Models\Sacco;
use App\Models\User;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CreditScoreController extends AdminController
{
    protected $title = 'Credit Scores';

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

    // Return the view with both the processed collection and individual sacco data
    return view('admin.sacco.analysis', [
        'saccos' => $processedSaccos,  // All SACCOs data
        'sacco' => $processedSaccos->first()  // First SACCO for backward compatibility
    ]);
}

    private function prepareSaccoData($sacco)
    {
        return [
            // Basic Information
            'id' => $sacco->id,
            'name' => $sacco->name,
            'created_at' => $sacco->created_at,
            'status' => $sacco->status,

            // Membership Demographics
            'totalMembers' => $this->calculateTotalMembers($sacco),
            'maleMembers' => $this->calculateMaleMembers($sacco),
            'femaleMembers' => $this->calculateFemaleMembers($sacco),
            'youthMembers' => $this->calculateYouthMembers($sacco),

            // Meeting Statistics
            'totalMeetings' => $this->calculateTotalMeetings($sacco),
            'averageAttendance' => $this->calculateAverageAttendance($sacco),

            // Loan Statistics
            'loanStats' => [
                'total' => $this->calculateTotalLoans($sacco),
                'principal' => $this->calculateTotalPrincipal($sacco),
                'interest' => $this->calculateTotalInterest($sacco),
                'repayments' => $this->calculateTotalRepayments($sacco),

                // Gender-based statistics
                'male' => [
                    'count' => $this->calculateMaleLoans($sacco),
                    'amount' => $this->calculateMaleLoanAmount($sacco)
                ],
                'female' => [
                    'count' => $this->calculateFemaleLoans($sacco),
                    'amount' => $this->calculateFemaleLoanAmount($sacco)
                ],
                'youth' => [
                    'count' => $this->calculateYouthLoans($sacco),
                    'amount' => $this->calculateYouthLoanAmount($sacco)
                ]
            ],

            // Savings Statistics
            'savingsStats' => [
                'totalAccounts' => $this->calculateTotalSavingsAccounts($sacco),
                'totalBalance' => $this->calculateTotalSavingsBalance($sacco),
                'averageSavings' => $this->calculateAverageSavings($sacco),

                // Gender-based statistics
                'male' => [
                    'accounts' => $this->calculateMaleSavingsAccounts($sacco),
                    'balance' => $this->calculateMaleSavingsBalance($sacco)
                ],
                'female' => [
                    'accounts' => $this->calculateFemaleSavingsAccounts($sacco),
                    'balance' => $this->calculateFemaleSavingsBalance($sacco)
                ],
                'youth' => [
                    'accounts' => $this->calculateYouthSavingsAccounts($sacco),
                    'balance' => $this->calculateYouthSavingsBalance($sacco)
                ]
            ],

            // Credit Score
            'creditScore' => $this->calculateCreditScore($sacco)
        ];
    }

    // Calculation Methods

    private function calculateTotalMembers($sacco)
    {
        return $sacco->users()
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })->count();
    }

    private function calculateMaleMembers($sacco)
    {
        return $sacco->users()
            ->where('sex', 'Male')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })->count();
    }

    private function calculateFemaleMembers($sacco)
    {
        return $sacco->users()
            ->where('sex', 'Female')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })->count();
    }

    private function calculateYouthMembers($sacco)
    {
        return $sacco->users()
            ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 35')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })->count();
    }

    private function calculateTotalMeetings($sacco)
    {
        return $sacco->meetings()->count();
    }

    private function calculateAverageAttendance($sacco)
    {
        $meetings = $sacco->meetings;
        $totalAttendance = 0;
        $totalMeetings = $meetings->count();

        foreach ($meetings as $meeting) {
            $membersData = json_decode($meeting->members, true);
            if (isset($membersData['present'])) {
                $totalAttendance += $membersData['present'];
            }
        }

        return $totalMeetings > 0 ? round($totalAttendance / $totalMeetings) : 0;
    }

    // Loan Calculations
    private function calculateTotalLoans($sacco)
    {
        return $sacco->transactions()
            ->where('type', 'LOAN')
            ->whereHas('user', function ($query) {
                $query->where('user_type', 'admin');
            })->count();
    }

    private function calculateTotalPrincipal($sacco)
    {
        return abs($sacco->transactions()
            ->where('type', 'LOAN')
            ->whereHas('user', function ($query) {
                $query->where('user_type', 'admin');
            })->sum('amount'));
    }

    private function calculateTotalInterest($sacco)
    {
        return abs($sacco->transactions()
            ->where('type', 'LOAN_INTEREST')
            ->sum('amount'));
    }

    private function calculateTotalRepayments($sacco)
    {
        return abs($sacco->transactions()
            ->where('type', 'LOAN_REPAYMENT')
            ->whereHas('user', function ($query) {
                $query->where('user_type', 'admin');
            })->sum('amount'));
    }

    private function calculateMaleLoans($sacco)
    {
        return $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->count();
    }

    private function calculateMaleLoanAmount($sacco)
    {
        return abs($sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->sum('transactions.amount'));
    }

    private function calculateFemaleLoans($sacco)
    {
        return $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->count();
    }

    private function calculateFemaleLoanAmount($sacco)
    {
        return abs($sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->sum('transactions.amount'));
    }

    private function calculateYouthLoans($sacco)
    {
        return $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->count();
    }

    private function calculateYouthLoanAmount($sacco)
    {
        return abs($sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->sum('transactions.amount'));
    }

    // Savings Calculations
    private function calculateTotalSavingsAccounts($sacco)
    {
        return $sacco->transactions()
            ->where('type', 'SHARE')
            ->distinct('source_user_id')
            ->count();
    }

    private function calculateTotalSavingsBalance($sacco)
    {
        return abs($sacco->transactions()
            ->where('type', 'SHARE')
            ->sum('amount'));
    }

    private function calculateAverageSavings($sacco)
    {
        $totalSavings = $this->calculateTotalSavingsBalance($sacco);
        $totalAccounts = $this->calculateTotalSavingsAccounts($sacco);

        return $totalAccounts > 0 ? $totalSavings / $totalAccounts : 0;
    }

    private function calculateMaleSavingsAccounts($sacco)
    {
        return $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->distinct('source_user_id')
            ->count();
    }

    private function calculateMaleSavingsBalance($sacco)
    {
        return abs($sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Male')
            ->sum('transactions.amount'));
    }

    private function calculateFemaleSavingsAccounts($sacco)
    {
        return $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Female')
            ->distinct('source_user_id')
            ->count();
    }

    private function calculateFemaleSavingsBalance($sacco)
    {
        return abs($sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->where('users.sex', 'Female')
            ->sum('transactions.amount'));
    }

    private function calculateYouthSavingsAccounts($sacco)
    {
        return $sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->distinct('source_user_id')
            ->count();
    }

    private function calculateYouthSavingsBalance($sacco)
    {
        return abs($sacco->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'SHARE')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
            ->sum('transactions.amount'));
    }

private function getCreditScoreDescription($score)
{
    if ($score >= 80) {
        return "Excellent credit standing. The group demonstrates strong savings culture and reliable loan repayment history.";
    } else if ($score >= 60) {
        return "Good credit standing. The group shows consistent savings and satisfactory loan management.";
    } else if ($score >= 40) {
        return "Fair credit standing. There's room for improvement in savings and loan repayment patterns.";
    } else {
        return "Needs improvement. The group should focus on increasing savings and improving loan repayment rates.";
    }
}

    private function calculateCreditScore($sacco)
    {
        $data = [
            "number_of_loans" => $this->calculateTotalLoans($sacco),
            "total_principal" => $this->calculateTotalPrincipal($sacco),
            "total_interest" => $this->calculateTotalInterest($sacco),
            "total_principal_paid" => $this->calculateTotalRepayments($sacco),
            "number_of_savings_accounts" => $this->calculateTotalSavingsAccounts($sacco),
            "total_savings_balance" => $this->calculateTotalSavingsBalance($sacco),
            "number_of_loans_to_men" => $this->calculateMaleLoans($sacco),
            "total_disbursed_to_men" => $this->calculateMaleLoanAmount($sacco),
            "number_of_loans_to_women" => $this->calculateFemaleLoans($sacco),
            "total_disbursed_to_women" => $this->calculateFemaleLoanAmount($sacco),
            "number_of_loans_to_youth" => $this->calculateYouthLoans($sacco),
            "total_disbursed_to_youth" => $this->calculateYouthLoanAmount($sacco),
            "savings_per_member" => $this->calculateAverageSavings($sacco)
        ];

        try {
            $response = Http::post('https://vslacreditv2-brdqanfsfnd6fbfc.canadacentral-01.azurewebsites.net/predict', $data);
            $result = $response->json();

            return [
                'score' => $result['credit_score'] ?? null,
                'description' => $result['description'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'score' => null,
                'description' => 'Unable to calculate credit score at this time.'
            ];
        }
    }
}
