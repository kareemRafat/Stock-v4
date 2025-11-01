<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null) // prevent clickable row
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('الإسم بالعربي')
                    ->searchable()
                    ->fontFamily(FontFamily::Sans)
                    ->extraAttributes(['class' => 'text-violet-600'])
                    ->weight(FontWeight::Medium),

                TextColumn::make('username')
                    ->label('إسم الدخول')
                    ->fontFamily(FontFamily::Sans)
                    ->searchable(),

                TextColumn::make('role')
                    ->label('الوظيفة')
                    ->formatStateUsing(fn ($state, $record) => $record->role->name === 'ADMIN' ? 'ادمن' : 'موظف')
                    ->weight(FontWeight::Medium),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'مفعل' : 'غير مفعل'),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date('d-m-Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
