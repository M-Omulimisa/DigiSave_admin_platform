<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EligibleMember extends Model
{
    protected $fillable = [
        'member_id',
        'max_eligible_amount',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
