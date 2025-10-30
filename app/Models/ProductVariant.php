<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'product_variants';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'attributes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Each variant belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor: Get formatted price (e.g., ₹1,499.00)
     */
    public function getFormattedPriceAttribute(): ?string
    {
        if (is_null($this->price)) {
            return null;
        }

        // You can change '₹' to your preferred currency symbol
        return '₹' . number_format($this->price, 2);
    }

    /**
     * Accessor: Check if variant is in stock.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Accessor: Get variant attributes as a readable string.
     * Example: "Size: L, Color: Red"
     */
    public function getAttributeSummaryAttribute(): ?string
    {
        if (empty($this->attributes) || !is_array($this->attributes)) {
            return null;
        }

        return collect($this->attributes)
            ->map(fn($value, $key) => ucfirst($key) . ': ' . ucfirst($value))
            ->implode(', ');
    }

    /**
     * Scope: Active variants only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Variants that are in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope: Filter by a specific attribute (e.g., color, size)
     * Usage: ProductVariant::filterByAttribute('color', 'Red')->get();
     */
    public function scopeFilterByAttribute($query, string $attribute, $value)
    {
        return $query->whereJsonContains('attributes->' . $attribute, $value);
    }
}
