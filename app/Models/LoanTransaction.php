<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanTransaction extends Model
{
    use HasFactory;

    //creatd
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $user = User::find($model->user_id);
            if ($user == null) {
                throw new Exception("User not found");
            }
            $model->sacco_id = $user->sacco_id;
            return $model;
        });

        //creatd
        static::created(function ($model) {
        });
        static::updated(function ($model) {
            return $model;
        });
    }

    //belongs to loan
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
