<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierInvoiceFactory> */
    use HasFactory;

    protected $fillable = ['supplier_id', 'invoice_date', 'total_amount', 'invoice_number', 'notes'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }
}
