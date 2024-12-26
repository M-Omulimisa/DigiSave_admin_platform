<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_loan_id',
        'amount_paid',
        'principal_paid',
        'interest_paid',
        'remaining_balance',
        'payment_method',
        'transaction_reference',
        'payment_proof',
        'notes',
        'received_by',
        'status',
        'payment_date'
    ];

    protected $dates = [
        'payment_date',
        'created_at',
        'updated_at'
    ];

    public function loan()
    {
        return $this->belongsTo(CreditLoan::class, 'credit_loan_id');
    }

    public function calculateRemainingBalance()
    {
        $totalPaid = $this->loan->repayments()
            ->where('status', 'confirmed')
            ->where('id', '<=', $this->id)
            ->sum('principal_paid');

        return $this->loan->loan_amount - $totalPaid;
    }

    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount_paid, 0, '.', ',');
    }

    public function getFormattedBalanceAttribute()
    {
        return 'UGX ' . number_format($this->remaining_balance, 0, '.', ',');
    }
}
