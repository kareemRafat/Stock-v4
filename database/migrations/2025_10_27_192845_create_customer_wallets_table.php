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
        Schema::create('customer_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'sale',            // فاتورة بيع (تأثير موجب +)
                'payment',         // دفعة سداد (تأثير سالب -)
                'sale_return',     // مرتجع مبيعات (تأثير سالب -)
                'adjustment',       // تسوية يدوية
                'credit_use',
            ]);
            $table->decimal('amount', 10, 2);
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignId('return_invoice_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_wallets');
    }
};
