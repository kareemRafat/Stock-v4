<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerWallet extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerWalletFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'invoice_id',
        'invoice_number',
        'return_invoice_id',
        'notes',
        'created_at',

    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function createdDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : null,
        );
    }

    protected function createdTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i a') : null,
        );
    }
}
