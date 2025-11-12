<?php

namespace App\Filament\Resources\Suppliers\Tables;

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
                    ->weight(FontWeight::Medium)
                    ->hidden(fn() => ! Auth::user()->isAdmin()),

                TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(30)
                    ->default('لايوجد')
                    ->weight(FontWeight::Medium)
                    ->hidden(fn() => ! Auth::user()->isAdmin()),

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
                    ->hidden(fn() => ! Auth::user()->isAdmin()),

                EditAction::make()
                    ->hidden(fn() => ! Auth::user()->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription(' هل أنت متأكد من القيام بهذه العملية ؟ سيتم حذف جميع سجلات الارصدة للمورد')
                        ->hidden(fn() => ! Auth::user()->isAdmin()),
                ]),
            ]);
    }
}
