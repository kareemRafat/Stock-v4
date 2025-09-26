<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierWallet extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierWalletFactory> */
    use HasFactory;

    protected $fillable = ['supplier_id', 'type', 'amount', 'supplier_invoice_id', 'note' , 'created_at'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }
}
