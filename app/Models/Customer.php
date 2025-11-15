<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'phone',
        'phone2',
        'address',
        'city',
        'governorate',
        'status',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function wallet()
    {
        return $this->hasMany(CustomerWallet::class);
    }

    /**
     * حساب الرصيد الإجمالي للعميل
     *
     * @param  mixed  $beforeDate  التاريخ المراد حساب الرصيد قبله (اختياري)
     * @return float الرصيد (موجب = مديونية، سالب = رصيد دائن)
     */
    public function calculateBalance($beforeDate = null)
    {
        static $cache = [];

        $cacheKey = $this->id . '_' . ($beforeDate ? (string) $beforeDate : 'current');

        if (! isset($cache[$cacheKey])) {
            $query = $this->wallet();

            if ($beforeDate) {
                $query->where('created_at', '<', $beforeDate);
            }

            $cache[$cacheKey] = $query
                ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN type = "sale" THEN amount
                    WHEN type IN ("payment", "sale_return", "adjustment") THEN -amount
                    ELSE 0
                END), 0) as balance
            ')
                ->value('balance') ?? 0;
        }

        return $cache[$cacheKey];
    }

    /**
     * الحصول على الرصيد الدائن المتاح فقط
     *
     * @param  mixed  $beforeDate  التاريخ المراد حساب الرصيد قبله
     * @return float القيمة الموجبة للرصيد الدائن أو 0
     */
    public function getAvailableCreditBalance($beforeDate = null)
    {
        $balance = $this->calculateBalance($beforeDate);

        // إذا كان الرصيد سالب (دائن)، نرجع القيمة المطلقة
        // إذا كان موجب (مدين)، نرجع 0
        return $balance < 0 ? abs($balance) : 0;
    }

    /**
     * حساب المديونية على العميل فقط
     */
    public function getDebtAmount($beforeDate = null)
    {
        $balance = $this->calculateBalance($beforeDate);
        return $balance > 0 ? $balance : 0; // مديونية
    }

    /**
     * Accessor للرصيد الحالي
     */
    public function getBalanceAttribute()
    {
        return $this->calculateBalance();
    }
}
