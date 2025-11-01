<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('إسم العميل')
                    ->unique(ignoreRecord: true)
                    ->rules(['required', 'string', 'max:255'])
                    ->required(),
                ToggleButtons::make('status')
                    ->label('الحالة')
                    ->options([
                        'enabled' => 'مفعل',
                        'disabled' => 'معطل',
                    ])
                    ->colors([
                        'enabled' => 'success',
                        'disabled' => 'danger',
                    ])
                    ->icons([
                        'enabled' => 'heroicon-o-check-circle',
                        'disabled' => 'heroicon-o-x-circle',
                    ])
                    ->inline()
                    ->required()
                    ->default('enabled'),
                TextInput::make('phone')
                    ->tel()
                    ->label('رقم التواصل')
                    ->rules(['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'])
                    ->required()
                    ->helperText('رقم التواصل مطلوب لعملية تسجيل العميل'),
                TextInput::make('phone2')
                    ->tel()
                    ->rules(['nullable', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'])
                    ->label('رقم احتياطي')
                    ->helperText('يمكن ترك الحقل فارغاً'),
                Textarea::make('address')
                    ->label('العنوان')
                    ->rules(['required', 'string', 'max:1000'])
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('city')
                    ->label('المدينة')
                    ->rules(['required', 'string', 'max:255'])
                    ->required(),
                TextInput::make('governorate')
                    ->label('المحافظة')
                    ->required()
                    ->rules(['required', 'string', 'max:255']),
            ]);
    }
}
