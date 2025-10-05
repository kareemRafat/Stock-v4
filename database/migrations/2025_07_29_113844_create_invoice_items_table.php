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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('سعر المصنع وقت البيع');
            $table->decimal('wholesale_price', 10, 2)->nullable()->comment('سعر الجملة وقت البيع');
            $table->decimal('retail_price', 10, 2)->nullable()->comment('سعر القطاعي وقت البيع');
            $table->decimal('discount', 5, 2)->nullable()->comment('قيمة الخصم وقت البيع');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
