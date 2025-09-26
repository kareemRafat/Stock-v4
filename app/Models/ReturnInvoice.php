<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ReturnInvoice extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'return_invoice_number',
        'original_invoice_number',
        'original_invoice_id',
        'total_amount',
        'notes',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function originalInvoice()
    {
        return $this->belongsTo(Invoice::class, 'original_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(ReturnInvoiceItem::class);
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d h:i:s A') : null,
        );
    }

    protected function createdDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : null,
        );
    }

    protected function createdTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i a') : null,
        );
    }

    protected function createdTime12Hour(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i A') : null,
        );
    }
}
