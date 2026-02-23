<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;           // ← ADD THIS
use Illuminate\Database\Eloquent\Relations\BelongsToMany;    // ← ADD THIS
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';
    protected $casts = [
        'category_ids' => 'array',  // Auto-convert JSON to array
        'isActive' => 'boolean'
    ];
    protected $fillable = [
        'name',
        'image',
        'description',
        'laboratory_id',
        'isActive', // ← ADD THIS
    ];

    

    public $timestamps = true;

    // =============================================
    // 1. CATEGORIES (many-to-many)
    // =============================================
    // app/Models/Equipment.php

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'equipment_categories',  // ← Same as above
            'equipment_id',           // ← foreign key for Equipment
            'category_id'             // ← foreign key for Category
        );
    }

    // =============================================
    // 2. EQUIPMENT ITEMS (one-to-many)
    // =============================================
    public function items(): HasMany
    {
        return $this->hasMany(EquipmentItem::class, 'equipment_id');
    }

    // =============================================
    // 3. TRANSACTIONS (many-to-many via pivot)
    // =============================================
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_items')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    // =============================================
    // 4. LABORATORY (belongsTo)
    // =============================================
    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    // app/Models/Equipment.php

}
