<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}