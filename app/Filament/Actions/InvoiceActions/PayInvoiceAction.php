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

                //  Show balance for the customer
                TextEntry::make('id')
                    ->label('رصيد المحفظة الدائن الحالي')
                    ->formatStateUsing(function ($record) {
                        $customer = $record->customer;

                        // حساب الرصيد باستثناء حركات الفاتورة الحالية
                        $balance = $customer->wallet()
                            ->where('invoice_id', '!=', $record->id)
                            ->sum('amount');

                        if ($balance < 0) {
                            return number_format(abs($balance), 2) . ' ج.م';
                        }

                        return 'لا يوجد رصيد دائن للعميل';
                    })
                    ->extraAttributes(function ($record) {
                        $balance = $record->customer->wallet()
                            ->where('invoice_id', '!=', $record->id)
                            ->sum('amount');

                        if ($balance < 0) {
                            $color = '#16a34a'; // أخضر: رصيد دائن متاح
                        } else {
                            $color = '#1f2937'; // رمادي/أسود
                        }

                        return ['style' => "color: {$color}"];
                    }),

                TextEntry::make('id')
                    ->label('المبلغ المطلوب سداده نقداً')
                    ->formatStateUsing(function ($record) {
                        $total = $record->total_amount;

                        // جلب الرصيد الدائن المتاح (قيمة موجبة)
                        $availableCredit = self::getAvailableCreditBalance($record);

                        // المبلغ المتبقي للسداد = الإجمالي - الرصيد الدائن المتاح
                        $requiredPayment = max(0, $total - $availableCredit);

                        // نضمن عرض القيمة الموجبة لتجنب اللبس
                        return number_format($requiredPayment, 2) . ' ج.م';
                    })
                    ->extraAttributes(['class' => 'text-rose-600'])
                    ->columnSpan(1),

                // get js date
                ClientDatetimeHidden::make('created_at'),
            ])
            ->action(function (array $data, Model $record) {
                // **تم حذف فحص removeFromWallet**
                if (($data['paid'] ?? 0) <= 0 && self::getAvailableCreditBalance($record) >= 0) {
                    // فحص إذا كان المبلغ المدفوع صفر والرصيد الدائن المتاح صفر، نمنع العملية
                    return self::notifyError('حدث خطأ: يجب إدخال مبلغ نقدي أو توفر رصيد دائن كافٍ للعميل.');
                }


                DB::transaction(function () use ($data, $record) {
                    $customer = $record->customer;
                    $paid = $data['paid'] ?? 0; // المبلغ المدفوع نقداً/بطاقة: 864.00
                    $total = $record->total_amount; // قيمة الفاتورة الكلية: 882.00

                    // 1. حساب الرصيد الدائن المتاح للعميل قبل هذه الفاتورة
                    $availableCredit = self::getAvailableCreditBalance($record);

                    // 2. المبلغ الذي سيتم تغطيته من الرصيد الدائن
                    $amountToUseFromCredit = min($availableCredit, $total - $paid);

                    // 3. المبلغ المتبقي للسداد بعد الدفعة النقدية واستخدام الرصيد
                    $remainingDebt = $total - $paid - $amountToUseFromCredit;

                    // 4. تسجيل حركة الدفعة النقدية فقط (كما اتفقنا -864.00)
                    if ($paid > 0) {
                        $customer->wallet()->create([
                            'type' => 'payment',
                            'amount' => -$paid,
                            'invoice_id' => $record->id,
                            'notes' => 'دفعة نقدية/بطاقة لسداد جزء من الفاتورة',
                            'created_at' => $data['created_at'] ?? now(),
                        ]);
                    }

                    // 5. إلغاء الرصيد الدائن السابق (+18.00)
                    if ($amountToUseFromCredit > 0) {
                        $customer->wallet()->create([
                            'type' => 'credit_use',
                            'amount' => $amountToUseFromCredit, // قيمة موجبة لخصمها من الرصيد السالب
                            'invoice_id' => $record->id,
                            'notes' => 'خصم من الرصيد',
                            'created_at' => $data['created_at'] ?? now(),
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

    // ************* الدوال المساعدة المُعدلة *************

    /**
     * يحسب الرصيد الدائن المتاح للعميل (القيمة السالبة) باستثناء حركات الفاتورة الحالية.
     * @return float القيمة المطلقة للرصيد الدائن (موجبة) أو 0.0 إذا كان الرصيد مديونية أو صفر.
     */
    protected static function getAvailableCreditBalance($record): float
    {
        $customer = $record->customer;
        // حساب الرصيد باستثناء حركات الفاتورة الحالية
        $balance = $customer->wallet()
            ->where('invoice_id', '!=', $record->id)
            ->sum('amount');

        // إذا كان سالب (أي دائن)، نُعيد القيمة المطلقة (الموجبة)
        return $balance < 0 ? abs($balance) : 0.0;
    }

    protected static function updateInvoiceStatus($record, float $remainingDebt): void
    {
        // إذا كان المبلغ المتبقي للسداد يساوي أو أقل من الصفر (تم تغطية الدين بالكامل أو حدث فائض)
        if ($remainingDebt <= 0) {
            $record->update(['status' => 'paid']);
        }
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
