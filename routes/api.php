<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EquipmentImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\InventorySnapshotController;
use App\Http\Resources\EquipmentItemResource;
use App\Models\EquipmentItem;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ────────────────────────────────
//  PUBLIC ROUTES
// ────────────────────────────────
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Email Verification Routes (Public)
Route::post('email/verify', [EmailVerificationController::class, 'verifyEmail'])->name('verify.email');
// Signed GET route used by verification emails (temporary signed URL)
Route::get('email/verify/{id}', [EmailVerificationController::class, 'verifySigned'])->name('verification.verify');
Route::post('email/resend', [EmailVerificationController::class, 'resendVerificationEmail'])->name('resend-verification-email');

// ────────────────────────────────
//  PROTECTED ROUTES
// ────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    // Email Verification Routes (Protected)
    Route::post('email/send-verification', [EmailVerificationController::class, 'sendVerificationEmail']);
    Route::get('email/verification-status', [EmailVerificationController::class, 'checkVerificationStatus']);

    // Fresh equipment list with item counts
    Route::get('/equipment-data', [EquipmentController::class, 'data']);

    // ────────────────────────────────
    //  PROFILE ROUTES
    // ────────────────────────────────
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('profile/password', [ProfileController::class, 'updatePassword']);

    // ADMIN DASHBOARD
    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/users',     [DashboardController::class, 'users']);
        Route::get('/labs',      [DashboardController::class, 'labs']);
        Route::get('/equipment', [DashboardController::class, 'equipment']);
        Route::get('/activity',  [DashboardController::class, 'activity']);
        Route::get('/summary',   [DashboardController::class, 'summary']);
    });

    // ────────────────────────────────
    //  MAIN RESOURCES
    // ────────────────────────────────
    Route::apiResource('users',         UserController::class);
    Route::apiResource('laboratories',  LaboratoryController::class);
    Route::apiResource('equipment',     EquipmentController::class);
    Route::apiResource('categories',     CategoryController::class);

    // ────────────────────────────────
    //  EQUIPMENT ITEMS (CLEAN & NO CONFLICTS)
    // ────────────────────────────────
    // List all items (with equipment relation)
    Route::get('/item', function () {
        $query = EquipmentItem::with('equipment');
        
        // If user is custodian, filter by their laboratory
        if (auth()->user()->role === 'custodian') {
            $lab = \App\Models\Laboratory::where('custodianID', auth()->id())->first();
            if ($lab) {
                $query->whereHas('equipment', function ($q) use ($lab) {
                    $q->where('laboratory_id', $lab->id);
                });
            } else {
                return response()->json([], 200); // Return empty if no lab assigned
            }
        }
        
        return EquipmentItemResource::collection($query->get());
    });
    // routes/api.php
    Route::put('/equipment/{equipment}/toggle-active', [EquipmentController::class, 'toggleActive']);
    // Full CRUD for individual items
    Route::post('/item',   [EquipmentItemController::class, 'store']);
    Route::get('/item/{item}',    [EquipmentItemController::class, 'show']);
    Route::put('/item/{item}',    [EquipmentItemController::class, 'update']);
    Route::delete('/item/{item}', [EquipmentItemController::class, 'destroy']);

    // Get only available (not borrowed) items for borrowing
    Route::get('/equipment/{equipment}/available-items', [EquipmentItemController::class, 'availableItems']);

    // ────────────────────────────────
    //  IMPORT EQUIPMENT FROM EXCEL
    // ────────────────────────────────
    Route::post('equipment/import', [EquipmentImportController::class, 'import']);

    // ────────────────────────────────
    //  TRANSACTIONS
    // ────────────────────────────────
    Route::apiResource('transactions', TransactionController::class);

    // Action logs
    Route::get('logs', [\App\Http\Controllers\ActionLogController::class, 'index']);

    Route::post('transactions/{transaction}/accept',          [TransactionController::class, 'accept']);
    Route::post('transactions/{transaction}/decline',         [TransactionController::class, 'decline']);
    Route::post('transactions/{transaction}/return',          [TransactionController::class, 'return']);
    Route::post('transactions/{transaction}/update-assigned-items', [TransactionController::class, 'updateAssignedItems']);
    Route::post('transactions/{transaction}/accept-and-assign',     [TransactionController::class, 'acceptAndAssign']);
    Route::get('/item/{item}/history', [TransactionController::class, 'itemHistory']);

    // ────────────────────────────────
    //  INVENTORY SNAPSHOTS
    // ────────────────────────────────
    Route::get('inventory-snapshots/range', [InventorySnapshotController::class, 'getSnapshotsByDateRange']);
    Route::get('inventory-snapshots/equipment/{equipment}/trend', [InventorySnapshotController::class, 'getEquipmentTrend']);
    Route::get('inventory-snapshots/export', [InventorySnapshotController::class, 'exportCSV']);
    Route::get('inventory-snapshots/settings', [InventorySnapshotController::class, 'getSnapshotSettings']);
    Route::post('inventory-snapshots/settings', [InventorySnapshotController::class, 'updateSnapshotSettings']);
    Route::post('inventory-snapshots/trigger', [InventorySnapshotController::class, 'triggerSnapshot']);
});