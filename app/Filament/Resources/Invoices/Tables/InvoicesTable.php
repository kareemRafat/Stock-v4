<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Models\Customer;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Actions\InvoiceActions\PayInvoiceAction;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['returnInvoices']))
            ->recordUrl(null) // disable row clicking
            ->recordAction(null)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->weight('semibold')
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => strtoupper($state)),

                TextColumn::make('customer.name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->url(fn(Invoice $record): ?string => $record->customer_id ?
                        CustomerResource::getUrl('view', ['record' => $record->customer_id]) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('price_type')
                    ->label('نوع الفاتورة')
                    ->formatStateUsing(fn($state) => $state === 'wholesale' ? 'جملة' : 'قطاعي')
                    ->badge()
                    ->colors([
                        'primary' => fn($state) => $state === 'wholesale',
                        'success' => fn($state) => $state === 'retail',
                    ]),

                TextColumn::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->formatStateUsing(fn($record) => number_format($record->total_amount - $record->special_discount, 2) . ' جنيه')
                    ->suffix(' جنيه '),

                TextColumn::make('createdDate')
                    ->label('تاريخ الفاتورة')
                    ->color('primary'),

                TextColumn::make('has_returns')
                    ->label('هل بها مرتجع؟')
                    ->extraAttributes(['class' => 'text-sm'])
                    ->icon(fn(Invoice $record) => $record->has_returns ? 'heroicon-o-arrow-path' : 'heroicon-o-check')
                    ->iconPosition('before')
                    ->color(fn(Invoice $record): string => $record->has_returns ? 'danger' : 'success')
                    ->formatStateUsing(fn(Invoice $record): string => $record->has_returns ? 'مرتجع' : 'لا'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'orange',
                        'paid'      => 'success',
                        'cancelled' => 'danger',
                        default     => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending'   => 'قيد الانتظار',
                        'paid'      => 'مدفوعة',
                        'cancelled' => 'ملغاة',
                        default     => $state,
                    }),
            ])
            ->filters([
                Filter::make('status')
                    ->schema([
                        Toggle::make('pending_only')
                            ->label('عرض الفواتير غير المدفوعة')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['pending_only'] ?? false) {
                            $query->where('status', 'pending');
                        }
                    })
                    ->columnSpanFull(),

                SelectFilter::make('customer_id')
                    ->label('اسم العميل')
                    ->searchable()
                    ->options(fn() => Customer::limit(20)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn($value): ?string => Customer::find($value)?->name)
                    ->getSearchResultsUsing(fn(string $search) => Customer::where('name', 'like', "%{$search}%")
                        ->pluck('name', 'id')
                        ->toArray())
                    ->placeholder('كل العملاء')
                    ->columnSpan(2),

                SelectFilter::make('price_type')
                    ->label('نوع الفاتورة')
                    ->options([
                        'wholesale' => 'جملة',
                        'retail'    => 'قطاعي',
                    ])
                    ->native(false)
                    ->columnSpan(2),

            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('عرض الفاتورة'),
                PayInvoiceAction::make(),

                /* ActionGroup::make([
                    EditAction::make()
                        ->extraAttributes(['class' => 'font-medium']),
                ])
                    ->label('المزيد')
                    ->button()
                    ->color('gray')
                    ->size('xs')
                    ->tooltip('إجراءات إضافية'), */

            ])
            ->toolbarActions([
                /* BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
                ]), */]);
    }
}
