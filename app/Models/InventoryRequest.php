<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRequest extends Model
{
    protected $fillable = [
        'inventory_id',
        'requestor_name',
        'department_office',
        'reference_no_source',
        'property_no',
        'item_name',
        'unit',
        'requested_quantity',
        'request_date',
        'requested_at',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'request_date' => 'date',
        'requested_at' => 'datetime',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
