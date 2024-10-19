<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLoan extends Model
{
    use HasFactory;

    // Define the table name (optional, Laravel automatically assumes the plural form of the model name)
    protected $table = 'credit_loans';

    // Define which attributes are mass assignable
    protected $fillable = [
        'sacco_id',
        'loan_amount',
        'loan_term',
        'total_interest',
        'monthly_payment',
        'loan_purpose',
        'billing_address',
        'selected_method',
        'selected_bank',
        'account_number',
        'account_name',
        'phone_number',
        'terms_accepted',
        'use_current_address',
        'loan_status',
    ];

    // Relationships
    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    // Method to approve a loan
    public function approveLoan()
    {
        if ($this->loan_status === 'pending') {
            $this->loan_status = 'approved';
            $this->save();
            return ['status' => 'success', 'message' => 'Loan has been approved.'];
        }

        return ['status' => 'error', 'message' => 'Loan cannot be approved as it is already processed.'];
    }

    // Method to reject a loan
    public function rejectLoan()
    {
        if ($this->loan_status === 'pending') {
            $this->loan_status = 'rejected';
            $this->save();
            return ['status' => 'success', 'message' => 'Loan has been rejected.'];
        }

        return ['status' => 'error', 'message' => 'Loan cannot be rejected as it is already processed.'];
    }

    // Method to check if the loan is still pending
    public function isPending()
    {
        return $this->loan_status === 'pending';
    }
}
