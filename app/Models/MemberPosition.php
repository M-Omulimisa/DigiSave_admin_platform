<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPosition extends Model
{
    protected $table = 'positions';
    protected $fillable = ['name', 'sacco_id'];

    public function sacco(): BelongsTo
    {
        return $this->belongsTo(Sacco::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}