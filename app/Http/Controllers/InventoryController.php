<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryRegistrationHistory;
use App\Models\InventoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        return response()->json(
            Inventory::orderBy('name')->get()
        );
    }

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
            ->whereDate(
                'registered_date',
                $validated['date']
            )
            ->orderBy('registered_at')
            ->orderBy('id')
            ->get();

        return response()->json(
            $history->map(function (
                InventoryRegistrationHistory $item
            ) {
                return [
                    'id' => $item->id,
                    'property_no' => $item->property_no,
                    'name' => $item->name,
                    'category' => $item->category,
                    'unit' => $item->unit,
                    'registered_qty' => $item->registered_qty,
                    'reorder_level' => $item->reorder_level,
                    'unit_cost' => $item->unit_cost,
                    'image' => $item->image,
                    'registered_date' =>
                        $item->registered_date?->format('Y-m-d'),
                    'registered_at' =>
                        $item->registered_at?->toISOString(),
                    'created_at' =>
                        $item->created_at?->toISOString(),
                ];
            })
        );
    }

    public function requestHistory(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Request History Date
        |--------------------------------------------------------------------------
        |
        | date is OPTIONAL.
        |
        | /api/inventory/request-history?date=2026-09-21
        |     -> returns only requests for September 21, 2026.
        |
        | /api/inventory/request-history
        |     -> returns ALL request records.
        |
        | The second behavior is used by the Print button.
        |
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'date' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],
        ]);

        $query = InventoryRequest::query();

        /*
        |--------------------------------------------------------------------------
        | Filter by Date Only When a Date Was Supplied
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['date'])) {
            $query->whereDate(
                'request_date',
                $validated['date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ordering
        |--------------------------------------------------------------------------
        |
        | Newest request date first.
        | Then newest request time.
        | Then newest ID.
        |
        |--------------------------------------------------------------------------
        */

        $requests = $query
            ->orderByDesc('request_date')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Return Request Data
        |--------------------------------------------------------------------------
        */

        return response()->json(
            $requests->map(function (
                InventoryRequest $item
            ) {
                return [
                    'id' => $item->id,

                    'inventory_id' =>
                        $item->inventory_id,

                    'requestor_name' =>
                        $item->requestor_name,

                    'department_office' =>
                        $item->department_office,

                    'property_no' =>
                        $item->property_no,

                    'item_name' =>
                        $item->item_name,

                    'unit' =>
                        $item->unit,

                    'requested_quantity' =>
                        $item->requested_quantity,

                    'request_date' =>
                        $item->request_date?->format('Y-m-d'),

                    'requested_at' =>
                        $item->requested_at?->toISOString(),

                    'created_at' =>
                        $item->created_at?->toISOString(),
                ];
            })
        );
    }

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
            $inventory = Inventory::create($validated);

            $registeredQuantity =
                (int) $validated['quantity'];

            InventoryRegistrationHistory::create([
                'inventory_id' =>
                    $inventory->id,

                'property_no' =>
                    $inventory->property_no,

                'name' =>
                    $inventory->name,

                'category' =>
                    $inventory->category,

                'unit' =>
                    $inventory->unit,

                'registered_qty' =>
                    $registeredQuantity,

                'reorder_level' =>
                    $inventory->reorder_level,

                'unit_cost' =>
                    $inventory->unit_cost,

                'image' =>
                    $inventory->image,

                'registered_date' =>
                    now()->toDateString(),

                'registered_at' =>
                    now(),
            ]);

            return $inventory->fresh();
        });

        return response()->json([
            'message' =>
                'Inventory item registered successfully.',

            'data' =>
                $result,
        ], 201);
    }

    public function requestItem(Request $request)
    {
        $validated = $request->validate([
            'requestor_name' => [
                'required',
                'string',
                'max:255',
            ],

            'department_office' => [
                'required',
                'string',
                'max:255',
            ],

            'inventory_id' => [
                'required',
                'integer',
                'exists:inventories,id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'request_date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $inventory = Inventory::lockForUpdate()
                ->findOrFail(
                    $validated['inventory_id']
                );

            $quantity =
                (int) $validated['quantity'];

            if ($quantity > $inventory->quantity) {
                abort(
                    422,
                    "Insufficient stock. Only {$inventory->quantity} {$inventory->unit} available."
                );
            }

            $inventoryRequest =
                InventoryRequest::create([
                    'inventory_id' =>
                        $inventory->id,

                    'requestor_name' =>
                        $validated['requestor_name'],

                    'department_office' =>
                        $validated['department_office'],

                    'property_no' =>
                        $inventory->property_no,

                    'item_name' =>
                        $inventory->name,

                    'unit' =>
                        $inventory->unit,

                    'requested_quantity' =>
                        $quantity,

                    'request_date' =>
                        $validated['request_date'],

                    'requested_at' =>
                        now(),
                ]);

            $inventory->quantity -= $quantity;

            $inventory->save();

            return [
                'request' =>
                    $inventoryRequest->fresh(),

                'inventory' =>
                    $inventory->fresh(),
            ];
        });

        return response()->json([
            'message' =>
                'Inventory request recorded successfully.',

            'data' =>
                $result,
        ], 201);
    }

    public function show(Inventory $inventory)
    {
        return response()->json(
            $inventory
        );
    }

    public function update(
        Request $request,
        Inventory $inventory
    ) {
        $validated = $request->validate([
            'property_no' => [
                'required',
                'string',
                'max:255',
                'unique:inventories,property_no,' .
                    $inventory->id,
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

        $inventory->update(
            $validated
        );

        return response()->json([
            'message' =>
                'Inventory item updated successfully.',

            'data' =>
                $inventory->fresh(),
        ]);
    }

    public function destroy(
        Inventory $inventory
    ) {
        $inventory->delete();

        return response()->json([
            'message' =>
                'Inventory item deleted successfully.',
        ]);
    }
}