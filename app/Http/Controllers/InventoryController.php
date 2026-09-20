<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display all inventory items.
     */
    public function index()
    {
        return response()->json(
            Inventory::orderBy('name')->get()
        );
    }

    /**
     * Store a new inventory item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_no' => 'required|string|max:255|unique:inventories,property_no',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'image' => 'nullable|string',
        ]);

        $inventory = Inventory::create($validated);

        return response()->json([
            'message' => 'Inventory item created successfully.',
            'data' => $inventory,
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
     * Update an inventory item.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'property_no' => 'required|string|max:255|unique:inventories,property_no,' . $inventory->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'image' => 'nullable|string',
        ]);

        $inventory->update($validated);

        return response()->json([
            'message' => 'Inventory item updated successfully.',
            'data' => $inventory->fresh(),
        ]);
    }

    /**
     * Delete an inventory item.
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully.',
        ]);
    }
}