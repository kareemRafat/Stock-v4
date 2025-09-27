<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('test', function () {
    $product = Product::find(219);
    echo "<pre>";
    echo "=== تشخيص المشكلة ===\n";
    echo "المخزون الحالي: " . $product->stock_quantity . "\n";
    echo "المشتريات المسجلة: " . $product->purchases()->sum('quantity') . "\n";
    echo "تكلفة المشتريات المسجلة: " . $product->purchases()->sum('total_cost') . "\n";
    echo "سعر الإنتاج (production_price): " . $product->production_price . "\n";

    $recordedQuantity = $product->purchases()->sum('quantity');
    echo "\n=== الشرط ===\n";
    echo "المخزون ({$product->stock_quantity}) > المشتريات ({$recordedQuantity})؟ ";
    echo ($product->stock_quantity > $recordedQuantity) ? "نعم" : "لا";

    if ($product->stock_quantity > $recordedQuantity) {
        $oldStock = $product->stock_quantity - $recordedQuantity;
        $oldStockCost = $oldStock * $product->production_price;
        $newStockCost = $product->purchases()->sum('total_cost');
        $totalCost = $oldStockCost + $newStockCost;

        echo "\n=== الحساب اليدوي ===\n";
        echo "المخزون القديم: {$oldStock} قطعة\n";
        echo "تكلفة المخزون القديم: {$oldStock} × {$product->production_price} = {$oldStockCost}\n";
        echo "تكلفة المشتريات الجديدة: {$newStockCost}\n";
        echo "إجمالي التكلفة: {$oldStockCost} + {$newStockCost} = {$totalCost}\n";
        echo "المتوسط المحسوب: {$totalCost} ÷ {$product->stock_quantity} = " . ($totalCost / $product->stock_quantity) . "\n";
    }

    echo "\n=== النتيجة من الـ Attribute ===\n";
    echo "متوسط التكلفة: " . $product->average_cost . "\n";
});
