<?php

namespace App\Filament\Actions\InvoiceActions;

use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\Forms\Components\ClientDatetimeHidden;
use Filament\Infolists\Components\TextEntry;

class PayInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('payInvoice')
            ->label(fn($record) => $record->status === 'paid' ? 'خالص' : 'سداد')
            ->disabled(fn($record) => $record->status === 'paid')
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
                    ->default(0)
                    ->dehydrated()
                    ->helperText('المبلغ المدفوع نقداً.')
                    ->columnSpan(1),

                // عرض الرصيد الدائن المتاح
                TextEntry::make('available_credit')
                    ->label('رصيد المحفظة الدائن الحالي')
                    ->state(fn($record) => $record->customer->getAvailableCreditBalance($record->created_at))
                    ->formatStateUsing(function ($state) {
                        if ($state > 0) {
                            return number_format($state, 2) . ' ج.م';
                        }
                        return 'لا يوجد رصيد دائن للعميل';
                    })
                    ->color(fn($state) => $state > 0 ? 'success' : 'indigo')
                    ->weight('semibold'),

                // عرض المبلغ المطلوب سداده
                TextEntry::make('required_payment')
                    ->label(' المبلغ المطلوب سداده نقداً شامل الرصيد ')
                    ->state(function ($record) {
                        $total = $record->total_amount;
                        $availableCredit = $record->customer->getAvailableCreditBalance($record->created_at);
                        return max(0, $total - $availableCredit);
                    })
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' ج.م')
                    ->color('danger')
                    ->weight('semibold')
                    ->columnSpan(1),

                ClientDatetimeHidden::make('created_at'),
            ])
            ->action(function (array $data, Model $record) {
                // التحقق من وجود مبلغ للسداد
                $paid = $data['paid'] ?? 0;
                $availableCredit = $record->customer->getAvailableCreditBalance($record->created_at);

                if ($paid <= 0 && $availableCredit <= 0) {
                    return self::notifyError('حدث خطأ: يجب إدخال مبلغ نقدي أو توفر رصيد دائن كافٍ للعميل.');
                }

                DB::transaction(function () use ($data, $record) {
                    $customer = $record->customer;
                    $paid = $data['paid'] ?? 0;
                    $total = $record->total_amount;

                    // 1. حساب الرصيد الدائن المتاح قبل الفاتورة
                    $availableCredit = $customer->getAvailableCreditBalance($record->created_at);

                    // 2. المبلغ الذي سيتم تغطيته من الرصيد الدائن
                    $amountToUseFromCredit = min($availableCredit, max(0, $total - $paid));

                    // 3. المبلغ المتبقي للسداد
                    $remainingDebt = $total - $paid - $amountToUseFromCredit;

                    // 4. تسجيل حركة الدفعة النقدية (دائن)
                    if ($paid > 0) {
                        $customer->wallet()->create([
                            'type' => 'payment',
                            'amount' => $paid,
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                            'notes' => 'سداد نقدي للفاتورة ',
                            'created_at' => $data['created_at'],
                        ]);
                    }

                    // 5. استخدام الرصيد الدائن (دائن)
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

                    // 6. تحديث حالة الفاتورة
                    self::updateInvoiceStatus($record, $remainingDebt);
                    self::notifySuccess('تمت عملية التسديد بنجاح');
                });
            })
            ->color(fn($record) => $record->status === 'paid' ? 'gray' : 'rose')
            ->extraAttributes(['class' => 'font-medium'])
            ->icon(fn($record) => $record->status === 'paid' ? 'heroicon-s-check-circle' : 'heroicon-s-banknotes');
    }

    protected static function updateInvoiceStatus($record, float $remainingDebt): void
    {
        if ($remainingDebt <= 0) {
            $record->update(['status' => 'paid']);
        }
        // else {
        //     $record->update(['status' => 'partial']);
        // }
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
