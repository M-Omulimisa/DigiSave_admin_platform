<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentGroupAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agent_id',
        'sacco_id',
        'status',
        'allocated_at',
        'allocated_by'
    ];

    protected $dates = [
        'allocated_at',
        'deleted_at'
    ];

    // Relationship with User (Agent)
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    // Relationship with Sacco (Group)
    public function sacco()
    {
        return $this->belongsTo(Sacco::class, 'sacco_id');
    }

    // Relationship with User (Allocator)
    public function allocator()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    // Accessor for district name through sacco
    public function getDistrictNameAttribute()
    {
        return $this->sacco->district->name ?? 'N/A';
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        // When creating allocation
        static::creating(function ($allocation) {
            $allocation->allocated_at = now();
            $allocation->allocated_by = auth()->id();
            $allocation->status = 'active';
        });
    }
}
