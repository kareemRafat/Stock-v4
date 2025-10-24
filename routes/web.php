<?php

use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


Route::get('/monthly-profit', function () {
    $month = now()->month;
    $year = now()->year;

    // الربح الإجمالي = مبيعات - مرتجعات
    $grossProfit = StockMovement::whereIn('movement_type', ['invoice_sale', 'sale_return'])
        ->whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->sum(DB::raw("
            CASE
                WHEN movement_type = 'invoice_sale'
                    THEN (COALESCE(wholesale_price, retail_price) - cost_price) * qty_out
                WHEN movement_type = 'sale_return'
                    THEN -(COALESCE(wholesale_price, retail_price) - cost_price) * qty_in
                ELSE 0
            END
        "));

    // الخصومات من جدول الفواتير
    $totalDiscounts = Invoice::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->sum('discount_amount');

    // الربح الصافي بعد الخصم
    $netProfit = $grossProfit - $totalDiscounts;

    return response()->json([
        'month' => now()->format('F Y'),
        'gross_profit from stock movement table' => round($grossProfit, 2),
        'total_discounts' => round($totalDiscounts, 2),
        'net_profit' => round($netProfit, 2),
    ]);
});

