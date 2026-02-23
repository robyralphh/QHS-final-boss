<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventorySnapshot;
use App\Models\Equipment;
use App\Models\Laboratory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SnapshotDailyInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:snapshot';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Create daily inventory snapshots for all equipment and laboratories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = now()->toDateString();
        $equipment = Equipment::all();
        $laboratories = Laboratory::all();

        $count = 0;

        foreach ($equipment as $eq) {
            foreach ($laboratories as $lab) {
                // Count total items for this equipment in this lab
                $total = DB::table('equipment_items')
                    ->where('equipment_id', $eq->id)
                    ->where('laboratory_id', $lab->id)
                    ->count();

                // Count borrowed items (from active transactions)
                $borrowed = DB::table('transaction_equipment_items')
                    ->join('transactions', 'transaction_equipment_items.transaction_id', '=', 'transactions.id')
                    ->join('equipment_items', 'transaction_equipment_items.equipment_item_id', '=', 'equipment_items.id')
                    ->where('equipment_items.equipment_id', $eq->id)
                    ->where('equipment_items.laboratory_id', $lab->id)
                    ->whereIn('transactions.status', ['pending', 'borrowed'])
                    ->count();

                $available = max(0, $total - $borrowed);

                InventorySnapshot::updateOrCreate(
                    [
                        'snapshot_date' => $date,
                        'equipment_id' => $eq->id,
                        'laboratory_id' => $lab->id,
                    ],
                    [
                        'total_items' => $total,
                        'borrowed_count' => $borrowed,
                        'available_count' => $available,
                    ]
                );

                $count++;
            }
        }

        $message = "Daily inventory snapshots created successfully - {$count} records";
        $this->info($message);
        Log::info($message);
    }
}
