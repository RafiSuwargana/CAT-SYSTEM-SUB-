<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSession extends Model
{
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'session_id',
        'theta',
        'standard_error',
        'test_completed',
        'stop_reason',
        'final_score'
    ];

    protected $casts = [
        'theta' => 'decimal:6',
        'standard_error' => 'decimal:6',
        'test_completed' => 'boolean',
        'final_score' => 'decimal:2',
    ];

    /**
     * Get all responses for this session
     */
    public function responses(): HasMany
    {
        return $this->hasMany(TestResponse::class, 'session_id', 'session_id')
                    ->orderBy('item_order');
    }

    /**
     * Get all used items for this session
     */
    public function usedItems(): HasMany
    {
        return $this->hasMany(UsedItem::class, 'session_id', 'session_id');
    }
}
