<?php

namespace App\Filament\Resources\OutsourcedProductions\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\OutsourcedProductions\OutsourcedProductionResource;

class EditOutsourcedProduction extends EditRecord
{
    protected static string $resource = OutsourcedProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
        ];
    }
}
