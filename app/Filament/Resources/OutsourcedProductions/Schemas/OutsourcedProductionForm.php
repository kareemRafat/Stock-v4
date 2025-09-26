<?php

namespace App\Filament\Resources\OutsourcedProductions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;

class OutsourcedProductionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('product_name')
                    ->label('اسم الصنف')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('factory_name')
                    ->label('اسم المصنع')
                    ->required()
                    ->maxLength(255),

                TextInput::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->required(),

                TextInput::make('size')
                    ->label('المقاس')
                    ->required()
                    ->maxLength(100),

                TextInput::make('total_cost')
                    ->label('التكلفة')
                    ->numeric()
                    ->suffix('ج.م'),

                DatePicker::make('start_date')
                    ->label('تاريخ البدء')
                    ->displayFormat('d-m-Y')
                    ->required()
                    ->native(false),

                DatePicker::make('actual_delivery_date')
                    ->label('تاريخ التسليم')
                    ->displayFormat('d-m-Y')
                    ->native(false),

                ToggleButtons::make('status')
                    ->label('الحالة')
                    ->options([
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'canceled' => 'ملغي',
                    ])
                    ->colors([
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'canceled' => 'danger',
                    ])
                    ->icons([
                        'in_progress' => 'heroicon-o-cog',
                        'completed' => 'heroicon-o-check-circle',
                        'canceled' => 'heroicon-o-x-circle',
                    ])
                    ->inline()
                    ->required()
                    ->default('in_progress'),
            ]);
    }
}
