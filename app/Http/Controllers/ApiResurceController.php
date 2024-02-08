<?php

namespace App\Http\Controllers;

use App\AgentMeeting;
use App\Models\Agent;
use App\Models\AgentAllocation;
use App\Models\Association;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\CounsellingCentre;
use App\Models\Crop;
use App\Models\CropProtocol;
use App\Models\Cycle;
use App\Models\District;
use App\Models\Event;
use App\Models\Garden;
use App\Models\GardenActivity;
use App\Models\Group;
use App\Models\Meeting;
use App\Models\Institution;
use App\Models\Job;
use App\Models\Loan;
use App\Models\LoanScheem;
use App\Models\LoanTransaction;
use App\Models\NewsPost;
use App\Models\Person;
use App\Models\Product;
use App\Models\Sacco;
use App\Models\MemberPosition;
use App\Models\Organization;
use App\Models\Parish;
use App\Models\ServiceProvider;
use App\Models\Shareout;
use App\Models\ShareRecord;
use App\Models\SocialFund;
use App\Models\User;
use App\Models\Utils;
use App\Models\Village;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Dflydev\DotAccessData\Util;
use Encore\Admin\Auth\Database\Administrator;
use Exception;
use Illuminate\Http\Request;
use Throwable;

class ApiResurceController extends Controller
{

    use ApiResponser;

    public function get_districts()
    {
        
        $u = auth('api')->user();

        if ($u == null) {
            return $this->error('User not found.');
        }

        return $this->success(
            District::all(),
            $message = "Success.",
            200
        );
    }

    public function get_parishes()
    {
        
        $u = auth('api')->user();

        if ($u == null) {
            return $this->error('User not found.');
        }

        return $this->success(
            Parish::all(),
            $message = "Success.",
            200
        );
    }

    public function get_villages()
    {
        
        $u = auth('api')->user();

        if ($u == null) {
            return $this->error('User not found.');
        }

        return $this->success(
            Village::all(),
            $message = "Success.",
            200
        );
    }

    public function loan_schemes(Request $r)
    {
        $u = auth('api')->user();

        if ($u == null) {
            return $this->error('User not found.');
        }

        return $this->success(
            LoanScheem::where(
                [
                    'sacco_id' => $u->sacco_id
                ]
            )->orderby('id', 'desc')->get(),
            $message = "Success.",
            200
        );
    }

//     public function getPositionsBySaccoId(Request $request)
// {
//     $validatedData = $request->validate([
//         'sacco_id' => 'required|exists:saccos,id',
//     ]);

//     $saccoId = $validatedData['sacco_id'];

//     $positions = MemberPosition::where('sacco_id', $saccoId)->get();

//     return $this->success(
//         $positions,
//         $message = "Success",
//         $statusCode = 200);
// }

    public function loan_transactions(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $conds = [];
        if ($u->isRole('sacco')) {
            $conds = [
                'sacco_id' => $u->sacco_id
            ];
        } else {
            $conds = [
                'user_id' => $u->id
            ];
        }
        return $this->success(
            LoanTransaction::where($conds)->orderby('id', 'desc')->get(),
            $message = "Success",
            200
        );
    } 
    
    public function eligible_members(Request $r)
    {
        $u = auth('api')->user();
    
        if ($u == null) {
            return $this->error('User not found.');
        }
    
        // Fetch schemes associated with the Sacco
        $schemes = LoanScheem::where(['sacco_id' => $u->sacco_id])->get();
    
        // Fetch members associated with the Sacco
        $members = User::where(['sacco_id' => $u->sacco_id])->limit(1000)->orderBy('id', 'desc')->get();
    
        // Initialize an array to store eligible members with scheme details
        $eligibleMembers = [];
    
        // Iterate through schemes to find eligible members and their eligible amounts
        foreach ($schemes as $scheme) {
            foreach ($members as $member) {
                if ($member->balance >= $scheme->max_balance) {
                    // Calculate the maximum eligible amount based on balance for this scheme
                    $eligibleAmount = $member->balance * 3; // Adjust the multiplier as needed
    
                    // Append the scheme ID, eligible amount, and name to the member's data
                    $eligibleMembers[] = [
                        'sacco_id' => $u->sacco_id,
                        'active_cycle_id' => $member->cycle_id,
                        'member_id' => $member->id,
                        'name' => $member->name, // Include the name
                        'scheme_id' => $scheme->id,
                        'max_eligible_amount' => $eligibleAmount,
                    ];
                }
            }
        }
    
        // Return eligible members data with specific fields including name
        return $this->success($eligibleMembers, $message = "Eligible Members with Scheme IDs, Names, and Eligible Amounts", 200);
    }

    public function manifest(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $U = User::find($u->id);
        $U->updated_at = Carbon::now();
        $U->save();
        $sacco = Sacco::find($u->sacco_id);

        // //set header to json output
        // header('Content-Type: application/json');
        // echo json_encode($sacco);
        // die();

        return $this->success(
            json_encode([
                'balance' => $u->balance,
                'name' => $u->name,
                'id' => $u->id,
                'updated_at' => $u->updated_at,
                'sacco' => $sacco,
            ]),
            $message = "Success",
            200
        );
    }


    public function share_record_create(Request $r)
    {
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('User not found.');
        }
        if ($r->user_id == null) {
            return $this->error('User not found.');
        }
        //check for number_of_shares
        if ($r->number_of_shares == null) {
            return $this->error('Number of shares not found.');
        }
        $u = User::find($r->user_id);
        if ($u == null) {
            return $this->error('User not found.');
        }
        $sacco = Sacco::find($u->sacco_id);
        if ($sacco == null) {
            return $this->error('Sacco not found.');
        }
        $share_record = new ShareRecord();
        $share_record->user_id = $u->id;
        $share_record->number_of_shares = $r->number_of_shares;
        $share_record->created_by_id = $admin->id;

        

        try {
            $share_record->save();
        } catch (\Throwable $th) {
            return $this->error('Failed to save share record, because ' . $th->getMessage() . '');
        }
        return $this->success(
            $share_record,
            $message = "Success",
            200
        );
    }


    public function socialFundCreate(Request $request)
    {
        $admin = auth('api')->user();
    
        if ($admin === null) {
            return $this->error('User not found.');
        }
    
        if ($request->user_id === null) {
            return $this->error('User ID not provided.');
        }
    
        $user = User::find($request->user_id);
    
        if ($user === null) {
            return $this->error('User not found.');
        }
    
        $sacco = Sacco::find($user->sacco_id);
    
        if ($sacco === null) {
            return $this->error('Sacco not found.');
        }
    
        // Fetch the active cycle for the user's SACCO
        $activeCycle = Cycle::where('sacco_id', $sacco->id)
                        ->where('status', 'Active')
                        ->first();
    
        if (!$activeCycle) {
            return $this->error('Active cycle not found for the SACCO.');
        }
    
        $requiredAmount = $activeCycle->amount_required_per_meeting;

        $previousRemainingBalance = 0; // Default value if it's the first meeting
        
        if ($request->meeting_number > 1) {
            $previousSocialFund = SocialFund::where('user_id', $user->id)
                ->where('sacco_id', $sacco->id)
                ->where('cycle_id', $activeCycle->id)
                ->where('meeting_number', $request->meeting_number - 1)
                ->first();
        
            if ($previousSocialFund) {
                $previousRemainingBalance = $previousSocialFund->remaining_balance;
            } else {
                // Calculate the amount required for the first meeting (if not paid)
                $firstMeetingRequiredAmount = $activeCycle->amount_required_per_meeting;
                $previousRemainingBalance = $firstMeetingRequiredAmount;
            }
        }
        
        // Calculate the new remaining balance
        $newBalance = $previousRemainingBalance + $requiredAmount - $request->amount_paid;
        
    
        $socialFund = new SocialFund();
        $socialFund->user_id = $user->id;
        $socialFund->created_by_id = $admin->id;
        $socialFund->amount_paid = $request->amount_paid;
        $socialFund->meeting_number = $request->meeting_number;
        $socialFund->sacco_id = $sacco->id;
        $socialFund->cycle_id = $activeCycle->id;
        $socialFund->remaining_balance = $newBalance;
    
        try {
            $socialFund->save();
        } catch (\Throwable $th) {
            return $this->error('Failed to save social fund record: ' . $th->getMessage());
        }
    
        return $this->success(
            $socialFund,
            $message = "Social fund record created successfully",
            200
        );
    }

    public function request_agent_otp_sms(Request $r)
    {

        $r->validate([
            'phone_number' => 'required',
        ]);

        $phone_number = Utils::prepare_phone_number($r->phone_number);
        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Invalid phone number.');
        }
        $acc = Agent::where(['phone_number' => $phone_number])->first();
        // if ($acc == null) {
        //     $acc = Agent::where(['username' => $phone_number])->first();
        // }
        if ($acc == null) {
            return $this->error('Account not found.');
        }
        $otp = rand(10000, 99999) . "";
        if (
            str_contains($phone_number, '256783204665') ||
            str_contains(strtolower($acc->first_name), 'test') ||
            str_contains(strtolower($acc->last_name), 'test')
        ) {
            $otp = '12345';
        }

        $resp = null;
        try {
            $resp = Utils::send_sms($phone_number, $otp . ' is your Digisave OTP.');
        } catch (Exception $e) {
            return $this->error('Failed to send OTP  because ' . $e->getMessage() . '');
        }
        if ($resp != 'success') {
            return $this->error('Failed to send OTP  because ' . $resp . '');
        }
        $acc->password = password_hash($otp, PASSWORD_DEFAULT);
        $acc->save();
        return $this->success(
            $otp . "",
            $message = "OTP sent successfully.",
            200
        );
    }
         

    public function request_otp_sms(Request $r)
    {

        $r->validate([
            'phone_number' => 'required',
        ]);

        $phone_number = Utils::prepare_phone_number($r->phone_number);
        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Invalid phone number.');
        }
        $acc = User::where(['phone_number' => $phone_number])->first();
        if ($acc == null) {
            $acc = User::where(['username' => $phone_number])->first();
        }
        if ($acc == null) {
            return $this->error('Account not found.');
        }
        $otp = rand(10000, 99999) . "";
        if (
            str_contains($phone_number, '256783204665') ||
            str_contains(strtolower($acc->first_name), 'test') ||
            str_contains(strtolower($acc->last_name), 'test')
        ) {
            $otp = '12345';
        }

        $resp = null;
        try {
            $resp = Utils::send_sms($phone_number, $otp . ' is your Digisave OTP.');
        } catch (Exception $e) {
            return $this->error('Failed to send OTP  because ' . $e->getMessage() . '');
        }
        if ($resp != 'success') {
            return $this->error('Failed to send OTP  because ' . $resp . '');
        }
        $acc->password = password_hash($otp, PASSWORD_DEFAULT);
        $acc->save();
        return $this->success(
            $otp . "",
            $message = "OTP sent successfully.",
            200
        );
    }
    public function loans(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $conds = [];
        if ($u->isRole('sacco')) {
            $conds = [
                'sacco_id' => $u->sacco_id
            ];
        } else {
            $conds = [
                'user_id' => $u->id
            ];
        }
        return $this->success(
            Loan::where($conds)->orderby('id', 'desc')->get(),
            $message = "Success",
            200
        );
    }

    public function cycles(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $conds = [];
        $conds = [
            'sacco_id' => $u->sacco_id
        ];
        return $this->success(
            Cycle::where($conds)->orderby('id', 'desc')->get(),
            $message = "Success",
            200
        );
    }

    public function get_orgs(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        return $this->success(
            Organization::all(),
            $message = "Success",
            200
        );
    }


    public function get_positions()
{
    $user = auth('api')->user();

    if ($user) {
        $sacco = Sacco::where('administrator_id', $user->id)->first();

        if ($sacco) {
            $saccoId = $sacco->id;
            $positions = MemberPosition::where('sacco_id', $saccoId)->get();
            return $this->success(
            $positions,
            $message = "Success",
            $statusCode = 200);
        }
    }
    return response()->json(['message' => 'User or associated Sacco not found'], 404);
}

    
//     public function get_positions()
// {
//     $u = auth('api')->user();
//     if ($u == null) {
//         return $this->error('User not found.');
//     }

//     $conds = [];
//     $positions = [];

//     if ($u->isRole('sacco')) {
//         $conds = ['sacco_id' => $u->sacco_id];
//     } else {
//         $conds = ['user_id' => $u->id];
//     }

//     $positions = MemberPosition::where($conds)->orderBy('id', 'desc')->get();

//     if ($positions->isEmpty()) {
//         return $this->error('No positions found for the user.');
//     }

//     // Build the response data including user and positions
//     $responseData = [
//         'user' => $u, // Include user information
//         'positions' => $positions,
//     ];

//     return $this->success(
//         $responseData,
//         $message = "Success",
//         $statusCode = 200
//     );
// }


    public function share_records(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $conds = [];

        if ($u->isRole('sacco')) {
            $conds = [
                'sacco_id' => $u->sacco_id
            ];
        } else {
            $conds = [
                'user_id' => $u->id
            ];
        }

        return $this->success(
            ShareRecord::where($conds)->orderby('id', 'desc')->get(),
            $message = "Success",
            200
        );
    }

    public function socialFundRecords(Request $request)
    {
        $user = auth('api')->user();
    
        if ($user === null) {
            return $this->error('User not found.');
        }
    
        $conditions = [];
    
        if ($user->isRole('sacco')) {
            $conditions = [
                'sacco_id' => $user->sacco_id
            ];
        } else {
            $conditions = [
                'user_id' => $user->id
            ];
        }
    
        // Fetch the active cycle's required amount
        $activeCycle = Cycle::where('status', 'Active')
                        ->where('sacco_id', $user->sacco_id) // Adjust this condition if needed
                        ->first();
    
        if (!$activeCycle) {
            return $this->error('Active cycle not found.');
        }
    
        $requiredAmount = $activeCycle->amount_required_per_meeting;
    
        $socialFunds = SocialFund::where($conditions)
                        ->orderBy('id', 'desc')
                        ->get();
    
        // Append the required amount to each social fund record
        $socialFunds->each(function ($socialFund) use ($requiredAmount) {
            $socialFund->required_amount = $requiredAmount;
        });
    
        return $this->success(
            $socialFunds,
            $message = "Social fund records retrieved successfully",
            200
        );
    }    

    public function transactions(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $conds = [];
        if ($u->isRole('sacco')) {
            $conds = [
                'sacco_id' => $u->sacco_id
            ];
        } else {
            $conds = [
                'user_id' => $u->id
            ];
        }
        return $this->success(
            Transaction::where($conds)->orderby('id', 'desc')->get(),
            $message = "Success",
            200
        );
    }


    public function saccos(Request $r)
    {
        return $this->success(
            Sacco::where([])->orderby('id', 'desc')->get(),
            $message = "Sussess",
            200
        );
    }

    public function agent_allocations(Request $r)
    {
        return $this->success(
            AgentAllocation::where([])->orderby('id', 'desc')->get(),
            $message = "Sussess",
            200
        );
    }

    public function loan_create(Request $r)
    {
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('Admin not found.');
        }

        if (!isset($r->user_id)) {
            return $this->error('User account id not found.');
        }
        $u = User::find($r->user_id);

        if ($u == null) {
            return $this->error('User account found.');
        }

        if (
            $r->loan_scheem_id == null ||
            $r->amount == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }

        $loan_scheem = LoanScheem::find($r->loan_scheem_id);
        if ($loan_scheem == null) {
            return $this->error('Loan scheem not found.');
        }

        $total_deposit = Transaction::where([
            'user_id' => $u->id,
        ])
            ->where('amount', '>', 0)
            ->sum('amount');

        if ($loan_scheem->min_balance > $total_deposit) {
            return $this->error('You have not saved enough money to apply for this loan. You need to save at least UGX ' . number_format($loan_scheem->min_balance) . ' to apply for this loan.');
        }

        $oldLoans = Loan::where([
            'user_id' => $u->id,
            'is_fully_paid' => 'No',
        ])->get();

        if (count($oldLoans) > 0) {
            return $this->error('You have an existing loan that is not fully paid. You cannot apply for another loan until you have fully paid the existing loan.');
        }

        $sacco = Sacco::find($u->sacco_id);
        if ($sacco == null) {
            return $this->error('Sacco not found.');
        }

        if ($loan_scheem->max_amount < $r->amount) {
            return $this->error('You cannot apply for a loan of more than UGX ' . number_format($loan_scheem->max_amount) . '.');
        }

        if ($sacco->balance < $r->amount) {
            return $this->error('The sacco does not have enough money to lend you UGX ' . number_format($r->amount) . '.');
        }



        $loanAmount = $r->amount;
        $loanAmount = abs($loanAmount);
        $loanInterest = $loan_scheem->initial_interest_percentage / 100 * $loanAmount;
        $amount = $loanAmount + $loanInterest;
        $amount = -1 * $amount;
        DB::beginTransaction();
        try {

            $loan = new Loan();
            $loan->sacco_id = $u->sacco_id;
            $loan->user_id = $u->id;
            $loan->loan_scheem_id = $r->loan_scheem_id;
            $loan->amount = $loanAmount;
            $loan->balance = $amount;
            $loan->is_fully_paid = 'No';
            $loan->scheme_name = $loan_scheem->name;
            $loan->scheme_description = $loan_scheem->description;
            $loan->scheme_initial_interest_type = $loan_scheem->initial_interest_type;
            $loan->scheme_initial_interest_flat_amount = $loan_scheem->initial_interest_flat_amount;
            $loan->scheme_initial_interest_percentage = $loan_scheem->initial_interest_percentage;
            $loan->scheme_bill_periodically = $loan_scheem->bill_periodically;
            $loan->scheme_billing_period = $loan_scheem->billing_period;
            $loan->scheme_periodic_interest_type = $loan_scheem->periodic_interest_type;
            $loan->scheme_periodic_interest_percentage = $loan_scheem->periodic_interest_percentage;
            $loan->scheme_periodic_interest_flat_amount = $loan_scheem->periodic_interest_flat_amount;
            $loan->scheme_min_amount = $loan_scheem->min_amount;
            $loan->scheme_max_amount = $loan_scheem->max_amount;
            $loan->scheme_min_balance = $loan_scheem->min_balance;
            $loan->scheme_max_balance = $loan_scheem->max_balance;
            $loan->reason = $r->reason;

            try {
                $loan->save();
                DB::commit();
                //success
                dispatch(new \App\Jobs\CalculateLoanBalance());
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->error('Failed to save loan, because ' . $th->getMessage() . '');
            }


            $sacco_transactions = new Transaction();
            $sacco_transactions->user_id = $sacco->administrator_id;
            $sacco_transactions->source_user_id = $u->id;
            $sacco_transactions->sacco_id = $sacco->id;
            $sacco_transactions->type = 'LOAN';
            $sacco_transactions->source_type = 'Loan';
            $sacco_transactions->source_mobile_money_number = null;
            $sacco_transactions->source_mobile_money_transaction_id = null;
            $sacco_transactions->source_bank_account_number = null;
            $sacco_transactions->source_bank_transaction_id = null;
            $sacco_transactions->desination_bank_account_number = null;
            $sacco_transactions->desination_type = 'User';
            $sacco_transactions->desination_mobile_money_number = $u->phone_number;
            $sacco_transactions->desination_mobile_money_transaction_id = null;
            $sacco_transactions->desination_bank_transaction_id = null;
            $sacco_transactions->amount = (-1*(abs($loanAmount)));
            $sacco_transactions->description = "Loan Disbursement of UGX " . number_format($loanAmount) . " to {$u->phone_number} - $u->name. Loan Scheem: {$loan_scheem->name}. Reference: {$loan->id}.";
            $sacco_transactions->details = "Loan Disbursement of UGX " . number_format($loanAmount) . " to {$u->phone_number} - $u->name. Loan Scheem: {$loan_scheem->name}. Reference: {$loan->id}.";
            try {
                $sacco_transactions->save();
                DB::rollBack();
            } catch (\Throwable $th) {
                return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
            }

            $receiver_transactions = new Transaction();
            $receiver_transactions->user_id = $u->id;
            $receiver_transactions->source_user_id = $sacco->administrator_id;
            $receiver_transactions->type = 'LOAN_INTEREST';
            $receiver_transactions->source_type = 'LOAN_INTEREST';
            $receiver_transactions->source_mobile_money_number = null;
            $receiver_transactions->source_mobile_money_transaction_id = null;
            $receiver_transactions->source_bank_account_number = null;
            $receiver_transactions->source_bank_transaction_id = null;
            $receiver_transactions->desination_bank_account_number = null;
            $receiver_transactions->desination_type = 'User';
            $receiver_transactions->desination_mobile_money_number = $u->phone_number;
            $receiver_transactions->desination_mobile_money_transaction_id = null;
            $receiver_transactions->desination_bank_transaction_id = null;
            $amount = abs($loanInterest);
            $receiver_transactions->amount = $loanInterest;
            $receiver_transactions->description = "Aloan Interest of UGX " . number_format($loanInterest) . " from  $sacco->name -  Sacco Loan Scheem: {$loan_scheem->name}. Reference: {$loan->id}.";
            $receiver_transactions->details = "Aloan Interest of UGX " . number_format($loanInterest) . " from  $sacco->name -  Sacco Loan Scheem: {$loan_scheem->name}. Reference: {$loan->id}.";

            try {
                $receiver_transactions->save();
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
            }


            $LoanTransaction = new LoanTransaction();
            $LoanTransaction->user_id = $u->id;
            $LoanTransaction->loan_id = $loan->id;
            $LoanTransaction->sacco_id = $sacco->id;
            $amount = abs($loanAmount);
            $LoanTransaction->amount = -1 * $loanAmount;
            $LoanTransaction->balance = 0;
            $LoanTransaction->description = "Borrowed UGX " . number_format($loanAmount) . " from {$sacco->name} - {$loan_scheem->name}. Reference: {$loan->id}.";
            $LoanTransaction->save();
            $LoanTransaction->balance = $loan->balance;
            $LoanTransaction->save();

            $initialBalance = $loan->balance;
            if ($loan_scheem->initial_interest_type == 'Flat') {
                $initialBalance =  $loan->initial_interest_flat_amount;
            } else {
                $_amount = abs($amount);
                $initialBalance =  (($loan_scheem->initial_interest_percentage / 100)) * $_amount;
            }
            $initialBalance = abs($initialBalance);
            $initialInterestTransaction = new LoanTransaction();
            $initialInterestTransaction->user_id = $u->id;
            $initialInterestTransaction->loan_id = $loan->id;
            $initialInterestTransaction->sacco_id = $sacco->id;
            $initialInterestTransaction->amount = -1 * $initialBalance;
            $initialInterestTransaction->balance = $initialBalance;
            $initialInterestTransaction->description = "Initial Interest of UGX " . number_format($initialBalance) . " for {$sacco->name} - {$loan_scheem->name}. Reference: {$loan->id}.";
            $initialInterestTransaction->save();
            $LoanTransaction->balance = $loan->balance;
            $LoanTransaction->save();
            $balance = LoanTransaction::where('loan_id', $loan->id)->sum('amount');

            // dd("Loan balanace: $balance");
            $loan->$balance;
            try {
                $loan->save();
                // dd("New loan details: $loan");
                //success
                DB::commit();
                return $this->success($loan, $message = "Loan created successfully.", 200);
    
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->error('Failed to save loan, because ' . $th->getMessage() . '');
            }

            DB::commit(); 
            return $this->success(null, $message = "Loan applied successfully. You will receive a confirmation message shortly.", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error('Failed, because ' . $th->getMessage() . '');
        }
    }

    public function transactions_create(Request $r)
    {
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('User not found.');
        }
        /*         if ($admin->user_type != 'Admin') {
            return $this->error('Only admins can create a transaction.');
        } */

        $u = User::find($r->user_id);
        if ($u == null) {
            return $this->error('Receiver account not found.');
        }

        if ($u == null) {
            return $this->error('User not found.');
        }
        if (
            $r->type == null ||
            $r->amount == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }

        include_once(app_path() . '/Models/Utils.php');

        if (!in_array($r->type, TRANSACTION_TYPES)) {
            throw new Exception("Invalid transaction type.");
        }

        if ($r->type == 'WITHDRAWAL') {
            $amount = abs($r->amount);
            if ($u->balance < $amount) {
                return $this->error('You do not have enough money to withdraw UGX ' . number_format($amount) . '. Your balance is UGX ' . number_format($u->balance) . '.');
            }
            $amount = -1 * $amount;
            try {
                DB::beginTransaction();
                //create positive transaction for user
                $transaction_user = new Transaction();
                $transaction_user->user_id = $u->id;
                $transaction_user->source_user_id = $admin->id;
                $transaction_user->sacco_id = $u->sacco_id;
                $transaction_user->type = 'WITHDRAWAL';
                $transaction_user->source_type = 'WITHDRAWAL';
                $transaction_user->amount = $amount;
                $transaction_user->details = $r->description;
                $transaction_user->description = "Withdrawal of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name.";
                try {
                    $transaction_user->save();
                    
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $u->id;
                $transaction_sacco->sacco_id = $u->sacco_id;
                $transaction_sacco->type = 'WITHDRAWAL';
                $transaction_sacco->source_type = 'WITHDRAWAL';
                $transaction_sacco->amount = $amount;
                $transaction_user->details = $r->description;
                $transaction_sacco->description = "Withdrawal of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                DB::commit();
                    return $this->success(null, "Withdrawal of UGX " . number_format($amount) . " was successful. Your balance is now UGX " . number_format($u->balance) . ".", 200);
            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }
        } elseif ($r->type == 'FINE') {
            $amount = abs($r->amount);
            try {
                DB::beginTransaction();
                //create NEGATIVE transaction for user
                $transaction_user = new Transaction();
                $transaction_user->user_id = $u->id;
                $transaction_user->source_user_id = $admin->id;
                $transaction_user->sacco_id = $u->sacco_id;
                $transaction_user->type = 'FINE';
                $transaction_user->source_type = 'FINE';
                $transaction_user->amount = -1 * $amount;
                $transaction_user->details = $r->description;
                $transaction_user->description = "Fine of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name. Reason: {$r->description}.";
                try {
                    $transaction_user->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $u->id;
                $transaction_sacco->sacco_id = $u->sacco_id;
                $transaction_sacco->type = 'FINE';
                $transaction_sacco->source_type = 'FINE';
                $transaction_sacco->amount = $amount;
                $transaction_user->details = $r->description;
                $transaction_sacco->description = "Fine of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name. Reason: {$r->description}.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                DB::commit();
                return $this->success(null, "Fine of UGX " . number_format($amount) . " was successfully applied.", 200);

            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }
        }else if ($r->type == 'REGESTRATION') {

            $amount = abs($r->amount);
            try {
                DB::beginTransaction();
                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $u->id;
                $transaction_sacco->sacco_id = $u->sacco_id;
                $transaction_sacco->type = 'REGESTRATION';
                $transaction_sacco->source_type = 'REGESTRATION';
                $transaction_sacco->amount = $amount;
                $transaction_user->details = $r->description;
                $transaction_sacco->description = "Registration fees of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                DB::commit();
                return $this->success(null, "Registration fees of UGX " . number_format($amount) . " was successful. Your balance is now UGX " . number_format($u->balance) . ".", 200);
            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }


            //create positive transaction for sacco
        } 

         else if ($r->type == 'SAVING') {

            $amount = abs($r->amount);
            try {
                DB::beginTransaction();
                //create positive transaction for user
                $transaction_user = new Transaction();
                $transaction_user->user_id = $u->id;
                $transaction_user->source_user_id = $admin->id;
                $transaction_user->sacco_id = $u->sacco_id;
                $transaction_user->type = 'SAVING';
                $transaction_user->source_type = 'SAVING';
                $transaction_user->amount = $amount;
                $transaction_user->details = $r->description;
                $transaction_user->description =  "Saving of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name.";
                try {
                    $transaction_user->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $u->id;
                $transaction_sacco->sacco_id = $u->sacco_id;
                $transaction_sacco->type = 'SAVING';
                $transaction_sacco->source_type = 'SAVING';
                $transaction_sacco->amount = $amount;
                $transaction_user->details = $r->description;
                $transaction_sacco->description = "Saving of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                DB::commit();
                return $this->success(null, "Saving of UGX " . number_format($amount) . " was successful. Your balance is now UGX " . number_format($u->balance) . ".", 200);
            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }


            //create positive transaction for sacco
        } else if ($r->type == 'LOAN_REPAYMENT') {
            $loan = Loan::find($r->loan_id);
            if ($loan == null) {
                return $this->error('Loan not found.');
            }
            $amount = abs($r->amount);
            if (((int)($amount)) > ((abs($loan->balance)))) {
                return $this->error('You cannot pay more than the loan balance.');
            }
            $record = new LoanTransaction();
            $record->user_id = $u->id;
            $acc_balance = $u->balance;

            // if ($amount > $acc_balance) {
            //     return $this->error('You do not have enough money to pay this loan. Your balance is UGX ' . number_format($acc_balance) . '.');
            // }

            $amount = abs($r->amount);
            try {
                DB::beginTransaction();
                //reduce user balance
                // $transaction_user = new Transaction();
                // $transaction_user->user_id = $u->id;
                // $transaction_user->source_user_id = $admin->id;
                // $transaction_user->sacco_id = $u->sacco_id;
                // $transaction_user->type = 'LOAN_REPAYMENT';
                // $transaction_user->source_type = 'LOAN_REPAYMENT';
                // $transaction_user->amount = -1 * $amount;
                // $transaction_user->description = "Loan Repayment of UGX " . number_format($amount) . " to {$u->phone_number} - $u->name. Loan Scheem: {$loan->scheme_name}. Reference: {$loan->id}.";
                // $transaction_user->details = "Loan Repayment of UGX " . number_format($amount) . " to {$u->phone_number} - $u->name. Loan Scheem: {$loan->scheme_name}. Reference: {$loan->id}.";
                // try {
                //     if ($loan->balance == 0) {
                //         // Update is_fully_paid field to 'Yes' as the loan is fully paid
                //         $loan->is_fully_paid = 'Yes';
                //         $loan->save();
                //     }
                //     $transaction_user->save();
                // } catch (\Throwable $th) {
                //     DB::rollback();
                //     return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                // }

                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $u->id;
                $transaction_sacco->sacco_id = $u->sacco_id;
                $transaction_sacco->type = 'LOAN_REPAYMENT';
                $transaction_sacco->source_type = 'LOAN_REPAYMENT';
                $transaction_sacco->amount = $amount;
                $transaction_sacco->description = "Loan Repayment of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name. Loan Scheem: {$loan->scheme_name}. Reference: {$loan->id}.";
                $transaction_sacco->details = "Loan Repayment of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name. Loan Scheem: {$loan->scheme_name}. Reference: {$loan->id}.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                //create loan transaction
                $loan_transaction = new LoanTransaction();
                $loan_transaction->user_id = $u->id;
                $loan_transaction->loan_id = $loan->id;
                $loan_transaction->sacco_id = $u->sacco_id;
                $loan_transaction->amount = $amount;
                $loan_transaction->description = "Loan Repayment of UGX " . number_format($amount) . " from {$u->phone_number} - $u->name. Loan Scheem: {$loan->scheme_name}. Reference: {$loan->id}.";
                try {
                    $loan_transaction->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                if ($loan->balance == 0) {
                    $loan->is_fully_paid = 'Yes'; 
                    try {
                        $loan->save();
                    } catch (\Throwable $th) {
                        DB::rollback();
                        return $this->error('Failed to update loan status, because ' . $th->getMessage() . '');
                    }
                }
                
                DB::commit();
                return $this->success($loan, $message = "Loan repayment of UGX " . number_format($amount) . " was successful. Your balance is now UGX " . number_format($u->balance) . ".", 200);
            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }
            return;
        }


        $tra = new Transaction();
        $tra->user_id = $u->id;
        $tra->source_user_id = $admin->id;
        $tra->type = $r->type;
        $tra->source_type = $r->source_type;
        $tra->source_mobile_money_number = $r->source_mobile_money_number;
        $tra->source_mobile_money_transaction_id = $r->source_mobile_money_transaction_id;
        $tra->source_bank_account_number = $r->source_bank_account_number;
        $tra->source_bank_transaction_id = $r->source_bank_transaction_id;
        $tra->desination_type = $r->desination_type;
        $tra->desination_mobile_money_number = $r->desination_mobile_money_number;
        $tra->desination_mobile_money_transaction_id = $r->desination_mobile_money_transaction_id;
        $tra->desination_bank_account_number = $r->desination_bank_account_number;
        $tra->desination_bank_transaction_id = $r->desination_bank_transaction_id;
        $tra->amount = $r->amount;
        $tra->description = $r->description;
        $tra->details = $r->details;

        try {
            $tra->save();
            return $this->success(null, $message = "Transaction created successfully.", 200);
        } catch (\Throwable $th) {
            return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
        }
    }


    public function transactions_transfer(Request $r)
    {
        $sender = auth('api')->user();
        if ($sender == null) {
            return $this->error('User not found.');
        }
        if (
            $r->amount == null ||
            $r->desination_type == null ||
            $r->receiver_id == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }

        $receiver = User::find($r->receiver_id);
        if ($receiver == null) {
            return $this->error('Receiver not found.');
        }

        try {
            Transaction::send_money($sender->id, $receiver->id, $r->amount, $r->description, $r->desination_type);
            return $this->success(null, $message = "Sent UGX " . number_format($r->amount) . " to {$receiver->phone_number} - $receiver->name. Your balance is now UGX " . number_format($sender->balance) . ".", 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage() . '');
        }
    }

    public function sacco_join_request(Request $r)
    {

        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $sacco = Sacco::find($r->sacco_id);
        if ($sacco == null) {
            return $this->error('Sacco not found.');
        }
        $user = User::find($u->id);
        $user->sacco_join_status = 'Pending';
        $user->save();
        return $this->success(
            'Success',
            $message = "Request submitted successfully.",
        );
    }

    public function crops(Request $r)
    {
        $items = [];

        foreach (Crop::all() as $key => $crop) {
            $protocols = CropProtocol::where([
                'crop_id' => $crop->id
            ])->get();
            $crop->protocols = json_encode($protocols);

            $items[] = $crop;
        }

        return $this->success(
            $items,
            $message = "Success",
            200
        );
    }

    public function garden_activities(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }

        $gardens = [];
        if ($u->isRole('agent')) {
            $gardens = GardenActivity::where([])
                ->limit(1000)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $gardens = GardenActivity::where(['user_id' => $u->id])
                ->limit(1000)
                ->orderBy('id', 'desc')
                ->get();
        }

        return $this->success(
            $gardens,
            $message = "Success",
            200
        );
    }

    public function my_sacco_membership(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $members = User::where(['id' => $u->id])
            ->limit(1)
            ->orderBy('id', 'desc')
            ->get();
        return $this->success(
            $members,
            $message = "Success",
            200
        );
    }
    public function sacco_members(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $members = User::where(['sacco_id' => $u->sacco_id])
            ->limit(1000)
            ->orderBy('id', 'desc')
            ->get();
        return $this->success(
            $members,
            $message = "Success",
            200
        );
    }

    
    public function scheduleMeeting(Request $request)
    {
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('User not found.');
        }
        if ($request->user_id == null) {
            return $this->error('User not found.');
        }
        $u = User::find($request->user_id);
            // Create a new meeting using the AgentMeeting model
            $meeting = new AgentMeeting();
            $meeting->user_id = $u->id;
            $meeting->sacco_id = $request->input('sacco_id');
            $meeting->meeting_date = $request->input('meeting_date');
            $meeting->meeting_time = $request->input('meeting_time');
            $meeting->meeting_description = $request->input('meeting_description');
           

            try {
                $meeting->save();
            } catch (\Throwable $th) {
                return $this->error('Failed to schedule meeting record, because ' . $th->getMessage() . '');
            }
            return $this->success(
                $meeting,
                $message = "Success",
                200
            );
    }

    public function get_agent_meetings(Request $request)
{
    $user = auth('api')->user();
    if (!$user) {
        return $this->error('User not found.');
    }

    // Retrieve meetings associated with the logged-in user
    $meetings = AgentMeeting::where('user_id', $user->id)->get();

    if ($meetings->isEmpty()) {
        return $this->error('No meetings found for the user.');
    }

    return $this->success($meetings, $message="Meetings retrieved successfully", 200);
}

    

    public function cycles_create(Request $r)
    {

        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $cycle = new Cycle();
        $cycle->name = $r->name;
        $cycle->sacco_id = $u->sacco_id;
        $cycle->amount_required_per_meeting = $r->amount_required_per_meeting;
        $cycle->share_price = $r->share_price;
        $cycle->min_share_price = $r->min_share_price;
        $cycle->max_share_price = $r->max_share_price;
        $cycle->created_by_id = $u->id;
        $cycle->status = $r->status;
        $cycle->start_date = Carbon::parse($r->start_date);
        $cycle->end_date = Carbon::parse($r->end_date);
        try {
            $cycle->save();
            return $this->success(null, $message = "Success created!", 200);
        } catch (\Throwable $th) {
            return $this->error('Failed to save cycle, because ' . $th->getMessage() . '');
        }
    }

    public function deactivateCycle($cycleId)
{
    $u = auth('api')->user();

    if ($u == null) {
        return $this->error('User not found.');
    }

    $cycle = Cycle::find($cycleId);

    if ($cycle == null) {
        return $this->error('Cycle not found.');
    }
    if ($u->sacco_id != $cycle->sacco_id) {
        return $this->error('You do not have permission to update this cycle.');
    }

    try {
        $cycle->status = 'Inactive';
        $cycle->save();
        return $this->success($cycle, $message = "Cycle ended successfully!", 200);
    } catch (\Throwable $th) {
        return $this->error('Failed to end cycle, because ' . $th->getMessage());
    }
}


    public function meetings()
    {
        // Get the currently logged-in user
        $user = auth('api')->user();

        if ($user) {
            $sacco = Sacco::where('administrator_id', $user->id)->first();

            if ($sacco) {
                $groupId = $sacco->id;

                $meetings = Meeting::where('sacco_id', $groupId)->get();

                return $this->success($meetings, 'Meetings for the user\'s group');
            }

            return $this->error('Sacco not found for the administrator');
        }

        return $this->error('User not found');
    }

    public function register_meeting(Request $request)
{
    $admin = auth('api')->user();

    if ($admin === null) {
        return $this->error('User not found.');
    }

    // Check if the user is a SACCO admin
    if (!$admin->isRole('sacco')) {
        return $this->error('User is not a SACCO admin.');
    }

    // Get the SACCO ID based on the admin user
    $sacco = Sacco::where('administrator_id', $admin->id)->first();

    if ($sacco === null) {
        return $this->error('SACCO not found for the administrator.');
    }

    // Get the current active cycle for the SACCO
    $activeCycle = Cycle::where('sacco_id', $sacco->id)
                        ->where('status', 'Active')
                        ->first();

    if ($activeCycle === null) {
        return $this->error('No active cycle found for the SACCO.');
    }

    $meeting = new Meeting();
    $meeting->name = $request->input('name');
    $meeting->date = $request->input('date');
    $meeting->location = $request->input('location');
    $meeting->sacco_id = $sacco->id;
    $meeting->administrator_id = $admin->id;
    $meeting->members = $request->input('members');
    $meeting->minutes = $request->input('minutes');
    $meeting->attendance = $request->input('attendance');
    $meeting->cycle_id = $activeCycle->id; // Set the active cycle's ID

    try {
        $meeting->save();
        return $this->success($meeting, $message = "Meeting created successfully.", 200);
    } catch (\Throwable $th) {
        return $this->error('Failed to create meeting: ' . $th->getMessage());
    }
}

    public function cycles_update(Request $r, $saccoId, $cycleId)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
    
        try {
            $cycle = Cycle::where('sacco_id', $saccoId)
                ->where('id', $cycleId)
                ->firstOrFail(); // Find the cycle by its SACCO ID and Cycle ID
                
    
            // Update cycle attributes if they are provided in the request
            if ($r->has('name')) {
                $cycle->name = $r->name;
            }
            if ($r->has('description')) {
                $cycle->description = $r->description;
            }
            if ($r->has('amount_required_per_meeting')) {
                $cycle->amount_required_per_meeting = $r->amount_required_per_meeting;
            }
            if ($r->has('status')) {
                $cycle->status = $r->status;
            }
            if ($r->has('start_date')) {
                $cycle->start_date = Carbon::parse($r->start_date);
            }
            if ($r->has('end_date')) {
                $cycle->end_date = Carbon::parse($r->end_date);
            }
    
            // Ensure the cycle being updated belongs to the authenticated user's SACCO
            if ($cycle->sacco_id !== $u->sacco_id) {
                return $this->error('Unauthorized. This cycle does not belong to your SACCO.');
            }
    
            $cycle->save(); // Save the updated cycle
    
            // Retrieve the updated cycle data
            $updatedCycle = Cycle::findOrFail($cycle->id);
    
            return $this->success($updatedCycle, $message = "Cycle updated successfully!", 200);
        } catch (\Throwable $th) {
            return $this->error('Failed to update cycle: ' . $th->getMessage());
        }
    }    


    public function sacco_members_review(Request $r)
    {

        $member = User::find($r->member_id);
        if ($member == null) {
            return $this->error('Member not found.');
        }
        $member->sacco_join_status = $r->sacco_join_status;
        $member->save();
        return $this->success(
            $member,
            $message = "Success",
            200
        );
    }

    public function gardens(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }

        $gardens = [];
        if ($u->isRole('agent')) {
            $gardens = Garden::where([])
                ->limit(1000)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $gardens = Garden::where(['user_id' => $u->id])
                ->limit(1000)
                ->orderBy('id', 'desc')
                ->get();
        }

        return $this->success(
            $gardens,
            $message = "Success",
            200
        );
    }



    public function people(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }

        return $this->success(
            Person::where(['administrator_id' => $u->id])
                ->limit(100)
                ->orderBy('id', 'desc')
                ->get(),
            $message = "Success",
            200
        );
    }
    public function jobs(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }

        return $this->success(
            Job::where([])
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get(),
            $message = "Success",
        );
    }


    public function activity_submit(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        if (
            $r->activity_id == null ||
            $r->farmer_activity_status == null ||
            $r->farmer_comment == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }

        $activity = GardenActivity::find($r->activity_id);

        if ($activity == null) {
            return $this->error('Activity not found.');
        }

        $image = "";
        if (!empty($_FILES)) {
            try {
                $image = Utils::upload_images_2($_FILES, true);
                $image = 'images/' . $image;
            } catch (Throwable $t) {
                $image = "no_image.jpg";
            }
        }

        $activity->photo = $image;
        $activity->farmer_activity_status = $r->farmer_activity_status;
        $activity->farmer_comment = $r->farmer_comment;
        if ($r->activity_date_done != null && strlen($r->activity_date_done) > 2) {
            $activity->activity_date_done = Carbon::parse($r->activity_date_done);
            $activity->farmer_submission_date = Carbon::now();
            $activity->farmer_has_submitted = 'Yes';
        }



        try {
            $activity->save();
            return $this->success(null, $message = "Success created!", 200);
        } catch (\Throwable $th) {
            return $this->error('Failed to save activity, because ' . $th->getMessage() . '');
        }
    }

    public function garden_create(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        if (
            $r->name == null ||
            $r->planting_date == null ||
            $r->crop_id == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }


        $image = "";
        if (!empty($_FILES)) {
            try {
                $image = Utils::upload_images_2($_FILES, true);
                $image = 'images/' . $image;
            } catch (Throwable $t) {
                $image = "no_image.jpg";
            }
        }

        $obj = new Garden();
        $obj->name = $r->name;
        $obj->user_id = $u->id;
        $obj->status = $r->status;
        $obj->production_scale = $r->production_scale;
        $obj->planting_date = Carbon::parse($r->planting_date);
        $obj->land_occupied = $r->planting_date;
        $obj->crop_id = $r->crop_id;
        $obj->details = $r->details;
        $obj->photo = $image;
        $obj->save();


        return $this->success(null, $message = "Success created!", 200);
    }

    public function product_create(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        if (
            $r->name == null ||
            $r->category == null ||
            $r->price == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }

        $image = "";
        if (!empty($_FILES)) {
            try {
                $image = Utils::upload_images_2($_FILES, true);
                $image = 'images/' . $image;
            } catch (Throwable $t) {
                $image = "no_image.jpg";
            }
        }




        $obj = new Product();
        $obj->name = $r->name;
        $obj->administrator_id = $u->id;
        $obj->type = $r->category;
        $obj->details = $r->details;
        $obj->price = $r->price;
        $obj->offer_type = $r->offer_type;
        $obj->state = $r->state;
        $obj->district_id = $r->district_id;
        $obj->subcounty_id = 1;
        $obj->photo = $image;

        try {
            $obj->save();
            return $this->success(null, $message = "Product Uploaded Success!", 200);
        } catch (\Throwable $th) {
            return $this->error('Failed to save product, because ' . $th->getMessage() . '');
            //throw $th;
        }
    }

    public function person_create(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        if (
            $r->name == null ||
            $r->sex == null ||
            $r->subcounty_id == null
        ) {
            return $this->error('Some Information is still missing. Fill the missing information and try again.');
        }

        $image = "";
        if (!empty($_FILES)) {
            try {
                $image = Utils::upload_images_2($_FILES, true);
                $image = 'images/' . $image;
            } catch (Throwable $t) {
                $image = "no_image.jpg";
            }
        }

        $obj = new Person();
        $obj->id = $r->id;
        $obj->created_at = $r->created_at;
        $obj->association_id = $r->association_id;
        $obj->administrator_id = $u->id;
        $obj->group_id = $r->group_id;
        $obj->name = $r->name;
        $obj->address = $r->address;
        $obj->parish = $r->parish;
        $obj->village = $r->village;
        $obj->phone_number = $r->phone_number;
        $obj->email = $r->email;
        $obj->district_id = $r->district_id;
        $obj->subcounty_id = $r->subcounty_id;
        $obj->disability_id = $r->disability_id;
        $obj->phone_number_2 = $r->phone_number_2;
        $obj->dob = $r->dob;
        $obj->sex = $r->sex;
        $obj->education_level = $r->education_level;
        $obj->employment_status = $r->employment_status;
        $obj->has_caregiver = $r->has_caregiver;
        $obj->caregiver_name = $r->caregiver_name;
        $obj->caregiver_sex = $r->caregiver_sex;
        $obj->caregiver_phone_number = $r->caregiver_phone_number;
        $obj->caregiver_age = $r->caregiver_age;
        $obj->caregiver_relationship = $r->caregiver_relationship;
        $obj->photo = $image;
        $obj->save();


        return $this->success(null, $message = "Success registered!", 200);
    }

    public function groups()
    {
        return $this->success(Group::get_groups(), 'Success');
    }

    public function associations()
    {
        return $this->success(Association::where([])->orderby('id', 'desc')->get(), 'Success');
    }

    public function institutions()
    {
        return $this->success(Institution::where([])->orderby('id', 'desc')->get(), 'Success');
    }
    public function service_providers()
    {
        return $this->success(ServiceProvider::where([])->orderby('id', 'desc')->get(), 'Success');
    }
    public function counselling_centres()
    {
        return $this->success(CounsellingCentre::where([])->orderby('id', 'desc')->get(), 'Success');
    }
    public function products()
    {
        return $this->success(Product::where([])->orderby('id', 'desc')->get(), 'Success');
    }
    public function events()
    {
        return $this->success(Event::where([])->orderby('id', 'desc')->get(), 'Success');
    }
    public function news_posts()
    {
        return $this->success(NewsPost::where([])->orderby('id', 'desc')->get(), 'Success');
    }


    public function index(Request $r, $model)
    {

        $className = "App\Models\\" . $model;
        $obj = new $className;

        if (isset($_POST['_method'])) {
            unset($_POST['_method']);
        }
        if (isset($_GET['_method'])) {
            unset($_GET['_method']);
        }

        $conditions = [];
        foreach ($_GET as $k => $v) {
            if (substr($k, 0, 2) == 'q_') {
                $conditions[substr($k, 2, strlen($k))] = trim($v);
            }
        }
        $is_private = true;
        if (isset($_GET['is_not_private'])) {
            $is_not_private = ((int)($_GET['is_not_private']));
            if ($is_not_private == 1) {
                $is_private = false;
            }
        }
        if ($is_private) {

            $u = auth('api')->user();
            $administrator_id = $u->id;

            if ($u == null) {
                return $this->error('User not found.');
            }
            $conditions['administrator_id'] = $administrator_id;
        }

        $items = [];
        $msg = "";

        try {
            $items = $className::where($conditions)->get();
            $msg = "Success";
            $success = true;
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage();
        }

        if ($success) {
            return $this->success($items, 'Success');
        } else {
            return $this->error($msg);
        }
    }





    public function delete(Request $r, $model)
    {
        $administrator_id = Utils::get_user_id($r);
        $u = User::find($administrator_id);


        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'message' => "User not found.",
            ]);
        }


        $className = "App\Models\\" . $model;
        $id = ((int)($r->online_id));
        $obj = $className::find($id);


        if ($obj == null) {
            return Utils::response([
                'status' => 0,
                'message' => "Item already deleted.",
            ]);
        }


        try {
            $obj->delete();
            $msg = "Deleted successfully.";
            $success = true;
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage();
        }


        if ($success) {
            return Utils::response([
                'status' => 1,
                'data' => $obj,
                'message' => $msg
            ]);
        } else {
            return Utils::response([
                'status' => 0,
                'data' => null,
                'message' => $msg
            ]);
        }
    }


    public function update(Request $r, $model)
    {
        $administrator_id = Utils::get_user_id($r);
        $u = User::find($administrator_id);


        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'message' => "User not found.",
            ]);
        }


        $className = "App\Models\\" . $model;
        $id = ((int)($r->online_id));
        $obj = $className::find($id);


        if ($obj == null) {
            return Utils::response([
                'status' => 0,
                'message' => "Item not found.",
            ]);
        }


        unset($_POST['_method']);
        if (isset($_POST['online_id'])) {
            unset($_POST['online_id']);
        }

        foreach ($_POST as $key => $value) {
            $obj->$key = $value;
        }


        $success = false;
        $msg = "";
        try {
            $obj->save();
            $msg = "Updated successfully.";
            $success = true;
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage();
        }


        if ($success) {
            return Utils::response([
                'status' => 1,
                'data' => $obj,
                'message' => $msg
            ]);
        } else {
            return Utils::response([
                'status' => 0,
                'data' => null,
                'message' => $msg
            ]);
        }
    }

    public function getSaccoData($id)
    {
        try {
            $saccoData = Sacco::where('id', $id)->first();

            if (!$saccoData) {
                return response()->json(['error' => 'Sacco data not found for the provided ID'], 404);
            }

            return response()->json($saccoData, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sacco data: ' . $e->getMessage()], 500);
        }
    }

    public function getMemberDetailsByCycle(Request $request)
{
    try {
        $user = auth('api')->user();

        if ($user === null) {
            return $this->error('User not found.');
        }

        $sacco = Sacco::where('id', $user->sacco_id)->first();

        if ($sacco === null) {
            return $this->error('Sacco not found.');
        }

        $cycles = Cycle::where('sacco_id', $sacco->id)->get();

        $memberDetails = [];

        foreach ($cycles as $cycle) {
            $members = User::where('sacco_id', $sacco->id)->get();
            foreach ($members as $member) {
                $memberData = [
                    'cycle_id' => $cycle->id,
                    'user_id' => $member->id,
                    'name' => $member->name,
                    'shares' => ShareRecord::where('user_id', $member->id)
                        ->where('cycle_id', $cycle->id)
                        ->sum('total_amount') /($sacco->share_price),
                    'loans' => Transaction::where('user_id', $member->id)
                                               ->where('cycle_id', $cycle->id)
                                               ->where('type', 'LOAN')
                                               ->sum('amount'),
                    'loan_repayments' => Transaction::where('user_id', $member->id)
                                                         ->where('cycle_id', $cycle->id)
                                                         ->where('type', 'LOAN_REPAYMENT')
                                                         ->sum('amount'),
                    'fines' => Transaction::where('user_id', $member->id)
                                          ->where('cycle_id', $cycle->id)
                                          ->where('type', 'FINE')
                                          ->sum('amount'),
                    'share_out_money' => Shareout::where('member_id', $member->id)
                                          ->where('cycle_id', $cycle->id)
                                          ->sum('shareout_amount'),
            
                ];

                $memberDetails[] = $memberData;
            }
        }

        return $this->success($memberDetails, 'Member details fetched successfully.', 200);
    } catch (Exception $e) {
        return $this->error($e->getMessage(), $e->getCode());
    } catch (Throwable $e) {
        return $this->error('Something went wrong.', 500);
    }
}


    public function agent_saccos($saccoIds)
{
    $villageAgent = auth('village_agents')->user();

    if ($villageAgent == null) {
        return $this->error('Village agent not found.');
    }

    // Convert the comma-separated string to an array of integers
    $saccoIdsArray = explode(',', $saccoIds);
    $saccoIdsArray = array_map('intval', $saccoIdsArray);

    if (empty($saccoIdsArray)) {
        return $this->error('No Sacco IDs provided.');
    }

    $saccos = Sacco::whereIn('id', $saccoIdsArray)->get();

    // Return the response
    return $this->success(
        $saccos,
        $message = "Saccos fetched successfully.",
        $statusCode = 200
    );
}

//     public function agent_saccos(Request $request)
// {
//     $villageAgent = auth('village_agents')->user();

//     if ($villageAgent == null) {
//         return $this->error('Village agent not found.');
//     }

//     // Extract the list of Sacco IDs from the request query parameters
//     $saccoIds = $request->input('sacco_ids', []);

//     if (empty($saccoIds)) {
//         return $this->error('No Sacco IDs provided.');
//     }

//     $saccos = Sacco::whereIn('id', $saccoIds)->get();

//     // Return the response
//     return $this->success(
//         $saccos,
//         $message = "Saccos fetched successfully.",
//         $statusCode = 200
//     );
// }

}
