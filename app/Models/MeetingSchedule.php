<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Sacco;
use Illuminate\Support\Facades\Log;

class MeetingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_name',
        'location',
        'district',
        'meeting_date',
        'start_time',
        'end_time',
        'repeat_option',
        'notification',
        'notify_group_members',
        'leader_name',
        'sacco_id',
        'user_id',
        'cycle_id'
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'notify_group_members' => 'boolean',
    ];

    /**
     * Relationship with the Sacco model.
     */
    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    /**
     * Relationship with the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the meeting is today.
     */
    public function isToday()
    {
        return $this->meeting_date->isToday();
    }

    /**
     * Get the Carbon instance of the start time.
     */
    public function getStartDateTimeAttribute()
    {
        return Carbon::parse($this->meeting_date->format('Y-m-d') . ' ' . $this->start_time);
    }

    /**
     * Get the next notification time based on the selected notification setting.
     */
    public function getNotificationTimeAttribute()
    {
        return $this->startDateTime->subMinutes($this->notification);
    }

    /**
     * Determine if a reminder should be sent based on the notification time and current time.
     */
    public function shouldSendReminder()
    {
        $now = Carbon::now();
        return $now->greaterThanOrEqualTo($this->notification_time) && $now->lessThanOrEqualTo($this->startDateTime);
    }

    /**
     * Send reminders to all sacco members with valid phone numbers.
     */
    public function sendReminders()
    {
        $users = User::where('sacco_id', $this->sacco_id)->get();

        $message = "Reminder: You have a meeting scheduled on {$this->meeting_date->format('l, F j, Y')} at {$this->start_time}. Event: {$this->event_name}, Location: {$this->location}.";

        foreach ($users as $user) {
            if (Utils::phone_number_is_valid($user->phone_number)) {
                try {
                    Utils::send_sms($user->phone_number, $message);
                } catch (\Exception $e) {
                    Log::error("Failed to send SMS to {$user->phone_number}: {$e->getMessage()}");
                }
            }
        }
    }
}
