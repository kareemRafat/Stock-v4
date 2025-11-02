<?php

namespace App\Filament\Resources\Invoices\Tables;

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
                        Action::make('markAsPaid')
                            ->label('تسديد كامل')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('تسديد الفاتورة بالكامل')
                            ->modalDescription(
                                fn($record) =>
                                'سيتم تسجيل دفعة بقيمة: ' .
                                    number_format($record->total_amount - $record->paid_amount, 2) .
                                    ' ج.م لإتمام سداد الفاتورة'
                            )
                            ->schema([
                                ClientDatetimeHidden::make('created_at')
                            ])
                            ->modalSubmitActionLabel('تسديد الآن')
                            ->action(function ($record, $data) {
                                DB::transaction(function () use ($record, $data) {
                                    // حساب المبلغ المتبقي
                                    $remainingAmount = $record->total_amount - $record->special_discount - $record->paid_amount;
                                    if ($remainingAmount > 0) {
                                        // تسجيل الدفعة في المحفظة
                                        $record->customer->wallet()->create([
                                            'type' => 'payment',
                                            'amount' => $remainingAmount,
                                            'invoice_id' => $record->id,
                                            'invoice_number' => $record->invoice_number,
                                            'notes' => 'سداد كامل للفاتورة (المبلغ المتبقي)',
                                            'created_at' => $data['created_at'],
                                        ]);

                                        // تحديث paid_amount
                                        $record->update([
                                            'paid_amount' => $record->paid_amount + $remainingAmount,
                                            'status' => 'paid'
                                        ]);

                                        Notification::make()
                                            ->success()
                                            ->title('تم تسديد الفاتورة بنجاح')
                                            ->body('تم تسجيل دفعة بقيمة ' . number_format($remainingAmount, 2) . ' ج.م')
                                            ->send();
                                    } else {
                                        // الفاتورة مدفوعة فعلاً
                                        $record->update(['status' => 'paid']);

                                        Notification::make()
                                            ->info()
                                            ->title('الفاتورة مدفوعة')
                                            ->body('لا يوجد مبلغ متبقي للسداد')
                                            ->send();
                                    }
                                });
                            })
                            ->visible(fn($record) => $record->status === 'partial')
                    ),
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
                        'retail' => 'قطاعي',
                    ])
                    ->native(false)
                    ->columnSpan(2),

            ], layout: FiltersLayout::AboveContent)
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
