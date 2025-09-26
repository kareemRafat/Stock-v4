<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address'];

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function wallet()
    {
        return $this->hasMany(SupplierWallet::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ProductPurchase::class);
    }


    public function getBalanceAttribute()
    {
        // use query fore better performance
        // calculate the balance based on the wallet transactions
        return $this->wallet()
            ->selectRaw("
                        SUM(
                            CASE
                                WHEN type = 'debit' THEN -amount
                                WHEN type = 'invoice' THEN -amount
                                WHEN type = 'credit' THEN amount
                                ELSE 0
                            END
                        ) as balance
                    ")
            ->value('balance') ?? 0;
    }

    /**
     * إجمالي المشتريات من هذا المورد
     */
    public function getTotalPurchasesValueAttribute(): float
    {
        return $this->purchases()->sum('total_cost');
    }

    /**
     * عدد عمليات الشراء من هذا المورد
     */
    public function getTotalPurchasesCountAttribute(): int
    {
        return $this->purchases()->count();
    }

    /**
     * آخر عملية شراء
     */
    public function getLastPurchaseDateAttribute(): ?string
    {
        $lastPurchase = $this->purchases()->latest('purchase_date')->first();
        return $lastPurchase ? $lastPurchase->purchase_date->format('Y-m-d') : null;
    }

    /**
     * متوسط قيمة عمليات الشراء
     */
    public function getAveragePurchaseValueAttribute(): float
    {
        $count = $this->total_purchases_count;
        return $count > 0 ? round($this->total_purchases_value / $count, 2) : 0;
    }

    /**
     * البحث في الموردين
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
