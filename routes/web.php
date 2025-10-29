<?php

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\CustomerWallet;
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


Route::get('/monthly-financial-report', function () {
    $month = now()->month;
    $year = now()->year;

    // 🧮 1. إجمالي الربح من عمليات البيع والمرتجعات (العملاء)
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

    // 🧾 2. مرتجعات الموردين (تزود الربح لأنها استرجاع تكلفة)
    $purchaseReturnsProfit = StockMovement::where('movement_type', 'purchase_return')
        ->whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->sum(DB::raw('cost_price * qty_in'));

    // ✅ إجمالي الربح قبل الخصم
    $grossProfitTotal = $grossProfit + $purchaseReturnsProfit;

    // 💰 3. إجمالي الخصومات على الفواتير
    $totalDiscounts = Invoice::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->sum('special_discount');

    // 💹 4. الربح الصافي بعد الخصومات
    $netProfit = $grossProfitTotal - $totalDiscounts;

    // 🏦 5. التدفق النقدي من العملاء (دفعات فعلية داخل الشهر)
    $totalCashIn = CustomerWallet::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->where('type', 'credit') // المدفوعات من العملاء
        ->sum('amount');

    // 📉 6. إجمالي الديون الناتجة عن فواتير لم تُسدَّد بالكامل
    $totalDebts = CustomerWallet::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->where('type', 'debit') // مديونيات
        ->sum('amount');

    return response()->json([
        'month' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
        'gross_profit' => round($grossProfitTotal, 2),
        'total_discounts' => round($totalDiscounts, 2),
        'net_profit' => round($netProfit, 2),
        'cash_in' => round($totalCashIn, 2),
        'debts' => round($totalDebts, 2),
        'summary' => [
            'final_balance' => round($netProfit + $totalCashIn - $totalDebts, 2),
        ],
    ]);
});



Route::get('debug-balance', function () {
    $customer = \App\Models\Customer::find(6); // غير الـ ID

    $sales = $customer->wallet()
        ->where('type', 'sale')
        ->get(['amount', 'invoice_number', 'created_at']);

    $payments = $customer->wallet()
        ->where('type', 'payment')
        ->get(['amount', 'invoice_number', 'created_at']);

    $creditUses = $customer->wallet()
        ->where('type', 'credit_use')
        ->get(['amount', 'invoice_number', 'created_at']);

    $debits = $customer->wallet()->where('type', 'sale')->sum('amount');
    $credits = $customer->wallet()->whereIn('type', ['payment', 'sale_return', 'adjustment'])->sum('amount');

    return [
        'sales' => $sales,
        'payments' => $payments,
        'credit_uses' => $creditUses,
        'total_debits' => $debits,
        'total_credits' => $credits,
        'balance' => $debits - $credits,
    ];
});


Route::get('test-balance', function () {
    $invoice = \App\Models\Invoice::find(1); // الفاتورة #1 (11:34 pm)
    $customer = $invoice->customer;

    // كل الحركات
    $allTransactions = $customer->wallet()
        ->orderBy('created_at')
        ->get(['id', 'type', 'amount', 'invoice_number', 'created_at']);

    // الحركات قبل الفاتورة
    $beforeInvoice = $customer->wallet()
        ->where('created_at', '<', $invoice->created_at->format('Y-m-d H:i:s'))
        ->get(['id', 'type', 'amount', 'invoice_number', 'created_at']);

    // حساب الرصيد
    $balance = $customer->calculateBalance($invoice->created_at);
    $availableCredit = $customer->getAvailableCreditBalance($invoice->created_at);

    return [
        'invoice_date' => $invoice->created_at->format('Y-m-d H:i:s'),
        'all_transactions' => $allTransactions,
        'before_invoice' => $beforeInvoice,
        'balance' => $balance,
        'available_credit' => $availableCredit,
    ];
});
