<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * =========================================================
     * Mass Assignable Attributes
     * =========================================================
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
        'meta',
    ];

    /**
     * =========================================================
     * Attribute Casting
     * =========================================================
     */
    protected $casts = [
        'meta' => 'array',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * =========================================================
     * Relationships
     * =========================================================
     */

    /**
     * 🔹 Each OrderItem belongs to one Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 🔹 Each OrderItem belongs to one Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 🔹 Each OrderItem may belong to one Product Variant (optional).
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * =========================================================
     * Accessors & Helper Methods
     * =========================================================
     */

    /**
     * 🔹 Dynamically calculate subtotal (quantity × unit price).
     */
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->quantity * $this->unit_price);
    }

    /**
     * 🔹 Accessor for formatted total price.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    /**
     * 🔹 Automatically update total_price when saving or updating.
     * Ensures no manual calculation errors occur.
     */
    protected static function booted()
    {
        static::saving(function (self $item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }

    /**
     * =========================================================
     * Query Scopes
     * =========================================================
     */

    /**
     * 🔹 Scope: Filter by Order ID.
     */
    public function scopeOfOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * 🔹 Scope: Filter by Product ID.
     */
    public function scopeOfProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * =========================================================
     * Utility Methods
     * =========================================================
     */

    /**
     * 🔹 Check if this order item has an associated product variant.
     */
    public function hasVariant(): bool
    {
        return !is_null($this->product_variant_id);
    }

    /**
     * 🔹 Recalculate total manually (if needed externally).
     */
    public function calculateTotal(): float
    {
        return (float) ($this->quantity * $this->unit_price);
    }
}
