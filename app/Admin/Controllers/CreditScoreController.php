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

    private function calculateAverageAttendance(int $saccoId): string
    {
        // Get total meetings for the sacco
        $totalMeetings = Meeting::where('sacco_id', $saccoId)->count();

        // Avoid division by zero
        if ($totalMeetings === 0) {
            return "0.00";
        }

        // Fetch all meetings for the sacco
        $meetings = Meeting::where('sacco_id', $saccoId)->get();
        $totalPresent = 0; // Initialize total present counter

        foreach ($meetings as $meeting) {
            // Debugging: Check meeting members data
            $membersJson = $meeting->members;
            $attendanceData = json_decode($membersJson, true); // Decode JSON string as an associative array

            // Debugging output
            if (json_last_error() !== JSON_ERROR_NONE) {
                dd('JSON Decode Error: ', json_last_error_msg());
            }

            // Debugging: Display the present count for this meeting
            if (isset($attendanceData['present'])) {
                // dd('Present Count for Meeting:', $attendanceData['present']);
                $totalPresent += $attendanceData['present'];
            }
        }

        // Debugging: Check total present count after loop
        // dd('Total Present:', $totalPresent);

        // Calculate the average attendance per meeting
        $averageAttendance = $totalPresent / $totalMeetings;

        dd($averageAttendance);

        // Return the average attendance, formatted with two decimal places
        return $averageAttendance;
    }

    protected function grid()
    {
        $grid = new Grid(new Sacco());

        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)
                    ->pluck('sacco_id')->toArray();
                $grid->model()
                    ->whereIn('id', $saccoIds)
                    ->whereNotIn('status', ['deleted', 'inactive'])
                    ->whereHas('users', function ($query) {
                        $query->whereIn('position_id', function ($subQuery) {
                            $subQuery->select('id')
                                ->from('positions')
                                ->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                        })
                            ->whereNotNull('phone_number')
                            ->whereNotNull('name');
                    })
                    ->whereHas('meetings', function ($query) {
                        $query->havingRaw('COUNT(*) > 0');
                    })
                    ->with('users') // Eager loading users
                    ->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()
                ->whereNotIn('status', ['deleted', 'inactive'])
                ->whereHas('users', function ($query) {
                    $query->whereIn('position_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('positions')
                            ->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })
                        ->whereNotNull('phone_number')
                        ->whereNotNull('name');
                })
                ->whereHas('meetings', function ($query) {
                    $query->havingRaw('COUNT(*) > 0');
                })
                ->with('users') // Eager loading users
                ->orderBy('created_at', $sortOrder);
        }

        $grid->showExportBtn();
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name');

        $grid->column('id', __('Id'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('created_at', __('Created At'))->sortable()->display(function ($date) {
            return date('d M Y', strtotime($date));
        });

        $grid->column('name', __('Group'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('total_group_members', __('Group Members'))->display(function () {
            return User::where('sacco_id', $this->id)
                ->where(function ($query) {
                    $query->whereNull('user_type')
                        ->orWhere('user_type', '<>', 'Admin');
                })
                ->count();
        });

        // Column for the number of male members
    $grid->column('num_men', __('Number of Men'))->display(function () {
        return User::where('sacco_id', $this->id)
            ->where('sex', 'Male')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();
    });

    // Column for the number of female members
    $grid->column('num_women', __('Number of Women'))->display(function () {
        return User::where('sacco_id', $this->id)
            ->where('sex', 'Female')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();
    });

    // Column for the number of youth members
    $grid->column('num_youth', __('Number of Youths'))->display(function () {
        return User::where('sacco_id', $this->id)
            ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 35')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhere('user_type', '<>', 'Admin');
            })
            ->count();
    });

        $grid->column('total_meetings', __('Total Meetings'))->display(function () {
            return Meeting::where('sacco_id', $this->id)->count();
        });

        // $grid->column('average_attendance_per_meeting', __('Average Attendance Per Meeting'))->display(function () {
        //     // Call the calculateAverageAttendance method within the closure using 'use' to bring in the context of the controller
        //     return app(CreditScoreController::class)->calculateAverageAttendance($this->id);
        // });

        $grid->column('total_member_names', __('Total Member Attendance'))->display(function () {
            $meetings = $this->meetings; // Fetch all meetings for the sacco
            $allMemberNames = [];

            foreach ($meetings as $meeting) {
                $membersJson = $meeting->members;
                // dd($membersJson);
                $attendanceData = json_decode($membersJson, true); // Decode JSON string as an associative array

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Check if 'presentMembersIds' exists and is an array
                    if (isset($attendanceData['presentMembersIds']) && is_array($attendanceData['presentMembersIds'])) {
                        // Iterate over present members and collect their names
                        foreach ($attendanceData['presentMembersIds'] as $member) {
                            if (isset($member['name'])) {
                                $allMemberNames[] = $member['name']; // Collect names
                            }
                        }
                    }
                }
            }

            // Return the count of unique member names
            return count(array_unique($allMemberNames)); // Return the count of unique names
        });

        $grid->column('total_loans', __('Total Loans'))->display(function () {
            return $this->transactions()
                ->where('type', 'LOAN')
                ->whereHas('user', function ($query) {
                    $query->where('user_type', 'admin');
                })
                ->count();
        });

        $grid->column('total_loan_amount', __('Total Loan Amount'))->display(function () {
            return $this->transactions()
                ->where('type', 'LOAN')
                ->whereHas('user', function ($query) {
                    $query->where('user_type', 'admin');
                })
                ->sum('amount');

            return number_format(abs($totalPrincipal), 2, '.', ',');
        });

        // Add dynamic data columns for loans to specific demographics
        $grid->column('loans_to_males', __('Loans to Males'))->display(function () {
            return $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->count();
        });

        // Add dynamic data columns for loans to specific demographics
        $grid->column('loans_amount_to_males', __('Loans Amount to Males'))->display(function () {
            return $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->sum('amount');

            return number_format(abs($totalPrincipal), 2, '.', ',');
        });

        $grid->column('loans_to_females', __('Loans to Females'))->display(function () {
            return $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->count();
        });

        // Add dynamic data columns for loans to specific demographics
        $grid->column('loans_to_amount_females', __('Loans Amount to Females'))->display(function () {
            return $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->sum('amount');

            return number_format(abs($totalPrincipal), 2, '.', ',');
        });

        $grid->column('loans_to_youth', __('Loans to Youth'))->display(function () {
            return $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
                ->count();
        });

        $grid->column('loans_amount_to_youth', __('Loans Amount to Youth'))->display(function () {
            return $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 35')
                ->sum('amount');

            return number_format(abs($totalPrincipal), 2, '.', ',');
        });

        $grid->column('total_principal', __('Total Loan Amount'))->display(function () {
            $totalPrincipal = $this->transactions()
                ->where('type', 'LOAN')
                ->whereHas('user', function ($query) {
                    $query->where('user_type', 'admin');
                })
                ->sum('amount');

            return number_format(abs($totalPrincipal), 2, '.', ',');
        });

        $grid->column('total_loan_repayments', __('Total Loans Paid'))->display(function () {
            $totalPrincipal = $this->transactions()
                ->where('type', 'LOAN_REPAYMENT')
                ->whereHas('user', function ($query) {
                    $query->where('user_type', 'admin');
                })
                ->sum('amount');

            return number_format(abs($totalPrincipal), 2, '.', ',');
        });

        $grid->column('total_interest', __('Total Interest'))->display(function () {
            $totalInterest = $this->transactions()
                ->where('type', 'LOAN_INTEREST')
                ->sum('amount');

            return number_format(abs($totalInterest), 2, '.', ',');
        });

        $grid->column('total_savings_made', __('Savings Made'))->display(function () {
            $maleTotalCount = $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'SHARE')
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->count(); // Ensure count() is called here

            $femaleTotalCount = $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'SHARE')
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->count(); // Ensure count() is called here

            $totalCount = $maleTotalCount + $femaleTotalCount; // This should now be a valid integer addition
            return number_format(abs($totalCount), 2, '.', ',');
        });

        // Add column for total savings balance
        $grid->column('total_savings_balance', __('Total Savings Balance'))->display(function () {
            $maleTotalBalance = $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'SHARE')
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $femaleTotalBalance = $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'SHARE')
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $totalBalance = $maleTotalBalance + $femaleTotalBalance;
            return number_format(abs($totalBalance), 2, '.', ',');
        });

        $grid->column('average_savings_per_member', __('Average Savings Per Member'))->display(function () {
            // Calculate total savings
            $totalSavings = $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            // Calculate the number of distinct members with savings
            $distinctMembers = $this->transactions()
                ->join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->distinct('users.id')
                ->count('users.id');

            $averageSavings = $distinctMembers > 0 ? $totalSavings / $distinctMembers : 0;
            return number_format(abs($averageSavings), 2, '.', ',');
        });

        return $grid;
    }


    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }



    protected function detail($id)
    {
        $show = new Show(Sacco::findOrFail($id));

        $show->field('name', __('Group Name'));

        // Hardcoded values
        $show->field('number_of_loans', __('Number of Loans'))->as(function () {
            return 3;
        });
        $show->field('total_principal', __('Total Principal'))->as(function () {
            return 15000.0;
        });
        $show->field('total_interest', __('Total Interest'))->as(function () {
            return 1500.0;
        });
        $show->field('total_principal_paid', __('Total Principal Paid'))->as(function () {
            return 12000.0;
        });
        $show->field('total_interest_paid', __('Total Interest Paid'))->as(function () {
            return 1200.0;
        });
        $show->field('number_of_savings_accounts', __('Number of Savings Accounts'))->as(function () {
            return 2;
        });
        $show->field('total_savings_balance', __('Total Savings Balance'))->as(function () {
            return 5000.0;
        });
        $show->field('total_principal_outstanding', __('Total Principal Outstanding'))->as(function () {
            return 3000.0;
        });
        $show->field('total_interest_outstanding', __('Total Interest Outstanding'))->as(function () {
            return 300.0;
        });
        $show->field('number_of_loans_to_men', __('Number of Loans to Men'))->as(function () {
            return 2;
        });
        $show->field('total_disbursed_to_men', __('Total Disbursed to Men'))->as(function () {
            return 10000.0;
        });
        $show->field('total_savings_accounts_for_men', __('Total Savings Accounts for Men'))->as(function () {
            return 1;
        });
        $show->field('number_of_loans_to_women', __('Number of Loans to Women'))->as(function () {
            return 1;
        });
        $show->field('total_disbursed_to_women', __('Total Disbursed to Women'))->as(function () {
            return 5000.0;
        });
        $show->field('total_savings_accounts_for_women', __('Total Savings Accounts for Women'))->as(function () {
            return 1;
        });
        $show->field('total_savings_balance_for_women', __('Total Savings Balance for Women'))->as(function () {
            return 2500.0;
        });
        $show->field('number_of_loans_to_youth', __('Number of Loans to Youth'))->as(function () {
            return 1;
        });
        $show->field('total_disbursed_to_youth', __('Total Disbursed to Youth'))->as(function () {
            return 5000.0;
        });
        $show->field('total_savings_balance_for_youth', __('Total Savings Balance for Youth'))->as(function () {
            return 1000.0;
        });

        // Columns for credit score and description
        $show->field('credit_score', __('Credit Score'))->as(function () {
            return '<i class="fa fa-spinner fa-spin"></i>';
        })->unescape();

        $show->field('credit_description', __('Credit Score Description'))->as(function () {
            return '<i class="fa fa-spinner fa-spin"></i>';
        })->unescape();

        return $show;
    }

    public function fetchCreditScores()
    {
        $creditScoreData = [
            "number_of_loans" => 3,
            "total_principal" => 15000.0,
            "total_interest" => 1500.0,
            "total_principal_paid" => 12000.0,
            "total_interest_paid" => 1200.0,
            "number_of_savings_accounts" => 2,
            "total_savings_balance" => 5000.0,
            "total_principal_outstanding" => 3000.0,
            "total_interest_outstanding" => 300.0,
            "number_of_loans_to_men" => 2,
            "total_disbursed_to_men" => 10000.0,
            "total_savings_accounts_for_men" => 1,
            "number_of_loans_to_women" => 1,
            "total_disbursed_to_women" => 5000.0,
            "total_savings_accounts_for_women" => 1,
            "total_savings_balance_for_women" => 2500.0,
            "number_of_loans_to_youth" => 1,
            "total_disbursed_to_youth" => 5000.0,
            "total_savings_balance_for_youth" => 1000.0,
            "savings_per_member" => 2000.0,
            "youth_support_rate" => 0.2,
            "savings_credit_mobilization" => 0.5,
            "fund_savings_credit_status" => 1,
            "cluster_principal_interest" => 3,
            "cluster_loans_principal" => 1
        ];

        $response = Http::post('http://51.8.253.127:8080/predict', $creditScoreData);
        return response()->json($response->json());
    }
}
