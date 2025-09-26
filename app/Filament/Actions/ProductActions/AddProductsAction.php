<?php

namespace App\Filament\Actions\ProductActions;

use App\Filament\Resources\ProductResource\Pages\AddProducts;
use Filament\Actions\Action;

class AddProductsAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('إضـافـة منتجات') // عنوان الزر
            ->icon('heroicon-o-cube') // أيقونة مناسبة
            ->url(fn ($record) => AddProducts::getUrl()); // يفتح صفحة AddProducts
    }
}
