<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAllocation extends Model
{
    use HasFactory;

    protected $table = 'agent_allocation';

    protected $fillable = [
        'agent_id',
        'sacco_id',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }
}
