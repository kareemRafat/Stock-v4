<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovements extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'movement_type',
        'reference_id',
        'reference_table',
        'qty_in',
        'qty_out',
        'cost_price',
        'sale_price',
    ];

    /**
     * المنتج المرتبط بالحركة
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * العملية المرتبطة (فاتورة مشتريات / مبيعات / مرتجع ...)
     * باستخدام Polymorphic relation
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(null, 'reference_table', 'reference_id');
    }

    /**
     * هل الحركة دخول مخزون؟
     */
    public function isIn(): bool
    {
        return $this->qty_in > 0;
    }

    /**
     * هل الحركة خروج مخزون؟
     */
    public function isOut(): bool
    {
        return $this->qty_out > 0;
    }

    /**
     * الرصيد (الفرق بين الدخول والخروج)
     */
    public function getNetQuantityAttribute(): float
    {
        return $this->qty_in - $this->qty_out;
    }
}
