<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'total',
    ];

    /**
     * Relationships
     */

    // Each cart item belongs to a cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Each cart item belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Each cart item may belong to a variant
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Automatically calculate total when saving (quantity * price)
     */
    protected static function booted()
    {
        static::saving(function ($item) {
            $item->total = $item->quantity * $item->price;
        });
    }
}
