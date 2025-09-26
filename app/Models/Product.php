<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount',
        'stock_quantity',
        'unit',
        'production_price',
        'type',
        'supplier_id',
    ];

    protected $casts = [
        'production_price' => 'decimal:2',
        'price' => 'decimal:2',
        'discount' => 'integer',
        'stock_quantity' => 'integer',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(ProductPurchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * حساب متوسط سعر التكلفة
     */
    public function getAverageCostAttribute(): float
    {
        $totalQuantity = $this->purchases()->sum('quantity');
        $totalCost = $this->purchases()->sum('total_cost');

        if ($totalQuantity > 0) {
            return round($totalCost / $totalQuantity, 2);
        }

        return 0;
    }

    /**
     * إجمالي الكمية المشتراة
     */
    public function getTotalPurchasedQuantityAttribute(): int
    {
        return $this->purchases()->sum('quantity');
    }

    /**
     * إجمالي التكلفة المدفوعة
     */
    public function getTotalCostPaidAttribute(): float
    {
        return $this->purchases()->sum('total_cost');
    }

    /**
     * آخر سعر شراء
     */
    public function getLastPurchasePriceAttribute(): float
    {
        $lastPurchase = $this->purchases()->latest('purchase_date')->first();
        return $lastPurchase ? $lastPurchase->purchase_price : 0;
    }

    /**
     * آخر تاريخ شراء
     */
    public function getLastPurchaseDateAttribute(): ?string
    {
        $lastPurchase = $this->purchases()->latest('purchase_date')->first();
        return $lastPurchase ? $lastPurchase->purchase_date->format('Y-m-d') : null;
    }

    /**
     * قيمة المخزن الحالي (الكمية × متوسط التكلفة)
     */
    public function getCurrentStockValueAttribute(): float
    {
        return round($this->stock_quantity * $this->average_cost, 2);
    }


    /**
     * هامش الربح (%)
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->average_cost > 0) {
            return round((($this->final_price - $this->average_cost) / $this->average_cost) * 100, 2);
        }
        return 0;
    }

    /**
     * هل المنتج متوفر؟
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * حالة المخزون
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity == 0) {
            return 'نفذ';
        } elseif ($this->stock_quantity <= 10) { // يمكن تغيير الرقم حسب الحاجة
            return 'قليل';
        } else {
            return 'متوفر';
        }
    }

    // public function getFinalPriceAttribute()
    // {
    //     if ($this->discount > 0) {
    //         $final = $this->attributes['price'] - ($this->attributes['price'] * $this->discount / 100);

    //         return number_format($final, 2, '.', '');
    //     }

    //     return number_format($this->attributes['price'], 2, '.', '');
    // }

    /**
     * السعر بعد الخصم
     */
    public function getFinalPriceAttribute(): float
    {
        if ($this->discount > 0) {
            return round($this->price * (1 - $this->discount / 100), 2);
        }
        return $this->price;
    }

    /**
     * تحديث متوسط سعر التكلفة في حقل production_price
     */
    public function updateAverageCost(): void
    {
        $this->update([
            'production_price' => $this->average_cost
        ]);
    }

    /**
     * إضافة كمية للمخزن
     */
    public function addStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    /**
     * خصم كمية من المخزن
     */
    public function reduceStock(int $quantity): bool
    {
        if ($this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        return false;
    }
}
