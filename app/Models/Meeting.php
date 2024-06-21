<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    public function administrator()
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }

    // setter for multiple members
    public function setMembersAttribute($value)
    {
        $this->attributes['members'] = json_encode($value);
    }

    // getter for multiple members
    public function getMembersAttribute($value)
    {
        return $this->attributes['members'] = json_decode($value);
    }

    /**
     * Get all meetings for a given group.
     *
     * @param int $groupId Group ID for which meetings are fetched
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function getMeetingsForGroup($groupId)
    {
        return self::where('group_id', $groupId)->get();
    }
}


