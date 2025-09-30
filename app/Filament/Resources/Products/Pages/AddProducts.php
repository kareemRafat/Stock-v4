<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Resources\Products\ProductResource;
use Filament\Forms\Components\Textarea;

class AddProducts extends Page
{
    use InteractsWithForms;

    protected static string $resource = ProductResource::class;

    protected string $view = 'filament.pages.products.add-products';

    protected static ?string $title = 'إضافة منتجات';

    protected static ?string $breadcrumb = 'إضافة منتجات';

    public array $data = [];

    public function mount(): void
    {
        // initial 3 cards
        $this->form->fill([
            'products' => array_fill(0, 1, [
                'name' => '',
                'unit' => '',
                'production_price' => 0,
                'discount' => 0,
                'stock_quantity' => 0,
                'description' => '',
            ]),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Repeater::make('products')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('اسم المنتج'),

                        TextInput::make('unit')
                            ->label('وحدة القياس')
                            ->required()
                            ->placeholder('كرتونة - قطعة - كيلو'),

                        TextInput::make('production_price')
                            ->required()
                            ->label('سعر المصنع')
                            ->numeric()
                            ->suffix('جنيه'),

                        TextInput::make('discount')
                            ->required()
                            ->numeric()
                            ->label('الخصم')
                            ->suffix('%')
                            ->default(0),

                        TextInput::make('stock_quantity')
                            ->label('الكمية بالمخزن')
                            ->required()
                            ->numeric()
                            ->default(0),

                        Select::make('supplier_id')
                            ->label('المورد')
                            ->helperText('يمكن عدم إختيار مورد فى حالة عدم وجود مورد')
                            ->searchable()
                            ->options(function () {
                                return Supplier::limit(10)->pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(fn($value) => Supplier::find($value)?->name)
                            ->getSearchResultsUsing(function ($search) {
                                return Supplier::where('name', 'like', "%{$search}%")
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),

                        Textarea::make('description')
                            ->label('الوصف')
                            ->columnSpanFull(),
                    ]),
                ])
                ->label('منتجات جديدة')
                ->collapsible()
                ->columnSpanFull()
                ->itemLabel(fn(array $state): ?string => $state['name'] ?: 'منتج جديد'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data['products'] as $product) {
            $product['created_at'] = now();
            Product::create($product);
        }

        Notification::make()
            ->title('تم إضافة المنتجات بنجاح')
            ->success()
            ->send();

        $this->redirect(ProductResource::getUrl('index'));
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
