<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $slug = strtolower(str_replace(' ', '-', $category->name));
            $category->name = $slug;
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
