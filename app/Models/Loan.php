<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Loan extends Model
{
    use HasFactory;

    //get balance
    public function getBalanceAttribute()
    {
        return LoanTransaction::where('loan_id', $this->id)->sum('amount');
    }

    //boot
    protected static function boot()
    {
        parent::boot();
        //creating
        static::creating(function ($model) {
            $model->balance = 0;
            $model->scheme_min_balance = 0;
            $model->scheme_max_balance = 0;
        });
        static::created(function ($model) {
            Loan::process_loan($model->id);
        });

        static::updated(function ($model) {
            Loan::process_loan($model->id);
        });

        static::creating(function ($model) {
            $user = User::find($model->user_id);
            if ($user == null) {
                throw new Exception("User not found");
            }

            //get active cycle
            $cycle = Cycle::where('sacco_id', $user->sacco_id)->where('status', 'Active')->first();
            if ($cycle == null) {
                throw new Exception("No active cycle found");
            }
            $model->cycle_id = $cycle->id;

            $model->sacco_id = $user->sacco_id;
            return $model;
        });

        //created process
    }


    //append for user_text
    protected $appends = ['user_text'];

    //getter for user_text
    public function getUserTextAttribute()
    {
        $user = User::find($this->user_id);
        if ($user == null) {
            return "Unknown";
        }
        return $user->name;
    }

    //static process_loan
    public static function process_loan($loan_id)
    {
        $loan = Loan::find($loan_id);
        if ($loan == null) {
            throw new Exception("Loan not found");
        }

        $loan_owner = User::find($loan->user_id);
        if ($loan_owner == null) {
            throw new Exception("Loan owner not found");
        }

        $loan_scheem = LoanScheem::find($loan->loan_scheem_id);
        if ($loan_scheem == null) {
            throw new Exception("Loan scheem not found");
        }

        $amount_paid = LoanTransaction::where('loan_id', $loan_id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $amount_not_paid = LoanTransaction::where('loan_id', $loan_id)
            ->sum('amount');

        $amount_to_be_paid = LoanTransaction::where('loan_id', $loan_id)
            ->where('amount', '<', 0)
            ->sum('amount');

        $principal_amount = $loan->amount;
        $amount_not_paid =  $amount_not_paid;
        $balance =  $amount_not_paid;
        $amount_paid = $amount_paid;
        $is_processed = "Yes";
        $sex_of_beneficiary = $loan_owner->sex;
        $is_refugee = (strtolower($loan_owner->refugee_status) == "yes") ? "Yes" : "No";
        $interest_amount = 0;
        if ($loan_scheem->initial_interest_type == "Percentage") {
            $interest_amount = ($loan_scheem->initial_interest_percentage / 100) * $principal_amount;
        } else {
            $interest_amount = $loan_scheem->initial_interest_flat_amount;
        }

        try {
            DB::table('loans')->where('id', $loan_id)->update([
                'principal_amount' => $principal_amount,
                'amount_paid' => $amount_paid,
                'amount_not_paid' => $amount_not_paid,
                'is_processed' => $is_processed,
                'sex_of_beneficiary' => $sex_of_beneficiary,
                'is_refugee' => $is_refugee,
                'amount_to_be_paid' => $amount_to_be_paid,
                'interest_amount' => $interest_amount,
                'balance' => $balance
            ]);
        } catch (Exception $e) {
            throw new Exception("Failed to process loan");
        }
        $loan = Loan::find($loan_id);
        return true;
    }
}
