<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $table = 'product_items';
    protected $fillable = [
        'name',
        'category_id',
        'quantity',
        'max',
        'price',
        'status',
    ];
}
