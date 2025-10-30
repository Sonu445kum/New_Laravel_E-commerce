<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * (Optional: Laravel will automatically detect "notifications")
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'data',
        'read',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array', // JSON will be automatically converted to array
        'read' => 'boolean',
    ];

    /**
     * Relationship: Each notification belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead()
    {
        $this->update(['read' => true]);
    }
}
