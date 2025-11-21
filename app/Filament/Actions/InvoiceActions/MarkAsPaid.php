<?php

namespace App\Filament\Actions\InvoiceActions;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class MarkAsPaid
{
    public static function make(): Action
    {
        return Action::make('markAsPaid')
            ->label('تسديد كامل')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->disabled(fn($record) => $record->has_newer_unpaid > 0)
            ->requiresConfirmation()
            ->modalHeading('تسديد الفاتورة بالكامل')
            ->modalDescription(function ($record) {
                $remainingAmount = $record->amount_due_without_credit;

                return 'سيتم تسجيل دفعة بقيمة: ' .
                    number_format($remainingAmount, 2) .
                    ' ج.م لإتمام سداد الفاتورة';
            })
            ->schema([
                ClientDatetimeHidden::make('created_at'),
            ])
            ->modalSubmitActionLabel('تسديد الآن')
            ->action(function ($record, $data) {
                DB::transaction(function () use ($record, $data) {
                    // حساب المبلغ المتبقي
                    $remainingAmount = $record->amount_due_without_credit;
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
                            'status' => 'paid',
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
            ->visible(fn($record) => $record->status === 'partial');
    }
}
