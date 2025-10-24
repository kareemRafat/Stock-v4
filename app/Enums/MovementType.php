<?php

namespace App\Enums;

enum MovementType: string
{
    case OPENING_STOCK = 'opening_stock';
    case PURCHASE = 'purchase';
    case INVOICE_SALE = 'invoice_sale';
    case PURCHASE_RETURN = 'purchase_return';
    case SALE_RETURN = 'sale_return';
    case ADJUSTMENT = 'adjustment';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';

    /**
     * تحقق إذا كانت الحركة إدخال (input)
     */
    // 'opening_stock', 'purchase', 'sale_return', 'adjustment_in' , 'adjustment_in'
    // output 'invoice_sale', 'purchase_return', 'adjustment_out'
    public function isInput(): bool
    {
        return in_array($this, [
            self::OPENING_STOCK,
            self::PURCHASE,
            self::SALE_RETURN,
            self::ADJUSTMENT_IN,
            self::ADJUSTMENT,
        ]);
    }

    /**
     * تحقق إذا كانت الحركة إخراج (output)
     */
    public function isOutput(): bool
    {
        return !$this->isInput();
    }

    /**
     * احصل على الوصف بالعربي
     */
    public function label(): string
    {
        return match($this) {
            self::OPENING_STOCK => 'رصيد افتتاحي',
            self::PURCHASE => 'شراء من مورد',
            self::INVOICE_SALE => 'بيع لعميل',
            self::PURCHASE_RETURN => 'مرتجع للمورد',
            self::SALE_RETURN => 'مرتجع من عميل',
            self::ADJUSTMENT_IN => 'تعديل يدوي (إدخال)',
            self::ADJUSTMENT_OUT => 'تعديل يدوي (إخراج)',
            self::ADJUSTMENT => 'تعديل يدوي',
        };
    }

    /**
     * احصل على اللون في Filament
     */
    public function color(): string
    {
        return match($this) {
            self::OPENING_STOCK => 'info',
            self::PURCHASE, self::SALE_RETURN, self::ADJUSTMENT_IN => 'success',
            self::INVOICE_SALE, self::PURCHASE_RETURN, self::ADJUSTMENT_OUT => 'danger',
        };
    }

    /**
     * احصل على كل قيم الإدخال
     */
    public static function inputs(): array
    {
        return [
            self::OPENING_STOCK,
            self::PURCHASE,
            self::SALE_RETURN,
            self::ADJUSTMENT_IN,
            self::ADJUSTMENT_OUT,
            self::ADJUSTMENT,
        ];
    }

    /**
     * احصل على كل قيم الإخراج
     */
    public static function outputs(): array
    {
        return [
            self::INVOICE_SALE,
            self::PURCHASE_RETURN,
            self::ADJUSTMENT_OUT,
        ];
    }
}
