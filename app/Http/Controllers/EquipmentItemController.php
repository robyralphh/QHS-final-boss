<?php

namespace App\Http\Controllers;

use App\Http\Resources\EquipmentItemResource;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\EquipmentItem;
use App\Models\Equipment;
use App\Traits\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EquipmentItemController extends Controller
{
    use ActionLogger;
    /**
     * Display a listing of all items (optional)
     */
    public function index()
    {
        $items = EquipmentItem::orderBy('id', 'desc')->get();
        return EquipmentItemResource::collection($items);
    }

    /**
     * Store a newly created item
     */
    public function store(StoreItemRequest $request)
    {
        $data = $request->validated();

        // Critical: Verify that the equipment actually exists
        $equipment = Equipment::find($data['equipment_id']);

        if (!$equipment) {
            return response()->json([
                'message' => 'The selected equipment does not exist.',
                'errors' => [
                    'equipment_id' => ['Invalid equipment selected.']
                ]
            ], 422);
        }

        // Custodian restriction: can only add items to their assigned laboratory's equipment
        if (auth()->user()->role === 'custodian') {
            $lab = \App\Models\Laboratory::where('custodianID', auth()->id())->first();
            if (!$lab || $equipment->laboratory_id !== $lab->id) {
                return response()->json(['message' => 'Unauthorized: equipment belongs to a different laboratory.'], 403);
            }
        }

        // Safely generate unit_id
        try {
            $data['unit_id'] = EquipmentItem::generateUnitId($data['equipment_id']);
        } catch (\Exception $e) {
            Log::error('Failed to generate unit_id for equipment_id: ' . $data['equipment_id'], [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to generate unit ID. Please try again.'
            ], 500);
        }

        // Create the item
        $item = EquipmentItem::create($data);

        $this->logAction('equipment_item_created', ['item_id' => $item->id, 'equipment_id' => $item->equipment_id]);

        return response()->json(
            new EquipmentItemResource($item),
            201
        );
    }

    /**
     * Display the specified item
     */
    public function show($id)
    {
        $item = EquipmentItem::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Equipment item not found'
            ], 404);
        }

        return new EquipmentItemResource($item);
    }

    /**
     * Update the specified item
     */
    public function update(UpdateItemRequest $request, $id)
    {
        $item = EquipmentItem::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Equipment item not found'
            ], 404);
        }

        // Custodian restriction: can only edit items belonging to their assigned laboratory's equipment
        if (auth()->user()->role === 'custodian') {
            $equipment = Equipment::find($item->equipment_id);
            $lab = \App\Models\Laboratory::where('custodianID', auth()->id())->first();
            if (!$lab || !$equipment || $equipment->laboratory_id !== $lab->id) {
                return response()->json(['message' => 'Unauthorized: equipment belongs to a different laboratory.'], 403);
            }
        }

        $data = $request->validated();
        $item->update($data);

        $this->logAction('equipment_item_updated', ['item_id' => $item->id]);

        return new EquipmentItemResource($item);
    }

    /**
     * Remove the specified item
     */
    public function destroy($id)
    {
        $item = EquipmentItem::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Equipment item not found'
            ], 404);
        }

        $item->delete();

        $this->logAction('equipment_item_deleted', ['item_id' => $item->id]);

        return response()->json(null, 204);
    }

    /**
     * Get available (not borrowed) items for a specific equipment
     * Used in borrowing/return forms
     */
   public function availableItems(Equipment $equipment)
    {
        $items = EquipmentItem::where('equipment_id', $equipment->id)
            ->where('isBorrowed', false)
            ->whereIn('condition', ['New', 'Good', 'Fair', 'Poor'])
            ->select('id', 'unit_id', 'condition')
            ->orderBy('unit_id')
            ->get();

        return response()->json([
            'data' => $items
        ]);
    }
}