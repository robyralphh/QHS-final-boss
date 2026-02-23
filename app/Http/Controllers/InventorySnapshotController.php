<?php

namespace App\Http\Controllers;

use App\Models\InventorySnapshot;
use App\Models\Equipment;
use App\Models\Laboratory;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventorySnapshotController extends Controller
{
    /**
     * Get snapshots for a date range with optional laboratory filter
     */
    public function getSnapshotsByDateRange(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $laboratoryId = $request->get('laboratory_id');

        $query = InventorySnapshot::whereBetween('snapshot_date', [$startDate, $endDate])
            ->with(['equipment', 'laboratory'])
            ->orderBy('snapshot_date', 'desc')
            ->orderBy('equipment_id', 'asc');

        // If user is custodian, filter by their laboratory
        if (auth()->user()->role === 'custodian') {
            $lab = Laboratory::where('custodianID', auth()->id())->first();
            if ($lab) {
                $query->where('laboratory_id', $lab->id);
            } else {
                return response()->json([], 200); // Return empty if no lab assigned
            }
        } elseif ($laboratoryId) {
            $query->where('laboratory_id', $laboratoryId);
        }

        return response()->json($query->get());
    }

    /**
     * Get trend data for a specific equipment
     */
    public function getEquipmentTrend(Request $request, Equipment $equipment)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = InventorySnapshot::where('equipment_id', $equipment->id)
            ->whereBetween('snapshot_date', [$startDate, $endDate])
            ->with('laboratory')
            ->orderBy('snapshot_date', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->laboratory->name;
            });

        $result = [];
        foreach ($data as $labName => $snapshots) {
            $result[$labName] = $snapshots->map(function ($snapshot) {
                return [
                    'date' => $snapshot->snapshot_date->format('Y-m-d'),
                    'total_items' => $snapshot->total_items,
                    'borrowed_count' => $snapshot->borrowed_count,
                    'available_count' => $snapshot->available_count,
                ];
            })->values();
        }

        return response()->json($result);
    }

    /**
     * Export snapshots as CSV
     */
    public function exportCSV(Request $request)
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $laboratoryId = $request->get('laboratory_id');

            if (!$startDate || !$endDate) {
                return response()->json(['error' => 'Start date and end date are required'], 400);
            }

            $query = InventorySnapshot::whereBetween('snapshot_date', [$startDate, $endDate])
                ->with(['equipment', 'laboratory'])
                ->orderBy('snapshot_date', 'desc');

            if ($laboratoryId) {
                $query->where('laboratory_id', $laboratoryId);
            }

            $snapshots = $query->get();

            if ($snapshots->isEmpty()) {
                return response()->json(['error' => 'No snapshots found for the given date range'], 404);
            }

            // CSV Headers
            $headers = ['Date', 'Laboratory', 'Equipment', 'Total Items', 'Borrowed Count', 'Available Count'];

            // CSV Rows
            $rows = $snapshots->map(function ($snapshot) {
                return [
                    $snapshot->snapshot_date->format('Y-m-d'),
                    $snapshot->laboratory->name ?? 'Unknown',
                    $snapshot->equipment->name ?? 'Unknown',
                    $snapshot->total_items,
                    $snapshot->borrowed_count,
                    $snapshot->available_count,
                ];
            });

            // Add summary
            $summaryRows = [
                [],
                ['SUMMARY'],
                ['Total Records', $snapshots->count()],
                ['Date Range', "{$startDate} to {$endDate}"],
            ];

            // CSV Content
            $csvContent = [
                ['Inventory Snapshots Report'],
                [],
                $headers,
                ...$rows->map(fn($row) => array_map(fn($cell) => "\"$cell\"", $row)),
                ...$summaryRows->map(fn($row) => array_map(fn($cell) => "\"$cell\"", $row)),
            ];

            $output = implode("\n", array_map(fn($row) => implode(',', $row), $csvContent));

            return response($output)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="inventory_snapshots_' . date('Y-m-d') . '.csv"');
        } catch (\Exception $e) {
            \Log::error('Export CSV Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to export CSV: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get current snapshot settings
     */
    public function getSnapshotSettings()
    {
        $time = SystemSetting::get('daily_inventory_snapshot_time', '23:59');
        return response()->json(['snapshot_time' => $time]);
    }

    /**
     * Update snapshot settings
     */
    public function updateSnapshotSettings(Request $request)
    {
        $request->validate(['snapshot_time' => 'required|date_format:H:i']);
        
        SystemSetting::set('daily_inventory_snapshot_time', $request->snapshot_time);
        
        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * Manually trigger a snapshot for all equipment and laboratories
     */
    public function triggerSnapshot()
    {
        try {
            $date = now()->toDateString();
            $equipment = Equipment::all();
            $laboratories = Laboratory::all();

            foreach ($equipment as $eq) {
                foreach ($laboratories as $lab) {
                    // Count total items for this equipment (which belongs to a lab)
                    // Only count items if the equipment belongs to this lab
                    if ($eq->laboratory_id != $lab->id) {
                        continue;
                    }

                    // Count total items
                    $items = DB::table('equipment_items')
                        ->where('equipment_id', $eq->id)
                        ->get(['id', 'isBorrowed', 'condition']);

                    $total = $items->count();

                    // Count borrowed items (not Damaged/Missing/Under Repair)
                    $borrowed = $items->filter(function ($item) {
                        return $item->isBorrowed && 
                               !in_array($item->condition, ['Damaged', 'Missing', 'Under Repair']);
                    })->count();

                    // Count unavailable items (Damaged, Missing, Under Repair)
                    $unavailable = $items->filter(function ($item) {
                        return in_array($item->condition, ['Damaged', 'Missing', 'Under Repair']);
                    })->count();

                    // Available = total - borrowed - unavailable
                    $available = max(0, $total - $borrowed - $unavailable);

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
                }
            }

            return response()->json(['message' => 'Snapshot created successfully']);
        } catch (\Exception $e) {
            \Log::error('Trigger Snapshot Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create snapshot: ' . $e->getMessage()], 500);
        }
    }
}
