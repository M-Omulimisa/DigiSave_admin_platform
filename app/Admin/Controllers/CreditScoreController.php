<?php

namespace App\Admin\Controllers;

use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Http;

class CreditScoreController extends AdminController
{
    protected $title = 'Credit Scores';

    protected function grid()
    {
        $grid = new Grid(new Sacco());

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $grid->model()->orderBy('created_at', $sortOrder);

        $grid->column('name', __('Group Name'))->sortable();

        // Hardcoded values
        $hardcodedValues = [
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
            "total_savings_balance_for_youth" => 1000.0
        ];

        // foreach ($hardcodedValues as $key => $value) {
        //     $grid->column($key, __(ucwords(str_replace('_', ' ', $key))))->display(function () use ($value) {
        //         return $value;
        //     });
        // }

        // Columns for credit score and description
        $grid->column('credit_score', __('Credit Score'))->display(function () {
            return '<span style="color: green;"><i class="fa fa-spinner fa-spin"></i></span>';
        });

        $grid->column('credit_description', __('Credit Score Description'))->display(function () {
            return '<span style="color: green;"><i class="fa fa-spinner fa-spin"></i></span>';
        });

        return $grid;
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
