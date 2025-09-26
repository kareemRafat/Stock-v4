<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnInvoiceItem extends Model
{
    protected $fillable = [
        'return_invoice_id',
        'product_id',
        'quantity_returned',
        'price',
        'subtotal',
    ];

    public function returnInvoice()
    {
        return $this->belongsTo(ReturnInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
