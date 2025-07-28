<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResponse extends Model
{
    protected $fillable = [
        'session_id',
        'item_id',
        'answer',
        'theta_before',
        'theta_after',
        'se_after',
        'item_order',
        'probability',
        'information',
        'expected_fisher_information'
    ];

    protected $casts = [
        'answer' => 'integer',
        'theta_before' => 'decimal:6',
        'theta_after' => 'decimal:6',
        'se_after' => 'decimal:6',
        'item_order' => 'integer',
        'probability' => 'decimal:6',
        'information' => 'decimal:6',
        'expected_fisher_information' => 'decimal:6',
    ];

    /**
     * Get the session for this response
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'session_id', 'session_id');
    }

    /**
     * Get the item for this response
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemParameter::class, 'item_id', 'id');
    }
}
