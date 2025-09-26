<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateUniqueInvoiceNumber();
            }
        });
    }

    public static function generateUniqueInvoiceNumber(): string
    {
        $prefix = 'INV-';

        for ($i = 0; $i < 5; $i++) {
            $number = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (!self::where('invoice_number', $number)->exists()) {
                return $number;
            }
        }

        // Fallback to a timestamp if all attempts fail
        return $prefix . now()->format('ymdHis');
    }

    public function getTotalAmountAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d h:i:s A') : null,
        );
    }

    protected function createdDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : null,
        );
    }

    protected function createdTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i a') : null,
        );
    }

    protected function createdTime12Hour(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i A') : null,
        );
    }

    public function hasReturns(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->returnInvoices->isNotEmpty(), // Uses eager loaded data
        );
    }

    public function returnsCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->returnInvoices()->count(),
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
