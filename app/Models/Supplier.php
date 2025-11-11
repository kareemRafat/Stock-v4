<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // حساب الرصيد
    public function getBalanceAttribute()
    {
        // check if exists
        if (isset($this->attributes['balance'])) {
            return $this->attributes['balance'];
        }

        // لو لا، احسبه وخزنه
        $balance = $this->wallet()
            ->selectRaw('
        SUM(CASE
            WHEN type IN ("purchase", "adjustment") THEN amount
            WHEN type IN ("payment", "purchase_return", "debt_payment") THEN -amount
            ELSE 0
        END) as balance
    ')
            ->value('balance') ?? 0;

        // خزن القيمة عشان ما تحسبهاش تاني
        $this->attributes['balance'] = $balance;

        return $balance;
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
}
