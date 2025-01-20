<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentGroup extends Model
{
    use HasFactory;

    protected $table = 'agent_groups';

    protected $fillable = [
        'user_id',
        'sacco_id'
    ];

    // Relationship with User (Agent)
    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with Sacco (Group)
    public function group()
    {
        return $this->belongsTo(Sacco::class, 'sacco_id');
    }

    // Method to create a new agent-group relationship
    public static function assignGroupToAgent($agentId, $groupId)
    {
        return self::create([
            'user_id' => $agentId,
            'sacco_id' => $groupId
        ]);
    }

    // Method to get all groups for an agent
    public static function getAgentGroups($agentId)
    {
        return self::where('user_id', $agentId)->with('group')->get();
    }
}
