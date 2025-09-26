<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutsourcedProduction extends Model
{
    /** @use HasFactory<\Database\Factories\OutsourcedProductionFactory> */
    use HasFactory;

    protected $fillable = [
        'product_name',
        'factory_name',
        'quantity',
        'size',
        'total_cost',
        'start_date',
        'actual_delivery_date',
        'status',
        'notes',
    ];
}
