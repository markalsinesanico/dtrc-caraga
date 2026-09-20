<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRegistrationHistory extends Model
{
    protected $table = 'inventory_registration_histories';

    protected $fillable = [
        'inventory_id',
        'property_no',
        'name',
        'category',
        'unit',
        'registered_qty',
        'reorder_level',
        'unit_cost',
        'image',
        'registered_date',
        'registered_at',
    ];

    protected $casts = [
        'registered_qty' => 'integer',
        'reorder_level' => 'integer',
        'unit_cost' => 'decimal:2',
        'registered_date' => 'date',
        'registered_at' => 'datetime',
    ];

    /**
     * The original inventory record, if it still exists.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}