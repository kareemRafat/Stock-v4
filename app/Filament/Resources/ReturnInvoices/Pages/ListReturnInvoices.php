<?php

namespace App\Filament\Resources\ReturnInvoices\Pages;

use App\Filament\Resources\ReturnInvoices\ReturnInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnInvoices extends ListRecords
{
    protected static string $resource = ReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
