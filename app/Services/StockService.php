<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public $input = ['opening_stock', 'purchase', 'sale_return', 'adjustment_in'];
    public $output = ['invoice_sale', 'purchase_return', 'adjustment_out'];

    /**
     * add Stock Movement to StockMovement and update products stock
     */
    public function recordMovement(
        Product $product,
        string $movementType,
        int $quantity,
        ?float $costPrice = null,
        ?float $wholeSalePrice = null,
        ?float $retailPrice = null,
        ?float $discount = null,
        $referenceId = null,
        $referenceTable = null,
        $createdAt  = null
    ) {
        return DB::transaction(
            function ()
            use ($product, $movementType, $quantity, $costPrice, $wholeSalePrice, $retailPrice, $referenceId, $referenceTable, $discount, $createdAt) {

                // dd($wholeSalePrice, $retailPrice);

                // if the operation is insert input
                if (in_array($movementType, $this->input)) {
                    $product->increment('stock_quantity', $quantity);
                    $qtyIn = $quantity;
                    $qtyOut = 0;
                }
                // if the operation is output
                else {
                    $product->decrement('stock_quantity', $quantity);
                    $qtyIn = 0;
                    $qtyOut = $quantity;
                }

                if ($discount !== null && $wholeSalePrice !== null) {
                    $wholeSalePrice = $wholeSalePrice - ($wholeSalePrice * ($discount / 100));
                }

                // add stock_movements table Record
                return StockMovement::create([
                    'product_id'       => $product->id,
                    'movement_type'    => $movementType,
                    'qty_in'           => $qtyIn,
                    'qty_out'          => $qtyOut,
                    'cost_price'       => $costPrice ?? $product->cost_price,
                    'wholesale_price'  => $wholeSalePrice,
                    'retail_price'     => $retailPrice,
                    'reference_id'     => $referenceId,
                    'reference_table'  => $referenceTable,
                    'created_at'       => $createdAt ?? $product->created_at
                ]);
            }
        );
    }
}
