<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPurchase extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'quantity',
        'purchase_price',
        'total_cost',
        'purchase_date',
        'supplier_invoice_number',
        'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // العلاقات (Relationships)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Boot Method - للأحداث التلقائية
    protected static function boot()
    {
        parent::boot();

        // حساب التكلفة الإجمالية تلقائياً عند الإنشاء
        static::creating(function ($purchase) {
            $purchase->total_cost = $purchase->quantity * $purchase->purchase_price;
        });

        // حساب التكلفة الإجمالية تلقائياً عند التحديث
        static::updating(function ($purchase) {
            $purchase->total_cost = $purchase->quantity * $purchase->purchase_price;
        });

        // تحديث المخزن عند إضافة عملية شراء جديدة
        static::created(function ($purchase) {
            $purchase->product->addStock($purchase->quantity);
            // تحديث متوسط التكلفة (اختياري)
            $purchase->product->updateAverageCost();
        });

        // تحديث المخزن عند تعديل عملية شراء
        static::updated(function ($purchase) {
            $original = $purchase->getOriginal();
            $quantityDifference = $purchase->quantity - $original['quantity'];

            if ($quantityDifference != 0) {
                $purchase->product->addStock($quantityDifference);
                $purchase->product->updateAverageCost();
            }
        });

        // تحديث المخزن عند حذف عملية شراء
        static::deleted(function ($purchase) {
            $purchase->product->reduceStock($purchase->quantity);
            $purchase->product->updateAverageCost();
        });
    }

    // Accessors

    /**
     * سعر القطعة الواحدة
     */
    public function getUnitPriceAttribute(): float
    {
        return $this->purchase_price;
    }

    /**
     * تنسيق التاريخ
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->purchase_date->format('d/m/Y');
    }

    // Scopes

    /**
     * المشتريات في فترة معينة
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('purchase_date', [$startDate, $endDate]);
    }

    /**
     * المشتريات من مورد معين
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * المشتريات لمنتج معين
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

}
