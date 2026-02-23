<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentItem extends Model
{
    use HasFactory;

    protected $table = 'equipment_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'equipment_id',
        'unit_id',
        'condition',
        'isBorrowed',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'isBorrowed' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'isBorrowed' => false,
        'condition'  => 'Good',
    ];

    // Timestamps are enabled by default
    public $timestamps = true;

    // =============================================
    // RELATIONSHIPS
    // =============================================
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function borrowLogs(): HasMany
    {
        return $this->hasMany(BorrowLog::class, 'equipment_item_id');
    }

    // =============================================
    // AUTO-GENERATE UNIT ID ON CREATE
    // =============================================
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->unit_id)) {
                $item->unit_id = static::generateUnitId($item->equipment_id);
            }
        });
    }

    /**
     * Generate unique unit ID: EQ01-0001, EQ01-0002, etc.
     */
    public static function generateUnitId(int $equipmentId): string
    {
        // Get the highest number for this equipment
        $lastItem = static::where('equipment_id', $equipmentId)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastItem
            ? ((int) substr($lastItem->unit_id, -4)) + 1
            : 1;

        // Format: EQ01-0001
        return sprintf('EQ%02d-%04d', $equipmentId, $nextNumber);
    }
}