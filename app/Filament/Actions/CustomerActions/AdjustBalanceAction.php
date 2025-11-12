<?php

namespace App\Filament\Actions\CustomerActions;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class AdjustBalanceAction
{
    public static function make(): Action
    {
        return Action::make('adjustBalance')
            ->label('إضافة رصيد')
            ->modalSubmitActionLabel('إضافة رصيد')
            ->modalHeading(
                fn (Model $record) => new HtmlString('إضافة رصيد العميل: '."<span style='color: #3b82f6 !important;font-weight:bold'>{$record->name}</span>")
            )
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required()
                    ->rules(['required', 'numeric', 'min:0.01'])
                    ->minValue(0.01)
                    ->step(0.01),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات الإضافة')
                    ->placeholder('أدخل ملاحظات حول عملية إضافة الرصيد')
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
                $record->wallet()->create([
                    'type' => 'adjustment',
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? 'اضافة رصيد يدوي ',
                    'created_at' => $data['created_at'],
                ]);

                Notification::make()
                    ->title('تمت إضافة الرصيد بنجاح')
                    ->body('تم إضافة '.number_format($data['amount'], 2).' إلى رصيد العميل')
                    ->success()
                    ->send();
            })
            ->after(function (Model $record, Action $action) {
                if ($record->relationLoaded('wallet')) {
                    $record->load('wallet');
                }

                $action->getLivewire()->dispatch('refresh-wallet');
            })
            ->color('warning')
            ->outlined()
            ->extraAttributes(['class' => 'font-semibold'])
            ->icon('heroicon-s-clipboard-document-check')
            ->modalWidth('3xl')
            ->modalCancelActionLabel('إلغاء');
    }
}
