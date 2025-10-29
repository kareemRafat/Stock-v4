<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'status'
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
     * @param mixed $beforeDate التاريخ المراد حساب الرصيد قبله (اختياري)
     * @return float الرصيد (موجب = مديونية، سالب = رصيد دائن)
     */
    public function calculateBalance($beforeDate = null)
    {
        $query = $this->wallet();

        if ($beforeDate) {
            // استخدام where مباشرة
            $query->where('created_at', '<', $beforeDate);
        }

        // المدين (على العميل) - الفواتير
        $debits = $query->clone()
            ->where('type', 'sale')
            ->sum('amount');

        // الدائن (من العميل) - المدفوعات والمرتجعات
        $credits = $query->clone()
            ->whereIn('type', ['payment', 'sale_return', 'adjustment'])
            ->sum('amount');

        // الرصيد = المدين - الدائن
        return $debits - $credits;
    }

    /**
     * الحصول على الرصيد الدائن المتاح فقط
     * @param mixed $beforeDate التاريخ المراد حساب الرصيد قبله
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
     * Accessor للرصيد الحالي
     */
    public function getBalanceAttribute()
    {
        return $this->calculateBalance();
    }
}
