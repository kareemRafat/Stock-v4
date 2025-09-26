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
        Schema::create('outsourced_productions', function (Blueprint $table) {
            $table->id();
            $table->string('product_name'); // اسم المنتج
            $table->string('factory_name'); // اسم المصنع
            $table->integer('quantity'); // الكمية المطلوبة
            $table->string('size')->nullable(); // المقاس
            $table->decimal('total_cost', 10, 2)->nullable(); // التكلفة الكلية
            $table->date('start_date')->nullable(); // تاريخ بدء التصنيع
            $table->date('actual_delivery_date')->nullable(); // تاريخ التسليم الفعلي
            $table->enum('status', ['in_progress', 'completed', 'canceled'])->default('in_progress'); // حالة الطلب
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outsourced_productions');
    }
};
