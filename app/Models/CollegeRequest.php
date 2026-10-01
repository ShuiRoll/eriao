<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeRequest extends Model
{
    protected $fillable = [
        'reference_number',
        'college_name',
        'subject',
        'recipient_name',
        'letter_received_at',
        'notes',
        'status',
        'recorded_by',
        'signed_by',
        'signed_at',
        'released_by',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'letter_received_at' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollegeRequestItem::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CollegeRequestActivity::class)->latest('created_at');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
