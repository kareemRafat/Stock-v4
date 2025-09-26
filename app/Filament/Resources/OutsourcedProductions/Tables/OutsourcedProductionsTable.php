<?php

namespace App\Filament\Resources\OutsourcedProductions\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;

class OutsourcedProductionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null) // prevent clickable row
            ->recordUrl(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product_name')
                    ->label('اسم الصنف')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم النسخ')
                    ->copyMessageDuration(1500)
                    ->color('indigo'),
                TextColumn::make('factory_name')
                    ->label('اسم المصنع')
                    ->weight(FontWeight::Medium),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->weight(FontWeight::Medium)
                    ->numeric(locale: 'en'),
                TextColumn::make('size')
                    ->label('المقاس')
                    ->weight(FontWeight::Medium),
                TextColumn::make('total_cost')
                    ->label('التكلفة')
                    ->weight(FontWeight::Medium)
                    ->numeric(locale: 'en')
                    ->suffix(' ج.م '),
                TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->weight(FontWeight::Medium)
                    ->date("d-m-Y")
                    ->sortable(),
                TextColumn::make('actual_delivery_date')
                    ->label('تاريخ التسليم')
                    ->weight(FontWeight::Medium)
                    ->date("d-m-Y")
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->weight(FontWeight::Medium)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'canceled' => 'ملغي',
                        default => $state,
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->extraAttributes(['class' => 'font-semibold'])
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
                ]),
            ]);
    }
}
