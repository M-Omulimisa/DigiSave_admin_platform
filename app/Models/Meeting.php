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

    // Optionally, you can use casts instead of custom getter/setter:
    // protected $casts = [
    //     'members' => 'array',
    //     'minutes' => 'array',
    //     'attendance' => 'array',
    // ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    protected function formatAttendanceForDisplay($members)
{
    $memberData = json_decode($members, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($memberData) && !empty($memberData)) {
        if (isset($memberData['presentMembersIds']) && is_array($memberData['presentMembersIds'])) {
            $formattedMembers = '<div class="card-deck">';
            foreach ($memberData['presentMembersIds'] as $member) {
                $formattedMembers .= '<div class="card text-white bg-info mb-3" style="max-width: 18rem;">';
                $formattedMembers .= '<div class="card-body"><h5 class="card-title">' . $member['name'] . '</h5></div>';
                $formattedMembers .= '</div>';
            }
            $formattedMembers .= '</div>';
            return $formattedMembers;
        }
    }
    return 'No attendance recorded';
}

protected function formatMinutesForDisplay($minutes)
{
    $minutesData = json_decode($minutes, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $formattedMinutes = '<div class="row">';
        foreach ($minutesData as $section => $items) {
            $formattedMinutes .= '<div class="col-md-6"><div class="card"><div class="card-body">';
            $formattedMinutes .= '<h5 class="card-title">' . ucfirst(str_replace('_', ' ', $section)) .
                ':</h5><ul class="list-group list-group-flush">';
            foreach ($items as $item) {
                if (isset($item['title']) && isset($item['value'])) {
                    $formattedMinutes .= '<li class="list-group-item">' . $item['title'] . ': ' .
                        $item['value'] . '</li>';
                }
            }
            $formattedMinutes .= '</ul></div></div></div>';
        }
        $formattedMinutes .= '</div>';
        return $formattedMinutes;
    }
    return 'No minutes recorded';
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
        // If $value is a string, decode it.
        if (is_string($value)) {
            return json_decode($value, true);
        }
        // If it's already an object or array, return it as-is.
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
