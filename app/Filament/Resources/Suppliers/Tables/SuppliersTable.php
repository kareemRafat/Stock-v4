<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Models\Supplier;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                // eager loading
                Supplier::query()
                    ->withSum(['wallet as debit_sum' => function ($query) {
                        $query->whereIn('type', ['debit', 'invoice']);
                    }], 'amount')
                    ->withSum(['wallet as credit_sum' => function ($query) {
                        $query->where('type', 'credit');
                    }], 'amount')
            )
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
                            + $rowLoop->iteration
                    )
                    ->sortable(false)
                    ->weight('semibold'),
                TextColumn::make('name')
                    ->label('اسم المورد')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->color('violet'),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->weight(FontWeight::Medium),

                TextColumn::make('balance')
                    ->label('رصيد المورد')
                    ->getStateUsing(fn($record) => ($record->credit_sum - $record->debit_sum) ?? 0)
                    ->formatStateUsing(
                        fn($state) =>
                        $state == 0
                            ? '0 ج.م'
                            : number_format($state, 2) . ' ج.م'
                    )
                    ->color(
                        fn($state) =>
                        $state < 0 ? 'rose' : ($state > 0 ? 'success' : 'gray')
                    )
                    ->weight(FontWeight::Medium),
                TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(30)
                    ->default('لايوجد')
                    ->weight(FontWeight::Medium),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d-m-Y')
                    ->weight(FontWeight::Medium),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('wallet')
                    ->label('حركة الرصيد')
                    ->color('teal')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->url(fn($record) => route('filament.admin.resources.suppliers.wallet', $record))
                    ->icon('heroicon-o-wallet')
                    ->disabled(fn($record) => $record->balance == 0),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
                ]),
            ]);
    }
}
