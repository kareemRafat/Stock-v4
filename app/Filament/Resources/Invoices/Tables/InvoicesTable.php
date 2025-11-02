<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Filament\Actions\InvoiceActions\MarkAsPaid;
use App\Models\Invoice;
use App\Models\Customer;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Actions\InvoiceActions\PayInvoiceAction;
use App\Filament\Forms\Components\ClientDatetimeHidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

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

                TextColumn::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->formatStateUsing(fn($record) => number_format($record->total_amount - $record->special_discount, 2))
                    ->suffix(' جنيه '),

                TextColumn::make('remaining')
                    ->label('المتبقي')
                    ->state(fn($record) => max(0, ($record->total_amount - $record->special_discount) - $record->paid_amount))
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' ج.م')
                    ->badge()
                    ->color(fn($state) => $state == 0 ? 'success' : 'danger'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->icon(fn($record) => $record->status === 'partial'
                        ? 'heroicon-o-arrow-right-circle'
                        : null)
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'orange',
                        'paid' => 'success',
                        'partial' => 'rose',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'paid' => 'مدفوعة',
                        'partial' => 'مدفوعة جزئياً',
                        'cancelled' => 'ملغاة',
                        default => $state,
                    })
                    ->action(
                        MarkAsPaid::make()
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الفاتورة')
                    ->options([
                        'paid' => 'المدفوعة',
                        'pending' => 'غير المدفوعة',
                        'partial' => 'المدفوعة جزئياً',
                    ])
                    ->native(false)
                    ->default(''),

                SelectFilter::make('customer_id')
                    ->label('اسم العميل')
                    ->searchable()
                    ->options(fn() => Customer::limit(20)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn($value): ?string => Customer::find($value)?->name)
                    ->getSearchResultsUsing(fn(string $search) => Customer::where('name', 'like', "%{$search}%")
                        ->pluck('name', 'id')
                        ->toArray())
                    ->placeholder('كل العملاء'),

                SelectFilter::make('price_type')
                    ->label('نوع الفاتورة')
                    ->options([
                        'wholesale' => 'جملة',
                        'retail' => 'قطاعي',
                    ])
                    ->native(false),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('عرض الفاتورة'),
                PayInvoiceAction::make(),
            ])
            ->toolbarActions([
                /* BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
                ]), */]);
    }
}
