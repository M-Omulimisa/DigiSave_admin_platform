<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAllocation extends Model
{
    use HasFactory;

    protected $table = 'agent_allocation';
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class, 'sacco_id');
    }
}

