<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shareout extends Model
{
    use HasFactory;

    protected $fillable = [
        'sacco_id',
        'cycle_id',
        'member_id',
        'shareout_amount',
        'shareout_date',
    ];

    protected $dates = [
        'shareout_date',
    ];

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function member()
    {
        return $this->belongsTo(User::class);
    }
}
