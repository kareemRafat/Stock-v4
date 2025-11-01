<?php

namespace App\Filament\Resources\ReturnInvoices\Pages;

use App\Filament\Resources\ReturnInvoices\ReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnInvoice extends ViewRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    protected static ?string $title = 'عرض فاتورة المرتجع';

    protected string $view = 'filament.pages.ReturnInvoices.view-return-invoice';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                // Refresh the table on index page
                ->extraAttributes(['wire:navigate' => true]) // very important
                ->url(ReturnInvoiceResource::getUrl('index')),
        ];
    }
}
