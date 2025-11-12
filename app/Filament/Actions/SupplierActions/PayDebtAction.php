<?php

namespace App\Filament\Actions\SupplierActions;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use App\Models\SupplierWallet;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class PayDebtAction
{
    public static function make(): Action
    {
        return Action::make('PayDebt')
            ->label('سداد مديونية')
            ->modalSubmitActionLabel('سداد مديونية')
            ->modalHeading(
                fn (Model $record) => new HtmlString('سداد مديونية المورد : '."<span style='color: #3b82f6 !important;font-weight:bold'>{$record->name}</span>")
            )
            ->disabled(fn ($record): bool => $record->balance < 0 || $record->balance == 0)
            ->color(fn ($record): string => $record->balance < 0 || $record->balance == 0 ? 'gray' : 'warning')
            ->schema([
                TextEntry::make('available_debt')
                    ->label('المديونية الحالية')
                    ->state(fn ($record) => $record->balance)
                    ->formatStateUsing(function ($state) {
                        if ($state > 0) {
                            return number_format($state, 2).' ج.م';
                        }

                        return 'لا توجد مديونية للعميل';
                    })
                    ->color(fn ($state) => $state > 0 ? 'rose' : 'indigo')
                    ->weight('semibold'),

                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required()
                    ->rules(['required', 'numeric', 'min:0.01'])
                    ->minValue(0.01)
                    ->step(0.01),

                Forms\Components\Textarea::make('note')
                    ->label('ملاحظات الإضافة')
                    ->placeholder('أدخل ملاحظات حول عملية إضافة الرصيد ان وجد')
                    ->columnSpanFull()
                    ->autosize()
                    ->maxLength(500),

                ClientDatetimeHidden::make('created_at'),
            ])
            ->action(function (array $data, Model $record): void {
                // check balance if the transaction (Debit)
                if (($data['amount'] ?? 0) <= 0) {
                    Notification::make()
                        ->title('خطأ في المبلغ')
                        ->body('المبلغ يجب أن يكون أكبر من الصفر')
                        ->danger()
                        ->send();

                    return;
                }

                // create Transaction in wallet
                SupplierWallet::create([
                    'supplier_id' => $record->id,
                    'type' => 'debt_payment', // سداد مديونية
                    'amount' => $data['amount'],
                    'note' => $data['note'] ?? 'سداد مديونية مباشرة',
                    'created_at' => $data['created_at'] ?? now(),
                ]);

                // Notifications
                $remainingBalance = max(0, $record->balance);
                Notification::make()
                    ->title('تم السداد بنجاح')
                    ->body('تم سداد '.number_format($data['amount'], 2).' ج.م. المتبقي من الدين : '.number_format($remainingBalance, 2).' ج.م')
                    ->success()
                    ->send();
            })
            ->after(function (Model $record, Action $action) {
                if ($record->relationLoaded('wallet')) {
                    $record->load('wallet');
                }

                // to reload livewire wallet page component when add pay
                $action->getLivewire()->dispatch('refresh-wallet');
            })
            ->outlined()
            ->extraAttributes(['class' => 'font-semibold'])
            ->icon('heroicon-s-clipboard-document-check')
            ->modalWidth('3xl')
            ->modalCancelActionLabel('إلغاء');
    }
}
