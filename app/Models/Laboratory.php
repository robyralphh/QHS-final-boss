<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Laboratory extends Model
{
    use HasFactory;

    // Specify the table name (optional, if it follows Laravel's naming convention)
    protected $table = 'laboratory';

    
    // Define fillable fields for mass assignment
    protected $fillable = [
        'name',
        'location',
        'description',
        'custodianID',
        'isActive',
        'gallery',
    ];

    // Define guarded fields (alternative to fillable)
    // protected $guarded = [];

    // Define timestamps (optional, defaults to true)
    public $timestamps = true;
    public function items()
{
    return $this->hasMany(EquipmentItem::class);
}

public function borrowLogs()
{
    return $this->hasMany(BorrowLog::class);
}
    // Define custom date formats (optional)

    // Define relationships (if any)
    // Example: A laboratory has many users
    public function users()
    {
        return $this->hasMany(User::class);
    }
    // app/Models/Laboratory.php
public function transactions()
{
    return $this->hasMany(Transaction::class);
}

public function equipment()
{
    return $this->hasMany(Equipment::class);
}

}
