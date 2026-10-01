<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeRequestItem extends Model
{
    protected $fillable = [
        'college_request_id',
        'product_id',
        'quantity',
    ];

    public function collegeRequest(): BelongsTo
    {
        return $this->belongsTo(CollegeRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class, 'product_id');
    }
}
