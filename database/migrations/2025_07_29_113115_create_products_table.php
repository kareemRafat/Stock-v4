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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['جملة', 'قطاعي'])->default('جملة');
            $table->decimal('production_price', 10, 2)->comment('سعر المصنع');
            $table->decimal('price', 10, 2)->comment('سعر البيع');
            $table->unsignedTinyInteger('discount')->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null')->comment('المورد');
            $table->string('unit')->nullable();
            // unit وحدة القياس (كرتونة - قطعة - كيلو إلخ)
            $table->timestamps();

            // for performance
            $table->index(['name', 'type']);
            $table->index('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
