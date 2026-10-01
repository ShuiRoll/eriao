<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItems extends Model
{
    protected $table = "product_order_items";
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'unit_price',
    ];
}
