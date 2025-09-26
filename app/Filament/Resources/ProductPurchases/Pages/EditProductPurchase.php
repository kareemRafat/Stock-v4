<?php

namespace App\Filament\Resources\ProductPurchases\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProductPurchases\ProductPurchaseResource;

class EditProductPurchase extends EditRecord
{
    protected static string $resource = ProductPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
        ];
    }
}
