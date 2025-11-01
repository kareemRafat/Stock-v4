<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Filament\Actions\SupplierActions\PayDebtAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn ($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
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
                    ->url(fn ($record) => route('filament.admin.resources.suppliers.wallet', $record))
                    ->icon('heroicon-o-wallet'),
                PayDebtAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn () => ! Auth::user() || Auth::user()->role->value !== 'admin'),
                ]),
            ]);
    }
}
