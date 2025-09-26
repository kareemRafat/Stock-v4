<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierInvoiceItemFactory> */
    use HasFactory;

    protected $fillable = ['supplier_invoice_id', 'product_id', 'quantity', 'price', 'subtotal'];

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
