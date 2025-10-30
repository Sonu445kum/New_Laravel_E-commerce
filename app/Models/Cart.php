<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'subtotal',
    ];

    /**
     * Relationships
     */

    // Each cart belongs to one user (nullable for guest users)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A cart can have many items
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate total subtotal dynamically from items (optional)
     */
    public function calculateSubtotal()
    {
        return $this->items->sum('total');
    }

    /**
     * Automatically update subtotal when items change (optional hook)
     */
    protected static function booted()
    {
        static::saving(function ($cart) {
            if ($cart->relationLoaded('items')) {
                $cart->subtotal = $cart->calculateSubtotal();
            }
        });
    }
}
