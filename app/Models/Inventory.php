<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $fillable = [
        'iar_number',
        'property_no',
        'name',
        'category',
        'unit',
        'quantity',
        'reorder_level',
        'image',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reorder_level' => 'integer',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(InventoryRequest::class);
    }

    public function registrationHistories(): HasMany
    {
        return $this->hasMany(InventoryRegistrationHistory::class);
    }
}
