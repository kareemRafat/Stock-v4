<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    public $fillable = [
        'name',
        'phone',
        'phone2',
        'address',
        'city',
        'governorate',
        'status'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function wallet()
    {
        return $this->hasMany(CustomerWallet::class);
    }

    /**
     * Get the current balance of the customer's wallet.
     * Positive value: Customer owes you (مديونية).
     * Negative value: You owe the customer (رصيد دائن).
     */
    public function getBalanceAttribute()
    {
        // use query fore better performance
        // calculate the balance based on the wallet transactions
        return $this->wallet()->sum('amount') ?? 0;
    }
}
