<?php

namespace App\Filament\Resources\ProductPurchases\Pages;

use App\Filament\Resources\ProductPurchases\ProductPurchaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditProductPurchase extends EditRecord
{
    protected static string $resource = ProductPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => ! Auth::user() || Auth::user()->role->value !== 'admin'),
        ];
    }
}
