<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * ==============================
     * Table Configuration
     * ==============================
     */
    protected $fillable = [
        'order_id',
        'transaction_id',
        'status',
        'method',
        'amount',
        'meta',
    ];

    /**
     * ==============================
     * Casting Configuration
     * ==============================
     */
    protected $casts = [
        'meta' => 'array', // store and retrieve JSON data as PHP array
        'amount' => 'decimal:2',
    ];

    /**
     * ==============================
     * Relationships
     * ==============================
     */

    // Each Payment belongs to a specific Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * ==============================
     * Accessors & Helpers
     * ==============================
     */

    // Check if payment was successful
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    // Check if payment is pending
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // Check if payment failed
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Check if payment refunded
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * ==============================
     * Query Scopes
     * ==============================
     */

    // Scope: Filter by status
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Scope: Filter by payment method
    public function scopeMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    // Scope: Filter successful payments
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    // Scope: Filter failed payments
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * ==============================
     * Utility Methods
     * ==============================
     */

    // Get formatted payment amount (for displaying in views)
    public function formattedAmount(): string
    {
        return number_format($this->amount, 2);
    }

    // Get gateway transaction ID or fallback
    public function getTransactionReference(): string
    {
        return $this->transaction_id ?? 'N/A';
    }
}
