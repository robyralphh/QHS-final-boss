<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\EquipmentItem;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use App\Events\TransactionUpdated;
use App\Traits\ActionLogger;

class TransactionController extends Controller
{
    use ActionLogger;
    /**
     * List all transactions with equipment summary (quantity = count of assigned units)
     */
   public function index(Request $request)
{
    $perPage = $request->get('per_page', 25);
    $page = $request->input('page', 1);
    $userId = auth()->id();
    $userRole = auth()->user()->role;

    return Cache::remember("transactions.page.{$page}.{$perPage}.{$userRole}.{$userId}", now()->addMinutes(3), function () use ($perPage, $page, $userId, $userRole) {
        \Log::info("TransactionController index called (fresh query) - page {$page}, perPage {$perPage}");

        // NOTE: We intentionally do NOT eager-load 'equipment' here.
        // The Transaction::equipment() BelongsToMany is keyed on equipment_item_id
        // (not equipment.id), so it returns wrong data. We build equipment from
        // the raw $assignedItems query below instead.
        $query = Transaction::with([
            'borrower:id,name,email',
            'laboratory:id,name',
        ])
            ->select('id', 'borrower_id', 'borrower_name', 'borrower_email', 'laboratory_id', 'borrow_date', 'return_date', 'status', 'notes', 'created_at', 'accepted_at', 'returned_at', 'rejected_at', 'accepted_by_name', 'returned_by_name', 'rejected_by_name', 'rejection_reason');

        // If user is custodian, filter by their laboratory
        if ($userRole === 'custodian') {
            $lab = \App\Models\Laboratory::where('custodianID', $userId)->first();
            if ($lab) {
                $query->where('laboratory_id', $lab->id);
            } else {
                return response()->json([], 200); // Return empty if no lab assigned
            }
        } elseif ($userRole === 'user') {
            // Regular users only see their own transactions
            $query->where('borrower_id', $userId);
        }

        $transactions = $query->latest()->paginate($perPage);

        $transactionIds = $transactions->pluck('id');

        // Build equipment assignments from the pivot table directly (reliable)
        // Keys after groupBy are integers on MySQL, strings on SQLite – we handle both.
        $assignedItems = DB::table('transaction_equipment_items')
            ->join('equipment_items', 'transaction_equipment_items.equipment_item_id', '=', 'equipment_items.id')
            ->whereIn('transaction_equipment_items.transaction_id', $transactionIds)
            ->select('transaction_equipment_items.transaction_id', 'equipment_items.equipment_id', 'equipment_items.unit_id')
            ->get()
            ->groupBy('transaction_id')
            ->map->groupBy('equipment_id');

        // Collect all equipment IDs from the assignment map (not from broken eager-load)
        $equipmentIds = $assignedItems->flatMap(fn($byEq) => $byEq->keys())->unique();

        $previewItems = EquipmentItem::whereIn('equipment_id', $equipmentIds)
            ->where('isBorrowed', false)
            ->whereNotIn('condition', ['Damaged', 'Missing', 'Under Repair'])
            ->select('equipment_id', 'unit_id')
            ->get()
            ->groupBy('equipment_id');

        // Pre-load equipment names in one query
        $equipmentNames = \App\Models\Equipment::select('id', 'name')
            ->whereIn('id', $equipmentIds)
            ->pluck('name', 'id');

        $transactions->getCollection()->transform(function ($t) use ($assignedItems, $previewItems, $equipmentNames) {
            // groupBy keys may be int or string depending on DB driver – check both
            $tid = $assignedItems->has($t->id) ? $t->id : (string) $t->id;

            if ($assignedItems->has($tid)) {
                $equipmentList = $assignedItems->get($tid)->map(function ($items, $eqId) use ($equipmentNames) {
                    $name = $equipmentNames->get($eqId) ?? $equipmentNames->get((int)$eqId) ?? 'Unknown';
                    return (object)[
                        'id'       => (int) $eqId,
                        'name'     => $name,
                        'quantity' => $items->count(),
                    ];
                })->values();
            } else {
                $equipmentList = collect();
            }

            $summaryLines = [];
            foreach ($equipmentList as $eq) {
                $qty = $eq->quantity ?? 1;

                // Check both int and string keys for the nested lookup
                $eqGroup = $assignedItems->get($tid, collect());
                $realUnits = ($eqGroup->has($eq->id) ? $eqGroup->get($eq->id) : $eqGroup->get((string)$eq->id, collect()))->pluck('unit_id');

                $unitInfo = null;
                if ($realUnits->isNotEmpty()) {
                    $unitInfo = $realUnits->implode(', ');
                } elseif ($t->status === 'pending') {
                    $preview = $previewItems->get($eq->id) ?? $previewItems->get((string)$eq->id, collect());
                    $availablePreview = $preview->take($qty)->pluck('unit_id');
                    $unitInfo = $availablePreview->isNotEmpty()
                        ? 'Will assign: ' . $availablePreview->implode(', ')
                        : '(Will assign on approval)';
                }

                $line = "{$eq->name} ×{$qty}";
                if ($unitInfo) {
                    $line .= " ({$unitInfo})";
                }
                $summaryLines[] = $line;
            }

            $t->equipment_summary = implode(' • ', $summaryLines);

            $t->equipment = $equipmentList->map(fn($eq) => [
                'id'       => $eq->id,
                'name'     => $eq->name,
                'quantity' => $eq->quantity ?? 1,
            ])->values()->toArray();

            return $t;
        });

        return response()->json($transactions);
    });
}
    /**
     * Helper: Clear cached transaction list pages (pages 1–10) for ALL roles/users.
     */
    private function clearTransactionListCache()
    {
        // Clear cached transaction list pages for commonly used page sizes.
        // Must clear for all roles and all users since the cache key includes role+userId.
        $perPageOptions = [5, 25, 100];
        $roles = ['admin', 'custodian', 'user'];
        $users = \App\Models\User::pluck('id');
        for ($p = 1; $p <= 10; $p++) {
            foreach ($perPageOptions as $perPage) {
                foreach ($roles as $role) {
                    foreach ($users as $uid) {
                        Cache::forget("transactions.page.{$p}.{$perPage}.{$role}.{$uid}");
                    }
                }
            }
        }
    }

    /**
     * Store a new borrow request (assigns units directly on creation)
     */
    public function store(Request $request)
    {
        $request->validate([
            'borrower_id'       => 'required|exists:users,id',
            'borrower_name'     => 'required|string|max:255',
            'borrower_email'    => 'nullable|email',
            'borrower_contact'  => 'nullable|string',
            'laboratory_id'     => 'required|exists:laboratory,id',
            'borrow_date'       => 'required|date',
            'return_date'       => 'nullable|date|after_or_equal:borrow_date',
            'notes'             => 'nullable|string',
            'equipment'         => 'required|array|min:1',
            'equipment.*.equipment_id' => 'required|exists:equipment,id',
            'equipment.*.quantity'     => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $transaction = Transaction::create([
                'borrower_id'      => $request->borrower_id,
                'borrower_name'    => $request->borrower_name,
                'borrower_email'   => $request->borrower_email,
                'borrower_contact'=> $request->borrower_contact,
                'laboratory_id'    => $request->laboratory_id,
                'borrow_date'      => $request->borrow_date,
                'return_date'      => $request->return_date,
                'notes'            => $request->notes,
                'status'           => 'pending',
            ]);

            foreach ($request->equipment as $item) {
                $equipmentId = $item['equipment_id'];
                $quantity = $item['quantity'];

                $availableItems = EquipmentItem::where('equipment_id', $equipmentId)
                    ->where('isBorrowed', false)
                    ->whereNotIn('condition', ['Damaged', 'Missing', 'Under Repair'])
                    ->take($quantity)
                    ->get();

                if ($availableItems->count() < $quantity) {
                    $eqName = Equipment::find($equipmentId)->name ?? 'Equipment';
                    throw new Exception("Not enough {$eqName} available. Only {$availableItems->count()} good units left.");
                }

                foreach ($availableItems as $eqItem) {
                    DB::table('transaction_equipment_items')->insert([
                        'transaction_id'     => $transaction->id,
                        'equipment_item_id'  => $eqItem->id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                    $eqItem->isBorrowed = true;
                    $eqItem->save();
                }
            }

            Cache::forget('equipment_data');
            $this->clearTransactionListCache();

            broadcast(new TransactionUpdated($transaction->fresh()));

            $this->logAction('transaction_created', ['transaction_id' => $transaction->id, 'borrower_id' => $transaction->borrower_id]);

            return response()->json([
                'message' => 'Borrow request submitted successfully. Waiting for approval.',
                'data'    => $transaction->fresh(['equipment', 'laboratory', 'borrower'])
            ], 201);
        });
    }

    /**
     * Show single transaction with detailed assigned units
     */
    public function show(Transaction $transaction)
    {
        $transaction->load(['borrower:id,name,email', 'laboratory:id,name']);

        $assigned = DB::table('transaction_equipment_items')
            ->join('equipment_items', 'transaction_equipment_items.equipment_item_id', '=', 'equipment_items.id')
            ->join('equipment', 'equipment_items.equipment_id', '=', 'equipment.id')
            ->where('transaction_equipment_items.transaction_id', $transaction->id)
            ->select(
                'equipment.id as equipment_id',
                'equipment.name',
                'equipment_items.id as item_id',
                'equipment_items.unit_id',
                'equipment_items.condition'
            )
            ->get()
            ->groupBy('equipment_id');

        $summary = $assigned->map(function ($items, $eqId) use ($assigned) {
            return [
                'id' => (int)$eqId,
                'name' => $items->first()->name,
                'quantity' => $items->count(),
                'items' => $items->map(fn($i) => [
                    'id' => $i->item_id,
                    'unit_id' => $i->unit_id,
                    'condition' => $i->condition
                ])->values()->toArray()
            ];
        })->values();

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'borrower_name' => $transaction->borrower_name,
                'borrower_email' => $transaction->borrower_email,
                'laboratory' => $transaction->laboratory,
                'borrow_date' => $transaction->borrow_date,
                'return_date' => $transaction->return_date,
                'status' => $transaction->status,
                'notes' => $transaction->notes,
                'equipment' => $summary
            ]
        ]);
    }
     /* Get available items for a specific equipment
     */
    public function availableItems(Equipment $equipment)
    {
        $items = EquipmentItem::where('equipment_id', $equipment->id)
            ->where('isBorrowed', false)
            ->whereNotIn('condition', ['Damaged', 'Missing', 'Under Repair'])
            ->select('id', 'unit_id', 'condition')
            ->orderBy('unit_id')
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Accept a pending transaction
     */
    public function accept(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'This request has already been processed'], 400);
        }

        if (!$transaction->relationLoaded('equipment')) {
            $transaction->load('equipment');
        }

        $alreadyAssignedCount = DB::table('transaction_equipment_items')
            ->where('transaction_id', $transaction->id)
            ->count();

        if ($alreadyAssignedCount > 0) {
            $transaction->status = 'borrowed';
            $transaction->accepted_at = now();
            $transaction->accepted_by_name = auth()->user()->name;
            $transaction->save();
            Cache::forget('equipment_data');
            $this->clearTransactionListCache();

            $this->logAction('transaction_accepted', ['transaction_id' => $transaction->id, 'note' => 'items_already_assigned']);

            broadcast(new TransactionUpdated($transaction->fresh()));

            return response()->json(['message' => 'Request accepted. Items were already assigned.']);
        }

        DB::transaction(function () use ($transaction) {
            foreach ($transaction->equipment as $eq) {
                $qty = $eq->quantity;

                $availableItems = EquipmentItem::where('equipment_id', $eq->id)
                    ->where('isBorrowed', false)
                    ->whereNotIn('condition', ['Damaged', 'Missing', 'Under Repair'])
                    ->take($qty)
                    ->get();

                if ($availableItems->count() < $qty) {
                    $eqName = $eq->name ?? 'Equipment';
                    throw new Exception("Not enough {$eqName} available. Only {$availableItems->count()} good units left.");
                }

                $itemIds = $availableItems->pluck('id')->toArray();

                EquipmentItem::whereIn('id', $itemIds)->update(['isBorrowed' => true]);

                $pivotData = array_map(fn($id) => [
                    'transaction_id'     => $transaction->id,
                    'equipment_item_id'  => $id,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ], $itemIds);

                DB::table('transaction_equipment_items')->insert($pivotData);
            }

            $transaction->status = 'borrowed';
            $transaction->accepted_at = now();
            $transaction->accepted_by_name = auth()->user()->name;
            $transaction->save();
        });

        Cache::forget('equipment_data');
        $this->clearTransactionListCache();

        $this->logAction('transaction_accepted', ['transaction_id' => $transaction->id]);

        broadcast(new TransactionUpdated($transaction->fresh()));

        return response()->json(['message' => 'Request accepted. Items are now officially borrowed.']);
    }

    /**
     * Decline a pending transaction
     */
    public function decline(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Already processed'], 400);
        }

        $rejectionReason = $request->input('rejection_reason');

        DB::transaction(function () use ($transaction, $rejectionReason) {
            $itemIds = DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->pluck('equipment_item_id');

            if ($itemIds->isNotEmpty()) {
                // Free the items so they're available again, but keep pivot records
                // so BorrowHistory can still display what items were requested/rejected.
                EquipmentItem::whereIn('id', $itemIds)->update(['isBorrowed' => false]);
            }

            $transaction->status = 'rejected';
            $transaction->rejected_at = now();
            $transaction->rejected_by_name = auth()->user()->name;
            $transaction->rejection_reason = $rejectionReason ?: null;
            $transaction->save();
        });

        Cache::forget('equipment_data');
        $this->clearTransactionListCache();

        $this->logAction('transaction_declined', ['transaction_id' => $transaction->id]);

        broadcast(new TransactionUpdated($transaction->fresh()));

        return response()->json(['message' => 'Request has been declined']);
    }

    /**
     * Return borrowed items
     */
    public function return(Transaction $transaction)
    {
        if ($transaction->status !== 'borrowed') {
            return response()->json(['message' => 'This transaction is not currently borrowed'], 400);
        }

        DB::transaction(function () use ($transaction) {
            $itemIds = DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->pluck('equipment_item_id');

            EquipmentItem::whereIn('id', $itemIds)->update(['isBorrowed' => false]);
            $transaction->status = 'returned';
            $transaction->returned_at = now();
            $transaction->returned_by_name = auth()->user()->name;
            $transaction->save();
        });

        Cache::forget('equipment_data');
        $this->clearTransactionListCache();

        $this->logAction('transaction_returned', ['transaction_id' => $transaction->id]);

        broadcast(new TransactionUpdated($transaction->fresh()));

        return response()->json(['message' => 'Items returned successfully']);
    }

    /**
     * Get borrower history for a specific equipment item (unit)
     */
    public function itemHistory(EquipmentItem $item)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'custodian'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        // Find all transactions that referenced this equipment_item via pivot table
        $rows = DB::table('transaction_equipment_items')
            ->where('transaction_equipment_items.equipment_item_id', $item->id)
            ->join('transactions', 'transaction_equipment_items.transaction_id', '=', 'transactions.id')
            ->select(
                'transactions.id',
                'transactions.borrower_id',
                'transactions.borrower_name',
                'transactions.borrower_email',
                'transactions.borrower_contact',
                'transactions.laboratory_id',
                'transactions.borrow_date',
                'transactions.return_date',
                'transactions.status',
                'transactions.notes',
                'transaction_equipment_items.created_at as assigned_at'
            )
            ->orderBy('transaction_equipment_items.created_at', 'desc')
            ->get();

        // current real-time status of the item
        $current = [
            'id' => $item->id,
            'unit_id' => $item->unit_id,
            'condition' => $item->condition,
            'isBorrowed' => (bool) $item->isBorrowed,
        ];

        return response()->json(['data' => ['history' => $rows, 'current' => $current]]);
    }

    /**
     * Update a pending transaction
     */
    public function update(Request $request, Transaction $transaction)
    {
        // Allow full edits only while pending. If rejected or returned, allow notes-only edits.
        if ($transaction->status === 'pending') {
            $request->validate([
                'borrower_id'       => 'required|exists:users,id',
                'borrower_name'     => 'required|string|max:255',
                'borrower_email'    => 'nullable|email',
                'borrower_contact'  => 'nullable|string',
                'laboratory_id'     => 'required|exists:laboratory,id',
                'borrow_date'       => 'required|date',
                'return_date'       => 'nullable|date|after_or_equal:borrow_date',
                'notes'             => 'nullable|string',
                'equipment'         => 'required|array|min:1',
                'equipment.*.equipment_id' => 'required|exists:equipment,id',
                'equipment.*.quantity'     => 'required|integer|min:1',
            ]);

            DB::transaction(function () use ($request, $transaction) {
                $transaction->update([
                    'borrower_id'      => $request->borrower_id,
                'borrower_name'    => $request->borrower_name,
                'borrower_email'   => $request->borrower_email,
                'borrower_contact'=> $request->borrower_contact,
                'laboratory_id'    => $request->laboratory_id,
                'borrow_date'      => $request->borrow_date,
                'return_date'      => $request->return_date,
                'notes'            => $request->notes,
            ]);

            $oldItemIds = DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->pluck('equipment_item_id');

            EquipmentItem::whereIn('id', $oldItemIds)->update(['isBorrowed' => false]);
            DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->delete();

            foreach ($request->equipment as $item) {
                $equipmentId = $item['equipment_id'];
                $quantity = $item['quantity'];

                $availableItems = EquipmentItem::where('equipment_id', $equipmentId)
                    ->where('isBorrowed', false)
                    ->whereNotIn('condition', ['Damaged', 'Missing', 'Under Repair'])
                    ->take($quantity)
                    ->get();

                if ($availableItems->count() < $quantity) {
                    $eqName = Equipment::find($equipmentId)->name ?? 'Equipment';
                    throw new Exception("Not enough {$eqName} available. Only {$availableItems->count()} good units left.");
                }

                foreach ($availableItems as $eqItem) {
                    DB::table('transaction_equipment_items')->insert([
                        'transaction_id'     => $transaction->id,
                        'equipment_item_id'  => $eqItem->id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                    $eqItem->isBorrowed = true;
                    $eqItem->save();
                }
            }
        });
            Cache::forget('equipment_data');
            $this->clearTransactionListCache();

            broadcast(new TransactionUpdated($transaction->fresh()));

            $this->logAction('transaction_updated', ['transaction_id' => $transaction->id]);

            return response()->json([
                'message' => 'Request updated successfully',
                'data'    => $transaction->fresh(['equipment', 'laboratory', 'borrower'])
            ]);
        }

        if ($transaction->status === 'borrowed') {
            $request->validate([
                'notes' => 'nullable|string',
                'return_date' => 'nullable|date'
            ]);

            if ($request->filled('return_date') && $request->input('return_date') < $transaction->borrow_date) {
                return response()->json(['message' => 'Return date cannot be before borrow date'], 422);
            }

            $transaction->notes = $request->notes;
            if ($request->filled('return_date')) {
                $transaction->return_date = $request->input('return_date');
            }
            $transaction->save();

            Cache::forget('equipment_data');
            $this->clearTransactionListCache();

            $this->logAction('transaction_updated', ['transaction_id' => $transaction->id]);

            broadcast(new TransactionUpdated($transaction->fresh()));

            return response()->json([
                'message' => 'Notes and return date updated successfully',
                'data' => $transaction->fresh()
            ]);
        }

        if (in_array($transaction->status, ['rejected', 'returned'])) {
            $request->validate([
                'notes' => 'nullable|string'
            ]);

            $transaction->notes = $request->notes;
            $transaction->save();

            Cache::forget('equipment_data');
            $this->clearTransactionListCache();

            $this->logAction('transaction_updated', ['transaction_id' => $transaction->id]);

            broadcast(new TransactionUpdated($transaction->fresh()));

            return response()->json([
                'message' => 'Notes updated successfully',
                'data' => $transaction->fresh()
            ]);
        }

        return response()->json(['message' => 'Cannot edit a processed transaction'], 400);
    }

    /**
     * Delete a pending or rejected transaction
     */
    public function destroy(Transaction $transaction)
    {
        if (!in_array($transaction->status, ['pending', 'rejected'])) {
            return response()->json(['message' => 'Cannot delete a processed transaction'], 400);
        }

        DB::transaction(function () use ($transaction) {
            $itemIds = DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->pluck('equipment_item_id');

            EquipmentItem::whereIn('id', $itemIds)->update(['isBorrowed' => false]);
            DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->delete();

            $transaction->delete();
        });

        Cache::forget('equipment_data');
        $this->clearTransactionListCache();

        $this->logAction('transaction_deleted', ['transaction_id' => $transaction->id]);

        return response()->json(null, 204);
    }

    /**
     * Manually update assigned specific units (by unit_id)
     */
    public function updateAssignedItems(Transaction $transaction, Request $request)
    {
        if (!$transaction->relationLoaded('equipment')) {
            $transaction->load('equipment');
        }

        $request->validate([
            'assigned_items' => 'required|array',
            'assigned_items.*' => 'required|array|min:1',
        ]);

        foreach ($transaction->equipment as $eq) {
            $required = $eq->quantity;
            $provided = count($request->assigned_items[$eq->id] ?? []);

            if ($provided !== $required) {
                return response()->json([
                    'message' => "Must assign exactly {$required} unit(s) for {$eq->name}. You provided {$provided}."
                ], 422);
            }
        }

        DB::transaction(function () use ($transaction, $request) {
            $newAssignments = $request->assigned_items;

            $oldItems = DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->pluck('equipment_item_id');

            EquipmentItem::whereIn('id', $oldItems)->update(['isBorrowed' => false]);
            DB::table('transaction_equipment_items')
                ->where('transaction_id', $transaction->id)
                ->delete();

            foreach ($newAssignments as $equipmentId => $unitIds) {
                foreach ($unitIds as $unitId) {
                    $item = EquipmentItem::where('unit_id', $unitId)
                        ->where('equipment_id', $equipmentId)
                        ->firstOrFail();

                    if ($item->isBorrowed) {
                        throw new Exception("Item {$unitId} is already borrowed.");
                    }

                    if (in_array($item->condition, ['Damaged', 'Missing', 'Under Repair'])) {
                        throw new Exception("Cannot assign {$unitId} — it is {$item->condition}");
                    }

                    $item->isBorrowed = true;
                    $item->save();

                    DB::table('transaction_equipment_items')->insert([
                        'transaction_id'     => $transaction->id,
                        'equipment_item_id'  => $item->id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }
        });

        Cache::forget('equipment_data');
        $this->clearTransactionListCache();

        $this->logAction('transaction_assigned_items_updated', ['transaction_id' => $transaction->id, 'assigned_items' => $request->assigned_items]);

        broadcast(new TransactionUpdated($transaction->fresh()));

        return response()->json(['message' => 'Assigned items updated successfully']);
    }
}