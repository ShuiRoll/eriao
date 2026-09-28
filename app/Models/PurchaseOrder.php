<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = "purchase_order";
    protected $fillable = [
        'requested_by',
        'approved_by',
        'quantity',
        'total_amount',
        'status',
    ];
}
