<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens; // for API token authentication

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
     * A user may have multiple notifications.
     */
    public function notificationsCustom()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * (Optional) Coupons used or assigned to the user.
     * Useful if you implement per-user coupon tracking.
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'user_coupons')
                    ->withTimestamps();
    }

    /* ============================================================
     |                        SCOPES
     |============================================================ */

    /**
     * Scope for only active (non-blocked) users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    /**
     * Scope for only admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for only vendor users.
     */
    public function scopeVendors($query)
    {
        return $query->where('role', 'vendor');
    }

    /* ============================================================
     |                        HELPER METHODS
     |============================================================ */

    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has vendor role.
     */
    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    /**
     * Check if user is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }

    /**
     * Soft delete hook — automatically revoke tokens if user deleted.
     */
    protected static function booted()
    {
        static::deleting(function ($user) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        });
    }
}
?>