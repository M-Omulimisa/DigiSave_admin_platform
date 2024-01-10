<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            include_once(app_path() . '/Models/Utils.php');

            if (!in_array($model->type, TRANSACTION_TYPES)) {
                throw new Exception("Invalid transaction type.");
            }

            $user = Administrator::find($model->user_id);
            if ($user == null) {
                throw new Exception("User not found");
            }
            //get active cycle
            $cycle = Cycle::where('sacco_id', $user->sacco_id)->where('status', 'Active')->first();
            if ($cycle == null) {
                throw new Exception("No active cycle found");
            }

            
            $model->cycle_id = $cycle->id;
            $model->sacco_id = $user->sacco_id;
            return $model;
        });

        //updating
        static::updating(function ($model) {
            if (!in_array($model->type, TRANSACTION_TYPES)) {
                throw new Exception("Invalid transaction type.");
            }
        });

        //creatd
        static::created(function ($model) {
            $model->balance = Transaction::where('user_id', $model->user_id)->sum('amount');
            $model->save();
            return $model;
        });
    }

    public static function send_money($sender_id, $receiver_id, $amount, $description, $password)
    {
        $amount = abs($amount);
        $sender = User::find($sender_id);
        if ($sender == null) {
            throw new Exception("Sender not found");
        }
        if ($sender->balance < $amount) {
            throw new Exception("Insufficient balance");
        }
        if (!password_verify($password, $sender->password)) {
            throw new Exception("Invalid password");
        }
        $receiver = User::find($receiver_id);
        if ($receiver == null) {
            throw new Exception("Receiver not found");
        }

        if ($sender->id == $receiver->id) {
            throw new Exception("You cannot send money to yourself. " . $sender->id . "==" . $receiver->id);
        }

        $sender_transactions = new Transaction();
        $sender_transactions->user_id = $sender->id;
        $sender_transactions->source_user_id = $sender->id;
        $sender_transactions->sacco_id = $sender->sacco_id;
        $sender_transactions->type = 'Send';
        $sender_transactions->source_type = 'Transfer';
        $sender_transactions->source_mobile_money_number = $sender->phone_number;
        $sender_transactions->source_mobile_money_transaction_id = null;
        $sender_transactions->source_bank_account_number = null;
        $sender_transactions->source_bank_transaction_id = null;
        $sender_transactions->desination_bank_account_number = null;
        $sender_transactions->desination_bank_transaction_id = null;
        $sender_transactions->desination_mobile_money_transaction_id = null;
        $sender_transactions->desination_type = 'Transfer';
        $sender_transactions->desination_mobile_money_number = $receiver->phone_number;
        $sender_transactions->amount = (-1 * $amount);
        $sender_transactions->description = "Transfered UGX " . number_format($amount) . " to {$receiver->phone_number} - $receiver->name.";
        $sender_transactions->details = $description;
        try {
            $sender_transactions->save();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
        $receiver_transactions = new Transaction();
        $receiver_transactions->user_id = $receiver->id;
        $receiver_transactions->source_user_id = $sender->id;
        $receiver_transactions->sacco_id = $receiver->sacco_id;
        $receiver_transactions->type = 'Receive';
        $receiver_transactions->source_type = 'Transfer';
        $receiver_transactions->source_mobile_money_number = $sender->phone_number;
        $receiver_transactions->source_mobile_money_transaction_id = null;
        $receiver_transactions->source_bank_account_number = null;
        $receiver_transactions->source_bank_transaction_id = null;
        $receiver_transactions->desination_bank_account_number = null;
        $receiver_transactions->desination_bank_transaction_id = null;
        $receiver_transactions->desination_mobile_money_transaction_id = null;
        $receiver_transactions->desination_type = 'Transfer';
        $receiver_transactions->desination_mobile_money_number = $receiver->phone_number;
        $receiver_transactions->amount = $amount;
        $receiver_transactions->description = "Received UGX " . number_format($amount) . " from {$sender->phone_number} - $sender->name.";
        $receiver_transactions->details = $description;

        try {
            $receiver_transactions->save();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
        return true;
    }
}
