<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierInvoiceItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['supplier_invoice_id', 'product_id', 'quantity', 'cost_price', 'wholesale_price', 'retail_price', 'subtotal'];

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
