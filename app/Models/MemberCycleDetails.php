<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberCycleDetails extends Model
{
    protected $fillable = [
        'cycle_id',
        'user_id',
        'name',
        'shares',
        'loans',
        'loan_repayments',
        'fines',
        'share_out_money',
    ];
}
