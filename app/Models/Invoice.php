<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total_amount',
        'notes',
        'status',
        'special_discount',
        'price_type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'special_discount' => 'decimal:2',
    ];

    public function returnInvoices(): HasMany
    {
        return $this->hasMany(ReturnInvoice::class, 'original_invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function returns()
    {
        return $this->hasMany(\App\Models\ReturnInvoice::class, 'original_invoice_id');
    }

    /**
     * حساب المبلغ المطلوب سداده نقداً
     * بعد خصم: (الخصم الخاص + المرتجعات + رصيد العميل المتاح)
     */
    public function getAmountDueAttribute(): float
    {
        // استخدام relationLoaded
        $subtotal = $this->relationLoaded('items')
            ? $this->items->sum('subtotal')
            : $this->items()->sum('subtotal');

        $afterSpecialDiscount = $subtotal - $this->special_discount;
        $totalReturns = $this->calculateReturnsWithDiscount();
        $afterReturns = $afterSpecialDiscount - $totalReturns;

        // استخدام relationLoaded للعميل
        $availableCredit = $this->relationLoaded('customer')
            ? $this->customer->getAvailableCreditBalance($this->created_at)
            : ($this->customer->getAvailableCreditBalance($this->created_at) ?? 0);

        $amountDue = $afterReturns - $availableCredit;

        return max(0, $amountDue);
    }

    /**
     * حساب قيمة المرتجعات مع تطبيق نسبة الخصم الخاص
     */
    public function calculateReturnsWithDiscount(): float
    {
        // استخدام query واحد بدل loop
        $returnsTotal = \App\Models\ReturnInvoiceItem::query()
            ->whereHas('returnInvoice', function ($q) {
                $q->where('original_invoice_id', $this->id);
            })
            ->selectRaw('SUM(price * quantity_returned) as total')
            ->value('total') ?? 0;

        if ($returnsTotal == 0) {
            return 0;
        }

        // استخدام relationLoaded للتحقق من الـ eager loading
        $subtotal = $this->relationLoaded('items')
            ? $this->items->sum('subtotal')  // من الـ memory
            : $this->items()->sum('subtotal'); // query جديد

        $specialDiscountRatio = $subtotal > 0
            ? ($this->special_discount / $subtotal)
            : 0;

        return $returnsTotal * (1 - $specialDiscountRatio);
    }

    /**
     * الحصول على تفاصيل الحساب (للعرض)
     */
    public function getPaymentBreakdown(): array
    {
        $subtotal = $this->items()->sum('subtotal');
        $specialDiscount = $this->special_discount;
        $afterSpecialDiscount = $subtotal - $specialDiscount;
        $totalReturns = $this->calculateReturnsWithDiscount();
        $afterReturns = $afterSpecialDiscount - $totalReturns;
        $availableCredit = $this->customer->getAvailableCreditBalance($this->created_at) ?? 0;
        $amountDue = max(0, $afterReturns - $availableCredit);

        return [
            'subtotal' => $subtotal,
            'special_discount' => $specialDiscount,
            'after_special_discount' => $afterSpecialDiscount,
            'total_returns' => $totalReturns,
            'after_returns' => $afterReturns,
            'available_credit' => $availableCredit,
            'amount_due' => $amountDue,
        ];
    }

    // ////////////////////

    protected static function booted(): void
    {
        static::saved(function ($invoice) {
            $total = $invoice->items()->sum('subtotal');
            $invoice->updateQuietly([
                'total_amount' => $total,
            ]);
        });
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = self::generateUniqueInvoiceNumber();
            }
        });
    }

    public static function generateUniqueInvoiceNumber(): string
    {
        $prefix = 'INV-';

        for ($i = 0; $i < 5; $i++) {
            $number = $prefix.str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (! self::where('invoice_number', $number)->exists()) {
                return $number;
            }
        }

        // Fallback to a timestamp if all attempts fail
        return $prefix.now()->format('ymdHis');
    }

    public function getTotalAmountAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }

    protected function createdDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : null,
        );
    }

    protected function createdTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i a') : null,
        );
    }

    protected function createdTime12Hour(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i A') : null,
        );
    }

    public function hasReturns(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->returnInvoices->isNotEmpty(), // Uses eager loaded data
        );
    }

    public function returnsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->returnInvoices()->count(),
        );
    }

    public function hasReturnableItems()
    {
        static $cache = [];

        if (array_key_exists($this->id, $cache)) {
            return $cache[$this->id];
        }

        // Efficient database query
        $result = DB::table('invoice_items')
            ->where('invoice_id', $this->id)
            ->whereRaw('quantity > COALESCE((
            SELECT SUM(rii.quantity_returned)
            FROM return_invoice_items rii
            INNER JOIN return_invoices ri ON ri.id = rii.return_invoice_id
            WHERE ri.original_invoice_id = invoice_items.invoice_id
            AND rii.product_id = invoice_items.product_id
        ), 0)')
            ->exists();

        return $cache[$this->id] = $result;
    }
}
