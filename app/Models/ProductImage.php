<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * Table name explicitly defined.
     */
    protected $table = 'product_images';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'path',
        'alt',
        'sort_order',
    ];

    /**
     * Relationship: Each image belongs to one product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor: Get the full image URL (handles both local and external paths).
     */
    public function getImageUrlAttribute()
    {
        // If path is empty, return null
        if (!$this->path) {
            return null;
        }

        // If stored in 'storage/app/public' and path is relative
        if (!Str::startsWith($this->path, ['http://', 'https://'])) {
            return asset('storage/' . ltrim($this->path, '/'));
        }

        // If already an absolute URL
        return $this->path;
    }

    /**
     * Scope: Order images by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
