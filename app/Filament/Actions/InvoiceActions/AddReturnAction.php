<?php

namespace App\Filament\Actions\InvoiceActions;

use Filament\Tables\Actions\Action;
use App\Filament\Resources\ReturnInvoiceResource;

class AddReturnAction
{
    public static function make(): Action
    {
        return Action::make('returninvoice')
            ->label('عمل مرتجع')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->url(fn($record) => ReturnInvoiceResource::getUrl('create', [
                'original_invoice' => $record->id, // هنمرر الـ ID
            ]))
            ->color('indigo')
            ->tooltip(
                fn($record) => $record->hasReturnableItems()
                    ? 'إنشاء مرتجع للفاتورة'
                    : 'لا توجد منتجات متاحة للاسترجاع'
            )
            ->disabled(fn($record) => !$record->hasReturnableItems());
        // ->extraAttributes(['class' => 'font-semibold']);
    }
}
