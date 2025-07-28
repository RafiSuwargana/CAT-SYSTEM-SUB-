<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemParameter extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'a',
        'b', 
        'g',
        'u'
    ];

    protected $casts = [
        'a' => 'decimal:6',
        'b' => 'decimal:6',
        'g' => 'decimal:6',
        'u' => 'decimal:6',
    ];

    /**
     * Get all responses for this item
     */
    public function responses(): HasMany
    {
        return $this->hasMany(TestResponse::class, 'item_id', 'id');
    }

    /**
     * Get all used_items for this item
     */
    public function usedItems(): HasMany
    {
        return $this->hasMany(UsedItem::class, 'item_id', 'id');
    }
}
