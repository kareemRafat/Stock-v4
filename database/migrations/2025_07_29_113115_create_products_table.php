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
            $table->decimal('cost_price', 10, 2)->comment('سعر المصنع');
            $table->decimal('wholesale_price', 10, 2)->nullable()->comment('سعر الجملة');
            $table->decimal('retail_price', 10, 2)->nullable()->comment('سعر القطاعي');
            $table->unsignedTinyInteger('discount')->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null')->comment('المورد');
            $table->string('unit')->nullable();
            // unit وحدة القياس (كرتونة - قطعة - كيلو إلخ)
            $table->timestamps();

            // for performance
            $table->index(['name']);
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
