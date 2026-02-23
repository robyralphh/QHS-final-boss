<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Laboratory;
use App\Models\EquipmentItem;
use App\Models\BorrowLog; // Add this import if you have the model
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * User registrations in the last 7 days (for line chart)
     */
   public function users(Request $request)
{
    $days = $request->query('days', 7);
    $days = in_array($days, [7, 30]) ? $days : 7;

    $start = now()->subDays($days - 1)->startOfDay();
    $end   = now()->endOfDay();

    $registrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->whereBetween('created_at', [$start, $end])
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date');

    $range = [];
    $current = $start->copy();
    while ($current <= $end) {
        $dateKey = $current->format('Y-m-d');
        $range[] = [
            'date'  => $current->format('M j'),
            'count' => $registrations[$dateKey] ?? 0,
        ];
        $current->addDay();
    }

    return response()->json($range);
}

    /**
     * Total items per laboratory (for bar chart) - **ENHANCED**
     */
    public function labs()
    {
        // Count AVAILABLE items (not borrowed) per lab
        $data = Laboratory::withCount([
            'items as total_items' => function ($query) {
                $query->where('is_borrowed', false); // Only available items
            }
        ])
        ->orderBy('total_items', 'desc')
        ->get()
        ->map(fn($lab) => [
            'name'  => $lab->name,
            'total' => $lab->total_items ?? 0,
        ]);

        return response()->json($data);
    }

    /**
     * Equipment status distribution (for pie chart)
     */
    public function equipment()
    {
        $data = EquipmentItem::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(fn($item) => [
                'status' => ucfirst(str_replace('_', ' ', $item->status)),
                'count'  => $item->count,
            ]);

        return response()->json($data);
    }

    /**
     * Recent user activity (borrows/returns) - **NEW** for live feed
     */
    public function activity()
    {
        // If you don't have BorrowLog yet, use EquipmentItem recent updates as fallback
        try {
            $activities = EquipmentItem::with(['user', 'laboratory'])
                ->whereNotNull('updated_at')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $action = $item->is_borrowed ? 'borrowed' : 'returned';
                    return [
                        'id'     => $item->id,
                        'user'   => $item->user->name ?? 'Unknown User',
                        'avatar' => $item->user->avatar ?? null,
                        'action' => $action,
                        'item'   => $item->name,
                        'lab'    => $item->laboratory->name ?? 'Unknown Lab',
                        'time'   => $item->updated_at->diffForHumans(),
                        'icon'   => $action === 'borrowed' ? 'login' : 'logout',
                    ];
                });

            return response()->json(['data' => $activities]);
        } catch (\Exception $e) {
            // Fallback if no relationships exist yet
            $activities = EquipmentItem::orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id'     => $item->id,
                        'user'   => 'Unknown User',
                        'avatar' => null,
                        'action' => $item->is_borrowed ? 'borrowed' : 'returned',
                        'item'   => $item->name,
                        'lab'    => 'Unknown Lab',
                        'time'   => $item->updated_at->diffForHumans(),
                        'icon'   => $item->is_borrowed ? 'login' : 'logout',
                    ];
                });

            return response()->json(['data' => $activities]);
        }
    }

    /**
     * Summary stats (for cards) - **NEW**
     */
    public function summary()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $totalLabs = Laboratory::count();
        $totalEquipment = EquipmentItem::count();
        $available = EquipmentItem::where('is_borrowed', false)->count();

        // Calculate change today (example: available items updated today)
        $todayStart = Carbon::today();
        $yesterdayStart = Carbon::yesterday();
        $availableToday = EquipmentItem::where('is_borrowed', false)
            ->where('updated_at', '>=', $todayStart)
            ->count();
        $availableYesterday = EquipmentItem::where('is_borrowed', false)
            ->whereBetween('updated_at', [$yesterdayStart, $todayStart])
            ->count();
        $todayChange = $availableToday - $availableYesterday;

        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'labs' => $totalLabs,
            'equipment' => [
                'total' => $totalEquipment,
                'available' => $available,
                'today_change' => $todayChange > 0 ? "+{$todayChange}" : $todayChange,
            ],
        ]);
    }
}