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

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('movement_type', [
                'opening_stock',
                'purchase',
                'sale',
                'purchase_return',
                'sale_return',
                'adjustment',
            ]);

            $table->unsignedBigInteger('reference_id')->nullable(); // ID للفاتورة / العملية
            $table->string('reference_table', 50)->nullable();       // اسم الجدول المرتبط

            $table->decimal('qty_in', 12, 2)->default(0);
            $table->decimal('qty_out', 12, 2)->default(0);

            $table->decimal('cost_price', 12, 2);   // تكلفة الشراء/الوحدة
            $table->decimal('sale_price', 12, 2)->nullable(); // سعر البيع وقت العملية

            $table->timestamps();
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
