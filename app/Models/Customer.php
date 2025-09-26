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

    public function getBalanceAttribute()
    {
        // use query fore better performance
        // calculate the balance based on the wallet transactions
        return $this->wallet()
            ->selectRaw("
                        SUM(
                            CASE
                                WHEN type = 'debit' THEN -amount
                                WHEN type = 'invoice' THEN -amount
                                WHEN type = 'credit' THEN amount
                                ELSE 0
                            END
                        ) as balance
                    ")
            ->value('balance') ?? 0;
    }
}
