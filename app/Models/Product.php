<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'name',
        'price',
        'stock'
    ];

    public function stockLogs(): HasMany
    {
        return $this->hasMany(StockLog::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
