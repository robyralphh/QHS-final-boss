<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Transaction;
use App\Models\EquipmentItem;
use Illuminate\Support\Facades\DB;

class TransactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction)
    {
        // Load minimal needed relations
        // Load basic relations (do NOT request a non-existent `quantity` column)
        $transaction->load([
            'borrower:id,name,email',
            'laboratory:id,name',
            'equipment:id,name'
        ]);

        // Get assigned units grouped by equipment_id (equipment_items.equipment_id)
        $assigned = DB::table('transaction_equipment_items')
            ->join('equipment_items', 'transaction_equipment_items.equipment_item_id', '=', 'equipment_items.id')
            ->where('transaction_equipment_items.transaction_id', $transaction->id)
            ->select('equipment_items.equipment_id', 'equipment_items.unit_id')
            ->get()
            ->groupBy('equipment_id')
            ->map(fn($rows) => $rows->pluck('unit_id'));

        // Build clean equipment collection with items (keep as Collection so we can map again)
        $equipmentForBroadcast = $transaction->equipment->map(function ($eq) use ($assigned, $transaction) {
            $realUnits = $assigned->get($eq->id, collect());

            $qty = $realUnits->count() ?: 1;

            $unitInfo = '';
            if ($realUnits->isNotEmpty()) {
                $unitInfo = $realUnits->implode(', ');
            } elseif ($transaction->status === 'pending') {
                $preview = EquipmentItem::where('equipment_id', $eq->id)
                    ->where('isBorrowed', false)
                    ->whereNotIn('condition', ['Damaged', 'Missing', 'Under Repair'])
                    ->take($qty)
                    ->pluck('unit_id');

                $unitInfo = $preview->isNotEmpty()
                    ? 'Will assign: ' . $preview->implode(', ')
                    : '(Will assign on approval)';
            }

            return [
                'id' => $eq->id,
                'name' => $eq->name,
                'quantity' => $qty,
                'units' => $unitInfo,
            ];
        })->values();

        // Build equipment_summary
        $summaryLines = $equipmentForBroadcast->map(function ($eq) {
            $line = "{$eq['name']} ×{$eq['quantity']}";
            if (!empty($eq['units'])) {
                $line .= " ({$eq['units']})";
            }
            return $line;
        })->implode(' • ');

        // Build minimal payload for broadcast
        $this->transaction = [
            'id' => $transaction->id,
            'borrower_name' => $transaction->borrower_name,
            'borrower_email' => $transaction->borrower_email,
            'laboratory' => $transaction->laboratory,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at->toJson(),
            'equipment_summary' => $summaryLines,
            'equipment_summary' => $summaryLines,
            'equipment' => $equipmentForBroadcast->values()->toArray(), // Keep for fallback
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('transactions');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'transaction.updated';
    }
}