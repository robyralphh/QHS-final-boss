<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActionLog;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentItem;
use App\Models\Laboratory;
use App\Models\User;
use App\Models\Transaction;

class ActionLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(10, (int) $request->get('per_page', 25));

        $query = ActionLog::query()->with('user')->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->get('action') . '%');
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to'));
        }

        $data = $query->paginate($perPage);

        // Resolve ids to readable names for items on this page
        $collection = $data->getCollection();

        $categoryIds = [];
        $equipmentIds = [];
        $itemIds = [];
        $labIds = [];
        $userIds = [];
        $transactionIds = [];

        foreach ($collection as $row) {
            $m = $row->meta ?? [];
            // common singular keys
            if (!empty($m['category_id'])) $categoryIds[] = (int)$m['category_id'];
            if (!empty($m['equipment_id'])) $equipmentIds[] = (int)$m['equipment_id'];
            if (!empty($m['item_id'])) $itemIds[] = (int)$m['item_id'];
            if (!empty($m['laboratory_id'])) $labIds[] = (int)$m['laboratory_id'];
            if (!empty($m['user_id'])) $userIds[] = (int)$m['user_id'];
            if (!empty($m['transaction_id'])) $transactionIds[] = (int)$m['transaction_id'];

            // plural keys
            if (!empty($m['category_ids']) && is_array($m['category_ids'])) $categoryIds = array_merge($categoryIds, $m['category_ids']);
            if (!empty($m['equipment_ids']) && is_array($m['equipment_ids'])) $equipmentIds = array_merge($equipmentIds, $m['equipment_ids']);
            if (!empty($m['item_ids']) && is_array($m['item_ids'])) $itemIds = array_merge($itemIds, $m['item_ids']);
        }

        $categoryMap = $categoryIds ? Category::whereIn('id', array_unique($categoryIds))->pluck('name', 'id')->toArray() : [];
        $equipmentMap = $equipmentIds ? Equipment::whereIn('id', array_unique($equipmentIds))->pluck('name', 'id')->toArray() : [];
        $itemMap = $itemIds ? EquipmentItem::whereIn('id', array_unique($itemIds))->pluck('unit_id', 'id')->toArray() : [];
        $labMap = $labIds ? Laboratory::whereIn('id', array_unique($labIds))->pluck('name', 'id')->toArray() : [];
        $userMap = $userIds ? User::whereIn('id', array_unique($userIds))->pluck('name', 'id')->toArray() : [];
        $transactionMap = $transactionIds ? Transaction::whereIn('id', array_unique($transactionIds))->pluck('borrower_name', 'id')->toArray() : [];

        $collection->transform(function ($row) use ($categoryMap, $equipmentMap, $itemMap, $labMap, $userMap, $transactionMap) {
            $m = $row->meta ?? [];
            $userName = $row->user->name ?? 'System';

            $friendly = null;
            switch ($row->action) {
                case 'category_created':
                    $friendly = "{$userName} created category " . ($categoryMap[$m['category_id']] ?? ('#' . ($m['category_id'] ?? '')));
                    break;
                case 'category_updated':
                    $friendly = "{$userName} updated category " . ($categoryMap[$m['category_id']] ?? ('#' . ($m['category_id'] ?? '')));
                    break;
                case 'category_deleted':
                    $friendly = "{$userName} deleted category " . ($categoryMap[$m['category_id']] ?? ('#' . ($m['category_id'] ?? '')));
                    break;
                case 'equipment_created':
                    $friendly = "{$userName} added equipment " . ($equipmentMap[$m['equipment_id']] ?? ('#' . ($m['equipment_id'] ?? '')));
                    break;
                case 'equipment_updated':
                    $friendly = "{$userName} updated equipment " . ($equipmentMap[$m['equipment_id']] ?? ('#' . ($m['equipment_id'] ?? '')));
                    break;
                case 'equipment_deleted':
                    $friendly = "{$userName} removed equipment " . ($equipmentMap[$m['equipment_id']] ?? ('#' . ($m['equipment_id'] ?? '')));
                    break;
                case 'equipment_toggled_active':
                    $friendly = "{$userName} " . (($m['isActive'] ?? false) ? 'activated' : 'archived') . " equipment " . ($equipmentMap[$m['equipment_id']] ?? ('#' . ($m['equipment_id'] ?? '')));
                    break;
                case 'equipment_import':
                    $friendly = "{$userName} imported equipment (success: " . ($m['success_count'] ?? 0) . ", failed: " . ($m['failed_count'] ?? 0) . ")";
                    break;
                case 'equipment_item_created':
                    $friendly = "{$userName} created item " . ($itemMap[$m['item_id']] ?? ('#' . ($m['item_id'] ?? ''))) . " for equipment " . ($equipmentMap[$m['equipment_id']] ?? ('#' . ($m['equipment_id'] ?? '')));
                    break;
                case 'equipment_item_updated':
                    $friendly = "{$userName} updated item " . ($itemMap[$m['item_id']] ?? ('#' . ($m['item_id'] ?? '')));
                    break;
                case 'equipment_item_deleted':
                    $friendly = "{$userName} deleted item " . ($itemMap[$m['item_id']] ?? ('#' . ($m['item_id'] ?? '')));
                    break;
                case 'laboratory_created':
                    $friendly = "{$userName} created laboratory " . ($labMap[$m['laboratory_id']] ?? ('#' . ($m['laboratory_id'] ?? '')));
                    break;
                case 'laboratory_updated':
                    $friendly = "{$userName} updated laboratory " . ($labMap[$m['laboratory_id']] ?? ('#' . ($m['laboratory_id'] ?? '')));
                    break;
                case 'laboratory_deleted':
                    $friendly = "{$userName} deleted laboratory " . ($labMap[$m['laboratory_id']] ?? ('#' . ($m['laboratory_id'] ?? '')));
                    break;
                case 'user_created':
                    $friendly = "{$userName} created user " . ($userMap[$m['user_id']] ?? ('#' . ($m['user_id'] ?? '')));
                    break;
                case 'user_updated':
                    $friendly = "{$userName} updated user " . ($userMap[$m['user_id']] ?? ('#' . ($m['user_id'] ?? '')));
                    break;
                case 'user_deleted':
                    $friendly = "{$userName} deleted user " . ($userMap[$m['user_id']] ?? ('#' . ($m['user_id'] ?? '')));
                    break;
                case 'transaction_created':
                    $friendly = "{$userName} created borrow request " . ($transactionMap[$m['transaction_id']] ?? ('#' . ($m['transaction_id'] ?? '')));
                    break;
                case 'transaction_accepted':
                    $friendly = "{$userName} accepted request " . ($m['transaction_id'] ?? '');
                    break;
                case 'transaction_declined':
                    $friendly = "{$userName} declined request " . ($m['transaction_id'] ?? '');
                    break;
                case 'transaction_returned':
                    $friendly = "{$userName} marked returned " . ($m['transaction_id'] ?? '');
                    break;
                case 'transaction_updated':
                    $friendly = "{$userName} updated borrow request " . ($m['transaction_id'] ?? '');
                    break;
                case 'transaction_deleted':
                    $friendly = "{$userName} deleted borrow request " . ($m['transaction_id'] ?? '');
                    break;
                case 'transaction_assigned_items_updated':
                    $friendly = "{$userName} updated assigned items for request " . ($m['transaction_id'] ?? '');
                    break;
                default:
                    $friendly = ($row->user->name ?? 'System') . ' — ' . ($row->action ?? '');
            }

            $row->friendly_message = $friendly;
            // provide a compact meta summary
            $row->meta_summary = is_array($m) && count($m) ? json_encode($m) : null;

            return $row;
        });

        $data->setCollection($collection);

        return response()->json($data);
    }
}
