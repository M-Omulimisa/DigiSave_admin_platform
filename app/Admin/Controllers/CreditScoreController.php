<?php

namespace App\Admin\Controllers;

use App\Models\Meeting;
use App\Models\MemberPosition;
use App\Models\OrgAllocation;
use App\Models\Transaction;
use App\Models\Sacco;
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

    $grid->column('name', __('Name'))->sortable()->display(function ($name) {
        return ucwords(strtolower($name));
    });

    $grid->column('total_meetings', __('Total Meetings'))->display(function () {
        return Meeting::where('sacco_id', $this->id)->count();
    });

    $grid->column('total_member_names', __('Total Member Names'))->display(function () {
        $meetings = $this->meetings; // Fetch all meetings for the sacco
        $allMemberNames = []; // Initialize array to collect all member names across meetings

        foreach ($meetings as $meeting) {
            // Access the JSON string directly
            $membersJson = $meeting->members; // Assuming members is the JSON string
            $attendanceData = json_decode($membersJson, true); // Decode JSON string as an associative array

            // Use dd() to debug the JSON structure and any issues
            dd($attendanceData); // This will dump and die, allowing you to inspect the data

            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($attendanceData['presentMembersIds']) && is_array($attendanceData['presentMembersIds'])) {
                    foreach ($attendanceData['presentMembersIds'] as $member) {
                        if (isset($member['name'])) {
                            $allMemberNames[] = $member['name']; // Collect names
                        }
                    }
                } else {
                    // Use dd() to debug if 'presentMembersIds' is missing or not an array
                    dd('presentMembersIds is missing or not an array', $attendanceData);
                }
            } else {
                // Use dd() to debug if there's a JSON decode error
                dd('JSON decode error: ' . json_last_error_msg(), $membersJson);
            }
        }

        // Use dd() to debug the collection of all member names
        dd($allMemberNames);

        return count(array_unique($allMemberNames)); // Return the count of unique names
    });

    $grid->column('average_attendance', __('Average Attendance'))->display(function () {
        $meetings = Meeting::where('sacco_id', $this->id)->get();

        $totalAttendance = 0;
        $meetingCount = $meetings->count();

        foreach ($meetings as $meeting) {
            $attendanceData = json_decode($meeting->attendance, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($attendanceData) && !empty($attendanceData)) {
                if (isset($attendanceData['presentMembersIds']) && is_array($attendanceData['presentMembersIds'])) {
                    $totalAttendance += count($attendanceData['presentMembersIds']);
                }
            }
        }

        return $meetingCount > 0 ? $totalAttendance / $meetingCount : 0;
    });

    $grid->column('total_loans', __('Total Loans'))->display(function () {
        return $this->transactions()
            ->where('type', 'LOAN')
            ->whereHas('user', function ($query) {
                $query->where('user_type', 'admin');
            })
            ->count();
    });

    // Add dynamic data columns for loans to specific demographics
    $grid->column('loans_to_males', __('Loans to Males'))->display(function () {
        return $this->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->count();
    });

    $grid->column('loans_to_females', __('Loans to Females'))->display(function () {
        return $this->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->count();
    });

    $grid->column('loans_to_youth', __('Loans to Youth'))->display(function () {
        return $this->transactions()
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereRaw('TIMESTAMPDIFF(YEAR, users.dob, CURDATE()) < 30')
            ->count();
    });

    $grid->column('total_principal', __('Total Principal'))->display(function () {
        return $this->transactions()
            ->where('type', 'LOAN')
            ->whereHas('user', function ($query) {
                $query->where('user_type', 'admin');
            })
            ->sum('amount');
    });

    $grid->column('total_interest', __('Total Interest'))->display(function () {
        return $this->transactions()
            ->where('type', 'LOAN_INTEREST')
            ->sum('amount');
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

        return $maleTotalBalance + $femaleTotalBalance;
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

        return $distinctMembers > 0 ? $totalSavings / $distinctMembers : 0;
    });

    return $grid;
}


public function transactions() {
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
