<?php

namespace App\Filament\Actions\InvoiceActions;

use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Toggle;
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
            ->label(fn($record) => $record->status === 'paid' ? 'خالص' : 'سداد ')
            ->disabled(fn($record) => $record->status === 'paid')
            ->modalSubmitActionLabel('تسديد فاتورة')
            ->modalHeading(
                fn(Model $record) => new HtmlString('تسديد فاتورة العميل: ' . "<span style='color: #3b82f6 !important'>{$record->customer->name}</span>")
            )
            ->schema([
                TextInput::make('paid')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->dehydrated()
                    ->helperText('المبلغ المدفوع نقداً/ببطاقة. إذا كان المبلغ أكبر من الفاتورة، يضاف الباقي لرصيد العميل.')
                    ->columnSpan(1),
                Toggle::make('removeFromWallet')
                    ->label('استخدام الرصيد المتاح للعميل') // تم تغيير التسمية لتكون أوضح
                    ->default(false)
                    ->dehydrated(fn($state) => $state !== null)
                    ->helperText('استخدام رصيد العميل الحالي (إن وجد) لتغطية المتبقي من الفاتورة.')
                    ->columnSpan(2)
                    ->inline(false)
                    // تعطيل إذا كان الرصيد لا يكفي (الرصيد السالب يعني له رصيد)
                    ->disabled(fn($record) => $record->customer->balance >= 0),
                TextEntry::make('customer.balance')
                    ->label('رصيد المحفظة الحالي')
                    ->formatStateUsing(function ($record) {
                        $balance = $record->customer->balance ?? 0;
                        if ($balance < 0) {
                            return number_format(abs($balance), 2) . ' ج.م';
                        }
                        return 'لايوجد رصيد للعميل';
                    })
                    ->extraAttributes(function ($record) {
                        $balance = $record?->customer?->balance ?? 0;
                        if ($balance < 0) {
                            $color = 'red'; // أحمر: دين عليك للعميل (العميل له رصيد)
                        } else {
                            $color = 'green'; // رمادي/أسود: مديونية أو صفر
                        }

                        // نعيد الألوان والوزن فقط إذا كان هناك رصيد لكي يظهر اللون الأحمر بوضوح
                        return ['style' => "color: {$color};"];
                    }),
                // get js date
                ClientDatetimeHidden::make('created_at'),
            ])
            ->action(function (array $data, Model $record) {
                if ($data['paid'] <= 0 && (empty($data['removeFromWallet']) || !$data['removeFromWallet'])) {
                    return self::notifyError('حدث خطأ: لم يتم إدخال مبلغ أو اختيار السداد من الرصيد');
                }

                // يجب التأكد من أن حقل الفاتورة (sale) تم إضافته بالفعل
                // $record->customer->wallet()->create([
                //     'type' => 'sale',
                //     'amount' => $record->total_amount, // الموجب يمثل دين على العميل
                //     'invoice_id' => $record->id,
                // ]);

                DB::transaction(function () use ($data, $record) {
                    $customer = $record->customer;
                    $paid = $data['paid'] ?? 0;
                    $total = $record->total_amount;

                    // المبلغ المتبقي للسداد بعد الدفعة النقدية
                    $balanceDue = $total - $paid;

                    // استخدام المحفظة لسداد المتبقي (إن طلب العميل)
                    $walletAmountUsed = 0;
                    if ($balanceDue > 0) {
                        $walletAmountUsed = self::useWalletIfRequested($customer, $record, $balanceDue, $data);
                    }

                    // الدين المتبقي النهائي: balanceDue - walletAmountUsed
                    // (إذا كانت النتيجة سالبة، فهذا هو الرصيد الفائض)
                    $remainingDebt = $balanceDue - $walletAmountUsed;

                    // 4. تسجيل حركة الدفعة النقدية (إن وجدت) - هذه الحركة تشمل الفائض
                    if ($paid > 0) {
                        $customer->wallet()->create([
                            'type' => 'payment',
                            'amount' => -$paid, // قيمة سالبة دائماً
                            'invoice_id' => $record->id,
                            'notes' => 'دفعة نقدية لسداد جزء من الفاتورة',
                            'created_at' => $data['created_at'] ?? now(),
                        ]);
                    }

                    // 5. تحديث حالة الفاتورة (يجب أن تعتمد على الرصيد المتبقي وليس فقط تحديث paid)
                    self::updateInvoiceStatus($record, $remainingDebt);

                    self::notifySuccess('تمت عملية التسديد بنجاح');
                });
            })
            ->color(fn($record) => $record->status === 'paid' ? 'gray' : 'rose')
            ->extraAttributes(['class' => 'font-medium'])
            ->icon(fn($record) => $record->status === 'paid' ? 'heroicon-s-check-circle' : 'heroicon-s-banknotes');
    }

    // ************* الدوال المساعدة المُعدلة *************

    protected static function useWalletIfRequested($customer, $record, float $amountToCover, array $data): float
    {
        $walletAmountUsed = 0;

        if (!empty($data['removeFromWallet']) && $data['removeFromWallet'] && $amountToCover > 0) {

            // الرصيد للعميل هو قيمة سالبة في الـ balance
            $availableBalance = abs($customer->balance);

            // المبلغ الذي سنستخدمه هو الأقل بين (الرصيد المتاح) و (المبلغ المتبقي تغطيته)
            $walletAmountUsed = min($availableBalance, $amountToCover);

            if ($walletAmountUsed > 0) {
                // تسجيل الحركة كدفعة (payment)، والقيمة سالبة (-) لأنها تخفض المديونية
                // ملاحظة: بما أن هذه الحركة تسدد فاتورة، يجب أن تكون قيمة المبلغ **سالبة** لتعكس أنها دفعة.
                $customer->wallet()->create([
                    'type' => 'payment',
                    'amount' => -$walletAmountUsed, // سالب ليعكس أنه سداد من الرصيد
                    'invoice_id' => $record->id,
                    'notes' => 'خصم من رصيد العميل المتاح لسداد المتبقي من الفاتورة',
                    'created_at' => $data['created_at'] ?? now(),
                ]);
            }
        }

        return $walletAmountUsed;
    }


    protected static function updateInvoiceStatus($record, float $remainingDebt): void
    {
        // إذا كان المبلغ المتبقي للسداد يساوي أو أقل من الصفر (تم تغطية الدين بالكامل أو حدث فائض)
        if ($remainingDebt <= 0) {
            $record->update(['status' => 'paid']);
        }
    }


    protected static function notifyError(string $message) // <-- إضافة static
    {
        Notification::make()
            ->title($message)
            ->warning()
            ->send();
    }

    protected static function notifySuccess(string $message) // <-- إضافة static
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
