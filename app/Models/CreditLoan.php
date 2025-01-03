<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLoan extends Model
{
    use HasFactory;

    // Define the table name (optional)
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
        'disbursement_status',
        'disbursed_at',
        'disbursement_reference'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'disbursed_at'
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

    // Get formatted payment details
    public function getPaymentDetailsAttribute()
    {
        switch ($this->selected_method) {
            case 'bank':
                return "Bank: {$this->selected_bank}\nAccount: {$this->account_number}\nName: {$this->account_name}";
            case 'airtel':
            case 'mtn':
                return "Phone: {$this->phone_number}\nName: {$this->account_name}";
            default:
                return 'No payment details available';
        }
    }

    public function repayments()
{
    return $this->hasMany(LoanRepayment::class, 'credit_loan_id');
}

public function getTotalPaidAttribute()
{
    return $this->repayments()
        ->where('status', 'confirmed')
        ->sum('amount_paid');
}

public function getRemainingBalanceAttribute()
{
    $totalPaid = $this->repayments()
        ->where('status', 'confirmed')
        ->sum('principal_paid');

    return $this->loan_amount - $totalPaid;
}

public function getIsFullyPaidAttribute()
{
    return $this->remaining_balance <= 0;
}

public function getPaymentProgressAttribute()
{
    if ($this->loan_amount > 0) {
        $progress = ($this->total_paid / ($this->loan_amount + $this->total_interest)) * 100;
        return round($progress, 2);
    }
    return 0;
}
}
