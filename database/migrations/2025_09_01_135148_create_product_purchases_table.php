<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->comment('الكمية المشتراة');
            $table->decimal('purchase_price', 10, 2)->comment('سعر الشراء لهذه الدفعة');
            // التكلفة الإجمالية (quantity * purchase_price)
            $table->decimal('total_cost', 10, 2)->comment('التكلفة الإجمالية');
            $table->date('purchase_date')->comment('تاريخ الشراء');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('supplier_invoice_number')->nullable()->comment('رقم فاتورة المورد');
            $table->text('notes')->nullable()->comment('ملاحظات إضافية');
            $table->timestamps();

            // indexes for performance
            $table->index(['product_id', 'purchase_date']);
            $table->index(['supplier_id', 'purchase_date']);
            $table->index('purchase_date');
            $table->index('supplier_invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_purchases');
    }
};
