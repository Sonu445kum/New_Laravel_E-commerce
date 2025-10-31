<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * Handles user authentication, roles, soft deletes,
 * email verification, and relationships with orders, reviews, wishlist, etc.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_blocked',
        'is_admin',     // for IsAdmin middleware
        'is_active',    // for user status management
    ];

    /**
     * The attributes that should be hidden for arrays or JSON responses.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_blocked' => 'boolean',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Automatically hash password whenever it's set.
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /* ============================================================
     |                        RELATIONSHIPS
     |============================================================ */

    /**
     * A user can have many orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * A user can write multiple product reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * A user can receive many custom notifications.
     */
    public function notificationsCustom()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * A user can have many coupons.
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'user_coupons')->withTimestamps();
    }

    /**
     * 🆕 A user can have many wishlist items.
     */
    public function wishlist()
    {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    /* ============================================================
     |                        SCOPES
     |============================================================ */

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false)->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin')->orWhere('is_admin', true);
    }

    public function scopeVendors($query)
    {
        return $query->where('role', 'vendor');
    }

    /* ============================================================
     |                        HELPER METHODS
     |============================================================ */

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || (bool) $this->is_admin;
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /* ============================================================
     |                        EVENTS / HOOKS
     |============================================================ */

    protected static function booted()
    {
        static::deleting(function ($user) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        });
    }
}
