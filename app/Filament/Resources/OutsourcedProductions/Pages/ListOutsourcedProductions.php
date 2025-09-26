<?php

namespace App\Filament\Resources\OutsourcedProductions\Pages;

use App\Filament\Resources\OutsourcedProductions\OutsourcedProductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutsourcedProductions extends ListRecords
{
    protected static string $resource = OutsourcedProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->slideOver()
            ->createAnother(false),
        ];
    }
}
