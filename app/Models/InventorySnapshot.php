<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        'equipment_id',
        'laboratory_id',
        'total_items',
        'borrowed_count',
        'available_count',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }
}
