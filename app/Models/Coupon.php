<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'starts_at',
        'expires_at',
        'usage_limit',
        'used_count',
        'min_order_amount',
        'applies_to',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applies_to' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to only get active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Check if the coupon is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount based on type and order total.
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if (!$this->isValid() || $orderTotal < ($this->min_order_amount ?? 0)) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = ($orderTotal * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        // Prevent discount from exceeding order total
        return min($discount, $orderTotal);
    }

    /**
     * Increment usage count after applying coupon.
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Check if coupon applies to a given product or category.
     *
     * Example: applies_to = ["type" => "category", "ids" => [1,2,3]]
     */
    public function appliesToItem($item): bool
    {
        if (!$this->applies_to || !is_array($this->applies_to)) {
            return true; // global coupon
        }

        $type = $this->applies_to['type'] ?? null;
        $ids = $this->applies_to['ids'] ?? [];

        if ($type === 'category' && in_array($item->category_id, $ids)) {
            return true;
        }

        if ($type === 'product' && in_array($item->id, $ids)) {
            return true;
        }

        return false;
    }
}
