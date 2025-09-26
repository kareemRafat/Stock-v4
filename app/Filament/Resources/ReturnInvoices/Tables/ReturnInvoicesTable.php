<?php

namespace App\Filament\Resources\ReturnInvoices\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class ReturnInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('return_invoice_number')
                    ->label('رقم الفاتورة')
                    ->color('indigo')
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('اسم العميل')
                    ->searchable(),

                TextColumn::make('original_invoice_number')
                    ->label('فاتورة المبيعات')
                    ->color('orange')
                    ->searchable()
                    ->url(fn($record) => url("/invoices/{$record->original_invoice_id}"))
                    ->openUrlInNewTab(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('عدد الأصناف'),

                TextColumn::make('createdDate')
                    ->label('تاريخ الإنشاء'),

                TextColumn::make('createdTime')
                    ->label('وقت الإنشاء'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض الفاتورة')
                    ->color('success'),
                // EditAction::make(),
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
