<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'subtotal',
        'shipping',
        'tax',
        'discount',
        'total',
        'status',
        'notes',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'meta' => 'array',
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * ========================================
     * Model Booted: Auto-generate Order Number
     * ========================================
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * ========================================
     * Relationships
     * ========================================
     */

    // 🔹 Each order belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Each order belongs to one address (shipping/billing)
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    // 🔹 Each order has many order items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // 🔹 Each order can have multiple payments (retries, refunds)
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // 🔹 Fetch only the latest successful payment
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * ========================================
     * Accessors & Helper Methods
     * ========================================
     */

    // 🔹 Get formatted total with 2 decimals
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2);
    }

    // 🔹 Dynamically compute total (useful for recalculation)
    public function getComputedTotalAttribute(): float
    {
        return (float) ($this->subtotal + $this->shipping + $this->tax - $this->discount);
    }

    // 🔹 Check if order is delivered
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    // 🔹 Check if any successful payment exists
    public function isPaid(): bool
    {
        return $this->payments()->where('status', 'success')->exists();
    }

    /**
     * ========================================
     * Query Scopes (for dashboard filters)
     * ========================================
     */

    // 🔹 Filter by user
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // 🔹 Filter by order status
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // 🔹 Filter orders of current month
    public function scopeMonthly($query)
    {
        return $query->whereMonth('created_at', now()->month);
    }

    // 🔹 Calculate total revenue from delivered orders
    public function scopeRevenue($query)
    {
        return $query->where('status', 'delivered')->sum('total');
    }
}
