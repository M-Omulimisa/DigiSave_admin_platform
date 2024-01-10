<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SocialFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sacco_id',
        'cycle_id',
        'amount_paid',
        'remaining_balance',
    ];

    // Define the relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function trackPayments($userId, $saccoId, $cycleId, $paymentAmount, $meetingNumber)
    {
        DB::beginTransaction();
        try {
            $activeCycle = Cycle::where('sacco_id', $saccoId)
                ->where('status', 'Active')
                ->first();
    
            if (!$activeCycle) {
                throw new \Exception("Active cycle not found for the sacco.");
            }
    
            $cycleId = $activeCycle->id;
    
            $previousRemainingBalance = 0;

            if ($meetingNumber > 1) {
                // Fetch the balance from the previous meeting
                $previousSocialFund = SocialFund::where('user_id', $userId)
                    ->where('sacco_id', $saccoId)
                    ->where('cycle_id', $cycleId)
                    ->where('meeting_number', $meetingNumber - 1)
                    ->first();
            
                if ($previousSocialFund) {
                    $previousRemainingBalance = $previousSocialFund->remaining_balance;
                } else {
                    // Calculate the amount required for the first meeting (if not paid)
                    $firstMeetingRequiredAmount = $activeCycle->amount_required_per_meeting;
                    $previousRemainingBalance = $firstMeetingRequiredAmount;
                }
            }
            
            $requiredAmount = $activeCycle->amount_required_per_meeting;
            
            $remainingBalance = $previousRemainingBalance + $requiredAmount - $paymentAmount;
            
    
            // Create a transaction record for the payment
            Transaction::create([
                'user_id' => $userId,
                'sacco_id' => $saccoId,
                'type' => 'SOCIAL',
                'amount' => $paymentAmount,
                'remaining_balance' => $remainingBalance,
                'meeting_number' => $meetingNumber,
                'description' => 'Payment for social fund',
            ]);
    
            // Update or create the social fund record for the current meeting
            SocialFund::updateOrCreate(
                [
                    'user_id' => $userId,
                    'sacco_id' => $saccoId,
                    'cycle_id' => $cycleId,
                    'meeting_number' => $meetingNumber,
                ],
                [
                    'amount_paid' => $paymentAmount,
                    'remaining_balance' => $remainingBalance,
                ]
            );
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }
    }

    public static function setInitialRemainingBalance($saccoId, $cycleId, $requiredAmount)
{
    $users = User::where('sacco_id', $saccoId)->get();

    foreach ($users as $user) {
        self::updateOrCreate(
            [
                'user_id' => $user->id,
                'sacco_id' => $saccoId,
                'cycle_id' => $cycleId,
                'meeting_number' => 1, 
            ],
            [
                'amount_paid' => 0,
                'remaining_balance' => $requiredAmount,
            ]
        );
    }
}   
}
