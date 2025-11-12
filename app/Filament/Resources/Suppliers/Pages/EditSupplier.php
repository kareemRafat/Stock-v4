<?php

namespace App\Filament\Resources\Suppliers\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Suppliers\SupplierResource;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription(' هل أنت متأكد من القيام بهذه العملية ؟ سيتم حذف جميع سجلات الارصدة للمورد')
                ->hidden(fn() => ! Auth::user()->isAdmin()),
        ];
    }
}
