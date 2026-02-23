<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Models\EquipmentItem;
use App\Models\Category;
use App\Models\Laboratory;
use App\Traits\ActionLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    use ActionLogger;

    /**
     * Full data for admin dashboard (cached)
     */
    public function data()
{
    $query = Equipment::with(['categories', 'items'])
        ->select('id', 'name', 'description', 'image', 'laboratory_id', 'isActive')
        ->where('isActive', true);
    
    // If user is custodian, filter by their laboratory
    if (auth()->user()->role === 'custodian') {
        $lab = Laboratory::where('custodianID', auth()->id())->first();
        if ($lab) {
            $query->where('laboratory_id', $lab->id);
        } else {
            return [
                'equipment' => [],
                'laboratories' => [],
                'categories' => [],
            ];
        }
    }
    
    $equipment = $query->orderByDesc('id')->limit(200)->get();

    $equipmentData = $equipment->map(function ($eq) {
        $items = $eq->items;

        $total       = $items->count();
        $borrowed    = $items->where('isBorrowed', true)->count();
        // Handle condition field - count items that are not in good condition
        $unavailable = $items->filter(function($item) {
            $condition = $item->condition ?? 'Good';
            // Unavailable if condition is Damaged, Missing, or Under Repair
            return in_array($condition, ['Damaged', 'Missing', 'Under Repair']);
        })->count();
        
        $available = $total - $borrowed - $unavailable;

        return [
            'id'                  => $eq->id,
            'name'                => $eq->name,
            'description'         => $eq->description ?? '',
            'image'               => $eq->image,
            'laboratory_id'       => $eq->laboratory_id,
            'isActive'            => (bool) $eq->isActive,

            'total_quantity'      => $total,
            'borrowed_quantity'   => $borrowed,
            'available_quantity'  => $available,
            'quantity'            => $available,

            'categories' => $eq->categories->map(fn($c) => [
                'id'   => $c->id,
                'name' => $c->name
            ])->values()->toArray(),

            'items' => $items->map(fn($i) => [
                'id'         => $i->id,
                'unit_id'    => $i->unit_id,
                'condition'  => $i->condition,
                'isBorrowed' => (bool) $i->isBorrowed,
            ])->values()->toArray(),
        ];
    });

    return [
        'equipment'    => $equipmentData->values()->toArray(),
        'laboratories' => Laboratory::select('id', 'name')->orderBy('name')->get(),
        'categories'   => Category::select('id', 'name')->orderBy('name')->get(),
    ];
}

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment)
    {
        // If user is custodian, verify they can access this equipment
        if (auth()->user()->role === 'custodian') {
            $lab = Laboratory::where('custodianID', auth()->id())->first();
            if (!$lab || $equipment->laboratory_id !== $lab->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        
        $equipment->loadMissing(['categories', 'items']);

        $items = $equipment->items;
        $total = $items->count();
        $borrowed = $items->where('isBorrowed', true)->count();
        // Handle condition field
        $unavailable = $items->filter(function($item) {
            $condition = $item->condition ?? null;
            // Unavailable if condition is Damaged, Missing, or Under Repair
            return in_array($condition, ['Damaged', 'Missing', 'Under Repair']);
        })->count();

        $equipment->available_quantity = $total - $borrowed - $unavailable;
        $equipment->total_quantity = $total;

        return new EquipmentResource($equipment);
    }

    /**
     * List all equipment (used in other places)
     */
    public function index(Request $request)
    {
        $query = Equipment::with(['categories', 'items']);
        
        // If user is custodian, filter by their laboratory
        if (auth()->user()->role === 'custodian') {
            $lab = Laboratory::where('custodianID', auth()->id())->first();
            if ($lab) {
                $query->where('laboratory_id', $lab->id);
            } else {
                return response()->json([], 200); // Return empty if no lab assigned
            }
        }
        
        // Allow filtering by laboratory_id
        if ($request->has('laboratory_id')) {
            $query->where('laboratory_id', $request->laboratory_id);
        }
        
        $equipment = $query->get();

        return EquipmentResource::collection($equipment->map(function ($eq) {
            $items = $eq->items;
            $total = $items->count();
            $borrowed = $items->where('isBorrowed', true)->count();
            // Handle condition field
            $unavailable = $items->filter(function($item) {
                $condition = $item->condition ?? null;
                // Unavailable if condition is Damaged, Missing, or Under Repair
                return in_array($condition, ['Damaged', 'Missing', 'Under Repair']);
            })->count();

            $eq->available_quantity = $total - $borrowed - $unavailable;
            $eq->total_quantity = $total;

            return $eq;
        }));
    }

    /**
     * Store a new equipment
     */
    public function store(StoreEquipmentRequest $request)
    {
        // Only admins can create equipment
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('itemImage', $filename, 'public');
            $data['image'] = $path;
        } else {
            $data['image'] = 'itemImage/No-image-default.png';
        }

        $equipment = Equipment::create($data);

        if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
            $equipment->categories()->sync($data['category_ids']);
        }

        Cache::forget('equipment_data');

        $this->logAction('equipment_created', ['equipment_id' => $equipment->id]);

        return new EquipmentResource($equipment->load('categories', 'items'));
    }

    /**
     * Update equipment
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        // Only admins can update equipment
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $data = $request->validated();

        // Handle image
        if ($request->hasFile('image')) {
            if ($equipment->image && $equipment->image !== 'itemImage/No-image-default.png') {
                Storage::disk('public')->delete($equipment->image);
            }
            $file = $request->file('image');
            $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('itemImage', $filename, 'public');
            $data['image'] = $path;
        } elseif ($request->has('remove_image')) {
            if ($equipment->image && $equipment->image !== 'itemImage/No-image-default.png') {
                Storage::disk('public')->delete($equipment->image);
            }
            $data['image'] = null;
        }

        $equipment->update($data);

        if ($request->has('category_ids')) {
            $equipment->categories()->sync($request->category_ids ?? []);
        }

        Cache::forget('equipment_data');

        $this->logAction('equipment_updated', ['equipment_id' => $equipment->id]);

        return new EquipmentResource($equipment->load('categories', 'items'));
    }

    /**
     * Delete equipment
     */
    public function destroy(Equipment $equipment)
    {
        // Only admins can delete equipment
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($equipment->image && $equipment->image !== 'itemImage/No-image-default.png') {
            Storage::disk('public')->delete($equipment->image);
        }

        $equipment->delete();
        Cache::forget('equipment_data');

        $this->logAction('equipment_deleted', ['equipment_id' => $equipment->id]);

        return response()->json(null, 204);
    }

    /**
 * Toggle equipment active status (Archive/Unarchive)
 */
    public function toggleActive(Equipment $equipment)
    {
        // Only admins can toggle equipment active status
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check if any items are borrowed
        $borrowedCount = $equipment->items()->where('isBorrowed', true)->count();

        if ($borrowedCount > 0) {
            return response()->json([
                'message' => "Cannot change status: {$borrowedCount} unit(s) are currently borrowed."
            ], 422);
        }

        $equipment->isActive = !$equipment->isActive;
        $equipment->save();

        Cache::forget('equipment_data');

        $this->logAction('equipment_toggled_active', ['equipment_id' => $equipment->id, 'isActive' => (bool)$equipment->isActive]);

        return response()->json([
            'message' => 'Status updated successfully',
            'isActive' => (bool) $equipment->isActive
        ]);
    }
}