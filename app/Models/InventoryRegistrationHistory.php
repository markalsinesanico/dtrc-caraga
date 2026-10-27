<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRegistrationHistory extends Model
{
    protected $table = 'inventory_registration_histories';

    protected $fillable = [
        'inventory_id',
        'iar_number',
        'property_no',
        'name',
        'description',
        'category',
        'unit',
        'registered_qty',
        'reorder_level',
        'image',
        'registered_date',
        'registered_at',
    ];

    protected $casts = [
        'registered_qty' => 'integer',
        'reorder_level' => 'integer',
        'registered_date' => 'date',
        'registered_at' => 'datetime',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}