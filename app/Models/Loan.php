<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
