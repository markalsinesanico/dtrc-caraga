<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryRegistrationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display all current inventory items.
     */
    public function index()
    {
        return response()->json(
            Inventory::orderBy('name')->get()
        );
    }

    /**
     * Display inventory registration history for a specific date.
     *
     * IMPORTANT:
     * This reads registered_qty from the history table.
     * It NEVER reads quantity from inventories.
     */
    public function registrationHistory(Request $request)
    {
        $validated = $request->validate([
            'date' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],
        ]);

        $history = InventoryRegistrationHistory::query()
            ->where('registered_date', $validated['date'])
            ->orderBy('registered_at')
            ->orderBy('id')
            ->get();

        return response()->json(
            $history->map(function (InventoryRegistrationHistory $item) {
                return [
                    'id' => $item->id,

                    /*
                    |--------------------------------------------------------------------------
                    | Historical values
                    |--------------------------------------------------------------------------
                    */
                    'property_no' => $item->property_no,
                    'name' => $item->name,
                    'category' => $item->category,
                    'unit' => $item->unit,

                    /*
                    |--------------------------------------------------------------------------
                    | CRITICAL
                    |--------------------------------------------------------------------------
                    |
                    | This is the original quantity recorded during registration.
                    | It is independent from inventories.quantity.
                    |
                    */
                    'registered_qty' => $item->registered_qty,

                    'reorder_level' => $item->reorder_level,
                    'unit_cost' => $item->unit_cost,
                    'image' => $item->image,

                    'registered_date' => $item->registered_date?->format('Y-m-d'),
                    'registered_at' => $item->registered_at?->toISOString(),
                    'created_at' => $item->created_at?->toISOString(),
                ];
            })
        );
    }

    /**
     * Store a new inventory item.
     *
     * A separate immutable registration history record is created
     * at the exact same time.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_no' => [
                'required',
                'string',
                'max:255',
                'unique:inventories,property_no',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'string',
                'max:255',
            ],

            'unit' => [
                'required',
                'string',
                'max:255',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'reorder_level' => [
                'required',
                'integer',
                'min:1',
            ],

            'unit_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'image' => [
                'nullable',
                'string',
            ],
        ]);

        $result = DB::transaction(function () use ($validated) {
            /*
            |--------------------------------------------------------------------------
            | Create current inventory
            |--------------------------------------------------------------------------
            */
            $inventory = Inventory::create($validated);

            /*
            |--------------------------------------------------------------------------
            | Create immutable registration history snapshot
            |--------------------------------------------------------------------------
            |
            | registered_qty is copied from the quantity entered at registration.
            |
            */
            $registeredAt = now();

            InventoryRegistrationHistory::create([
                'inventory_id' => $inventory->id,

                // Snapshot values
                'property_no' => $inventory->property_no,
                'name' => $inventory->name,
                'category' => $inventory->category,
                'unit' => $inventory->unit,

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | Never change this when inventory.quantity changes later.
                |--------------------------------------------------------------------------
                */
                'registered_qty' => $inventory->quantity,

                'reorder_level' => $inventory->reorder_level,
                'unit_cost' => $inventory->unit_cost,
                'image' => $inventory->image,

                'registered_date' => $registeredAt
                    ->timezone(config('app.timezone', 'Asia/Manila'))
                    ->toDateString(),

                'registered_at' => $registeredAt,
            ]);

            return $inventory->fresh();
        });

        return response()->json([
            'message' => 'Inventory item registered successfully.',
            'data' => $result,
        ], 201);
    }

    /**
     * Display one inventory item.
     */
    public function show(Inventory $inventory)
    {
        return response()->json($inventory);
    }

    /**
     * Update current inventory.
     *
     * IMPORTANT:
     * This updates only the current inventory record.
     *
     * It does NOT update InventoryRegistrationHistory.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'property_no' => [
                'required',
                'string',
                'max:255',
                'unique:inventories,property_no,' . $inventory->id,
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'string',
                'max:255',
            ],

            'unit' => [
                'required',
                'string',
                'max:255',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'reorder_level' => [
                'required',
                'integer',
                'min:1',
            ],

            'unit_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'image' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update ONLY current inventory
        |--------------------------------------------------------------------------
        |
        | The registration history record is intentionally NOT updated.
        |
        */
        $inventory->update($validated);

        return response()->json([
            'message' => 'Inventory item updated successfully.',
            'data' => $inventory->fresh(),
        ]);
    }

    /**
     * Delete current inventory item.
     *
     * Registration history remains because the history table uses
     * nullOnDelete() for inventory_id.
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully.',
        ]);
    }
}