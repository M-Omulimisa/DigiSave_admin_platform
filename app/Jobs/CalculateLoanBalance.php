<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Models\LoanScheem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateLoanBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $loans = Loan::where('balance', '>', 0)
                      ->get();
    
        foreach ($loans as $loan) {
            $user = User::find($loan->user_id);
    
            if ($user && $user->user_type !== 'Admin' && $loan->scheme_bill_periodically === 'Yes') {
                $disbursementDate = $loan->created_at;
                $currentDate = now();
                
                $monthsPassed = $disbursementDate->diffInMonths($currentDate);
    
                if ($monthsPassed > 0) {
                    $loanScheme = LoanScheem::find($loan->loan_scheem_id);
                    $interestPercentage = $loanScheme->scheme_periodic_interest_percentage;
                    $newBalance = abs($loan->balance);
    
                    if ($loanScheme->scheme_periodic_interest_type === 'RemainingBalance') {
                        $newBalance += $newBalance * ($interestPercentage / 100) * $monthsPassed;
                    } elseif ($loanScheme->scheme_periodic_interest_type === 'OriginalPrincipal') {
                        $originalAmount = abs($loan->amount);
                        $newBalance += $originalAmount * ($interestPercentage / 100) * $monthsPassed;
                    }
    
                    $loan->balance = -1 * abs($newBalance);
                    $loan->save();
                }
            }
        }
    }
    
    
}
