<?php

namespace App\Filament\Resources\SupplierInvoices\Tables;

use App\Models\Supplier;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;

class SupplierInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->color('indigo')
                    ->weight('medium')
                    ->formatStateUsing(fn($state) => strtoupper($state)),

                TextColumn::make('supplier.name')
                    ->label('اسم المورد')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->weight('medium')
                    ->suffix(' جنيه '),

                TextColumn::make('created_at')
                    ->label('تاريخ الفاتورة')
                    ->weight('medium')
                    ->color('primary')
                    ->date("d/m/Y")
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->options(
                        fn() => Supplier::query()
                            ->latest()
                            ->limit(10)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search) =>
                        Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->placeholder('كل الموردين')
                    ->columnSpan(2),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('عرض الفاتورة'),
                // EditAction::make()
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
