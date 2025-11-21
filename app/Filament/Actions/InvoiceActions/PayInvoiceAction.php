<?php

namespace App\Filament\Actions\InvoiceActions;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PayInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('payInvoice')
            ->label(fn($record) => in_array($record->status, ['paid', 'partial']) ? 'مسدد ' : 'سداد')
            ->disabled(function ($record) {
                return in_array($record->status, ['paid', 'partial']) ||
                    $record->has_newer_unpaid > 0;
            })
            ->modalSubmitActionLabel('تسديد فاتورة')
            ->modalHeading(
                fn(Model $record) => new HtmlString('تسديد فاتورة العميل: ' . "<span style='color: #3b82f6 !important'>{$record->customer->name}</span>")
            )
            ->schema([
                // حقل المبلغ المدفوع
                TextInput::make('paid')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->required()
                    // ->default(fn($record) => number_format($record->amount_due_without_credit ,2))
                    ->dehydrated()
                    ->placeholder('المبلغ المدفوع نقداً')
                    ->columnSpan(1),

                // عرض الرصيد الدائن المتاح
                TextEntry::make('available_credit')
                    ->label(' رصيد المحفظة الدائن')
                    ->state(function ($record) {
                        static $cachedBalance = null;

                        if ($cachedBalance === null) {
                            $cachedBalance = $record->customer->getAvailableCreditBalance();
                        }

                        return $cachedBalance;
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state > 0) {
                            return number_format($state, 2) . ' ج.م';
                        }

                        return 'لا يوجد رصيد دائن';
                    })
                    ->color(fn($state) => $state > 0 ? 'success' : 'indigo')
                    ->weight('semibold'),

                // عرض المبلغ المطلوب سداده
                TextEntry::make('required_payment')
                    ->label('المبلغ المطلوب سداده')
                    ->state(fn($record) => $record->amount_due_without_credit)
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' ج.م')
                    ->color('danger')
                    ->weight('bold')
                    ->columnSpan(1)
                    ->hint('الفاتورة + المديونية - المرتجعات')
                    ->hintColor('gray'),

                ClientDatetimeHidden::make('created_at'),
            ])
            ->action(function (array $data, Model $record) {
                $paid = $data['paid'] ?? 0;

                // حساب الرصيد الدائن الحالي
                $availableCredit = $record->customer->getAvailableCreditBalance();

                if ($paid <= 0 && $availableCredit <= 0) {
                    return self::notifyError('حدث خطأ: يجب إدخال مبلغ نقدي أو توفر رصيد دائن كافٍ للعميل.');
                }

                DB::transaction(function () use ($data, $record, $paid, $availableCredit) {
                    $customer = $record->customer;

                    // 1. حساب الإجمالي المطلوب للفاتورة (شامل المديونية والمرتجعات)
                    $totalDue = $record->amount_due_without_credit;

                    // 2. المبلغ الذي سيتم تغطيته من الرصيد الدائن
                    $amountToUseFromCredit = min($availableCredit, max(0, $totalDue - $paid));

                    // 3. إجمالي المبلغ المدفوع (نقدي + رصيد)
                    $totalPaidNow = $paid + $amountToUseFromCredit;

                    // 4. المبلغ المتبقي للسداد
                    $remainingDebt = $totalDue - $totalPaidNow;

                    // 5. تسجيل حركة الدفعة النقدية
                    if ($paid > 0) {
                        $customer->wallet()->create([
                            'type' => 'payment',
                            'amount' => $paid,
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                            'notes' => 'سداد نقدي للفاتورة',
                            'created_at' => $data['created_at'],
                        ]);
                    }

                    // 6. استخدام الرصيد الدائن
                    if ($amountToUseFromCredit > 0) {
                        $customer->wallet()->create([
                            'type' => 'credit_use',
                            'amount' => $amountToUseFromCredit,
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                            'notes' => 'خصم من الرصيد الدائن للفاتورة',
                            'created_at' => $data['created_at'],
                        ]);
                    }

                    // 7. تحديث المبلغ المدفوع في الفاتورة
                    $record->update([
                        'paid_amount' => $record->paid_amount + $totalPaidNow
                    ]);

                    // 8. تحديث حالة الفاتورة
                    self::updateInvoiceStatus($record, $remainingDebt);
                    self::notifySuccess('تمت عملية التسديد بنجاح');
                });
            })
            ->color(fn($record) => in_array($record->status, ['paid', 'partial']) ? 'gray' : 'rose')
            ->extraAttributes(['class' => 'font-medium'])
            ->icon(fn($record) => in_array($record->status, ['paid', 'partial']) ? 'heroicon-s-check-circle' : 'heroicon-s-banknotes');
    }

    protected static function updateInvoiceStatus($record, $remainingDebt): void
    {
        $customer = $record->customer;

        if (abs($remainingDebt) < 0.01) {
            $record->update(['status' => 'paid']);
        } else if ($remainingDebt > 0) {
            $record->update(['status' => 'partial']);
        } else {
            $record->update(['status' => 'paid']);
        }

        // سدد كل الفواتير الأقدم
        $customer->invoices()
            ->where('created_at', '<', $record->created_at)
            ->whereIn('status', ['pending', 'partial'])
            ->update(['status' => 'paid']);

        DB::table('invoices')
            ->where('customer_id', $customer->id)
            ->where('created_at', '<=', $record->created_at)
            ->where('status', 'paid')
            ->update([
                'paid_amount' => DB::raw('total_amount - special_discount + previous_debt')
            ]);
    }

    protected static function notifyError(string $message)
    {
        Notification::make()
            ->title($message)
            ->warning()
            ->send();
    }

    protected static function notifySuccess(string $message)
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
