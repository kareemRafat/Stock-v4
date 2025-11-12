<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->striped()
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
                    ->searchable()
                    ->label('إسم العميل')
                    ->searchable()
                    ->fontFamily(FontFamily::Sans)
                    ->color('indigo')
                    ->weight(FontWeight::Medium),

                TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->label('رقم التواصل')
                    ->weight(FontWeight::Medium)
                    ->hidden(fn () => ! Auth::user()->isAdmin()),

                TextColumn::make('balance_sum')
                    ->label('رصيد العميل')
                    ->formatStateUsing(
                        fn ($state) => $state == 0
                            ? '0 ج.م'
                            : number_format(abs($state), 2).' ج.م'
                    )
                    ->color(
                        fn ($state) => $state > 0 ? 'rose' : ($state < 0 ? 'success' : 'gray')
                    )
                    ->tooltip(function ($state) {
                        if ($state > 0) {
                            return 'مديونية على العميل';
                        } elseif ($state < 0) {
                            return 'رصيد دائن للعميل';
                        } else {
                            return 'حساب متوازن';
                        }
                    })
                    ->url(function ($record) {
                        // open url only for admin
                        if (Auth::user() && Auth::user()->isAdmin()) {
                            return route('filament.admin.resources.customers.wallet', $record);
                        }

                        return null;
                    })
                    ->weight(FontWeight::Medium),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date('d-m-Y')
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enabled' => 'success', // Yellow badge for enabled
                        'disabled' => 'warning', // Green badge for disabled

                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'enabled' => 'مفعل',
                        'disabled' => 'معطل',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->tooltip(' تفاصيل العميل')
                    ->label('عرض التفاصيل'),

                Action::make('wallet')
                    ->label('حركة الرصيد')
                    ->color('teal')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->url(fn ($record) => CustomerResource::getUrl('wallet', ['record' => $record]))
                    ->icon('heroicon-o-wallet')
                    ->hidden(fn () => ! Auth::user()->isAdmin()),

                EditAction::make()
                    ->extraAttributes(['class' => 'font-semibold']),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription(' هل أنت متأكد من القيام بهذه العملية ؟ سيتم حذف جميع سجلات الارصدة للعميل')
                        ->extraAttributes(['class' => 'font-semibold'])
                        ->hidden(fn () => ! Auth::user()->isAdmin()),
                ]),
            ]);
    }
}
