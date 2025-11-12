<?php

namespace App\Filament\Resources\OutsourcedProductions\Pages;

use App\Filament\Resources\OutsourcedProductions\OutsourcedProductionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOutsourcedProduction extends EditRecord
{
    protected static string $resource = OutsourcedProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => ! Auth::user()->isAdmin()),
        ];
    }
}
