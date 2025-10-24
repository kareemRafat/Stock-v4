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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // when delete product delete stock_movement for this product
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('movement_type', [
                'opening_stock', // products insert
                'purchase', // supplier invoices
                'invoice_sale', // customer invoice
                'purchase_return', // supplier Invoice Return
                'sale_return', // customers Return
                'adjustment', // manual update
            ]);

            // null when opening_stock
            $table->unsignedBigInteger('reference_id')->nullable(); // ID للفاتورة / العملية
            $table->string('reference_table', 50)->nullable();       // polymorphic relation

            $table->decimal('qty_in', 12, 2)->default(0);
            $table->decimal('qty_out', 12, 2)->default(0);

            $table->decimal('cost_price', 12, 2)->comment('تكلفة الشراء/الوحدة');
            $table->decimal('wholesale_price', 10, 2)->nullable()->comment('سعر الجملة');
            $table->decimal('retail_price', 12, 2)->nullable()->comment('سعر البيع وقت العملية');

            // add stock_before و stock_after
            $table->integer('stock_before')->nullable()->comment('الرصيد قبل العملية');
            $table->integer('stock_after')->default(0)->comment('الرصيد بعد العملية');

            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['reference_table', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
