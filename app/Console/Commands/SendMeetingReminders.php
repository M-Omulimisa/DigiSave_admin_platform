<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MeetingSchedule;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Exception;

class SendMeetingReminders extends Command
{
    protected $signature = 'send:meeting-reminders';
    protected $description = 'Send reminders for upcoming meetings based on schedule and notification settings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get the current date and time
        $now = Carbon::now();

        // Retrieve all upcoming meetings
        $meetings = MeetingSchedule::whereDate('meeting_date', '>=', $now->toDateString())->get();

        foreach ($meetings as $meeting) {
            $meetingDateTime = Carbon::parse($meeting->meeting_date . ' ' . $meeting->start_time);

            // Calculate the notification time
            $notificationTime = $meetingDateTime->copy()->subMinutes($meeting->notification);

            // Check if the current time matches the notification time
            if ($now->greaterThanOrEqualTo($notificationTime) && $now->lessThanOrEqualTo($meetingDateTime)) {
                $this->sendReminders($meeting);
            }
        }
    }

    private function sendReminders($meeting)
    {
        // Find all users in the same sacco_id
        $users = User::where('sacco_id', $meeting->sacco_id)->get();

        // Craft the message with relevant meeting details
        $message = "Reminder: You have a meeting scheduled on {$meeting->meeting_date->format('l, F j, Y')} at {$meeting->start_time}. Event: {$meeting->event_name}, Location: {$meeting->location}.";

        foreach ($users as $user) {
            if ($this->isPhoneNumberValid($user->phone_number)) {
                try {
                    Utils::send_sms($user->phone_number, $message);
                    $this->info("Reminder sent to {$user->phone_number}");
                } catch (Exception $e) {
                    $this->error("Failed to send reminder to {$user->phone_number}: " . $e->getMessage());
                }
            }
        }
    }

    private function isPhoneNumberValid($phoneNumber)
    {
        return Utils::phone_number_is_valid($phoneNumber);
    }
}
