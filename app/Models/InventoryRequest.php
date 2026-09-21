<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRequest extends Model
{
    use HasFactory;

    protected $table = 'inventory_requests';

    protected $fillable = [
        'inventory_id',
        'requestor_name',
        'department_office',
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