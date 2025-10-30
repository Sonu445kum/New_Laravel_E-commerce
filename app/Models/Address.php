<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'label',
        'name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns this address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only default address.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Format the full address nicely.
     */
    public function getFullAddressAttribute(): string
    {
        $address = "{$this->address_line1}";
        if (!empty($this->address_line2)) {
            $address .= ", {$this->address_line2}";
        }
        $address .= ", {$this->city}, {$this->state} - {$this->postal_code}, {$this->country}";
        return $address;
    }
}
