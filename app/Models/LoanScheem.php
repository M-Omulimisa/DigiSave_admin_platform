<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanScheem extends Model
{
    use HasFactory;

    // Define the relationship with the Sacco model
    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }
}
