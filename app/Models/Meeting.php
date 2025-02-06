<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'date', 'location', 'sacco_id', 'administrator_id', 'members', 'minutes', 'attendance', 'cycle_id'
    ];

    // Optionally, you can use casts instead of custom getter/setter
    // protected $casts = [
    //     'members' => 'array',
    //     'minutes' => 'array',
    //     'attendance' => 'array',
    // ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    public function administrator()
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }

    /**
     * Setter for multiple members.
     *
     * @param mixed $value
     */
    public function setMembersAttribute($value)
    {
        $this->attributes['members'] = json_encode($value);
    }

    /**
     * Getter for multiple members.
     *
     * @param mixed $value
     * @return mixed
     */
    public function getMembersAttribute($value)
    {
        // Only decode if the value is a string.
        if (is_string($value)) {
            // Return as an associative array. Remove the assignment.
            return json_decode($value, true);
        }
        return $value;
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
