<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('الإسم بالعربي')
                ->required()
                ->rule(['regex:/^[\p{Arabic}\s]+$/u', 'required'])
                ->unique(ignoreRecord: true)
                ->columnSpanFull(),

            TextInput::make('username')
                ->label('إسم الدخول باللغة الإنجليزية')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText("الإسم مطلوب لعملية تسجيل الدخول")
                ->afterStateHydrated(fn($component, $state) => $component->state(strtolower($state)))
                ->dehydrateStateUsing(fn($state) => strtolower($state)),

            Select::make('role')
                ->label('الوظيفة')
                ->native(false)
                ->options(
                    collect(\App\Enums\UserRole::cases())->mapWithKeys(fn($case) => [
                        $case->value => match ($case->name) {
                            'ADMIN' => 'ادمن',
                            'EMPLOYEE' => 'موظف',
                            default => $case->name,
                        }
                    ])->toArray()
                )
                ->required(),

            TextInput::make('password')
                ->label('الباسورد')
                ->password()
                ->revealable()
                ->helperText(
                    fn($record) => $record && $record->exists
                        ? 'فى حالة عدم الرغبة فى تعديل الباسورد يرجى ترك الحقل فارغاً'
                        : null
                )
                ->required(fn($record) => ! $record || ! $record->exists)
                ->rule(
                    fn($record) => $record && $record->exists
                        ? ['nullable', 'confirmed', 'min:8']
                        : ['required', 'confirmed', 'min:8']
                )
                ->dehydrated(fn($state) => filled($state))
                ->dehydrateStateUsing(fn($state) => filled($state) ? \Illuminate\Support\Facades\Hash::make($state) : null),

            TextInput::make('password_confirmation')
                ->label('تـاكـيد الـباسورد')
                ->password()
                ->revealable()
                ->required(fn($record) => ! $record || ! $record->exists)
                ->rule(
                    fn($record) => $record && $record->exists
                        ? ['required_with:password']
                        : ['required']
                )
                ->dehydrated(false),
        ]);
    }
}
