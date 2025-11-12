<?php

namespace App\Filament\Actions\ProductActions;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class AddStockAction
{
    public static function make(): Action
    {
        return Action::make('addStock')
            ->label('إضافة مخزون')
            ->icon('heroicon-o-plus-circle')
            ->color('teal')
            ->tooltip('إضافة كمية جديدة للمخزن')
            ->modalHeading('إضافة كمية للمخزن')
            ->modalSubmitActionLabel('حفظ')
            ->schema([
                TextInput::make('amount')
                    ->label('الكمية المضافة')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                ClientDatetimeHidden::make('created_at'),
            ])
            ->action(function (array $data, $record, $livewire) {
                app(StockService::class)->recordMovement(
                    product: $record,
                    movementType: \App\Enums\MovementType::ADJUSTMENT_IN,
                    quantity: $data['amount'],
                    costPrice: $record->cost_price,
                    wholeSalePrice: $record->wholesale_price,
                    discount: $record->discount,
                    retailPrice: $record->retail_price,
                    referenceId: $record->id,
                    referenceTable: 'products',
                    createdAt: $data['created_at'],
                );
            })
            ->successNotificationTitle('تمت إضافة الكمية بنجاح');
    }

    /** For using in tables */
    public static function forTable(): Action
    {
        return self::make()
            ->label('إضافة');
    }

    /** For using in header actions (if you want later) */
    public static function forHeader(): Action
    {
        return self::make()
            ->label('إضافة رصيد')
            ->outlined();
    }
}
