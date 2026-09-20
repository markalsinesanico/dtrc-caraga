<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $fillable = [
        'property_no',
        'name',
        'category',
        'unit',
        'quantity',
        'reorder_level',
        'unit_cost',
        'image',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reorder_level' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Registration history belonging to this inventory item.
     *
     * Important:
     * Updating inventory.quantity does NOT update history.
     */
    public function registrationHistories(): HasMany
    {
        return $this->hasMany(InventoryRegistrationHistory::class);
    }
}