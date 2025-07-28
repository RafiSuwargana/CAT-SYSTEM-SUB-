<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsedItem extends Model
{
    protected $fillable = [
        'session_id',
        'item_id'
    ];

    /**
     * Get the session for this used item
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'session_id', 'session_id');
    }

    /**
     * Get the item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemParameter::class, 'item_id', 'id');
    }
}
