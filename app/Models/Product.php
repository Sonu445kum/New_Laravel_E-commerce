<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * ----------------------------------------
     * MASS ASSIGNABLE FIELDS
     * ----------------------------------------
     * These attributes can be created or updated
     * via mass assignment (e.g., Product::create()).
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'featured_image',      // ✅ Added featured image field
        'price',
        'discounted_price',
        'sku',
        'stock',
        'is_active',
        'is_featured',
        'meta',
    ];

    /**
     * ----------------------------------------
     * TYPE CASTS
     * ----------------------------------------
     * Converts database columns to native PHP types automatically.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'meta' => 'array',
        'price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
    ];

    /**
     * ----------------------------------------
     * RELATIONSHIPS
     * ----------------------------------------
     */

    // Each product belongs to one category.
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // One product can have multiple images.
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // One product can have multiple variants (e.g., sizes, colors).
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // One product can have multiple reviews from different users.
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Each product can appear in multiple cart items.
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Each product can appear in multiple order items.
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * ----------------------------------------
     * ACCESSORS & HELPERS
     * ----------------------------------------
     */

    // Accessor: Get the current/final price (discounted if available)
    public function getFinalPriceAttribute()
    {
        return $this->discounted_price ?? $this->price;
    }

    // Accessor: Get the full URL of the featured image
    public function getFeaturedImageUrlAttribute()
    {
        // If you are storing URLs directly (like from picsum or Cloudinary)
        if (filter_var($this->featured_image, FILTER_VALIDATE_URL)) {
            return $this->featured_image;
        }

        // Otherwise, return from local storage path (e.g., storage/products/)
        return $this->featured_image 
            ? asset('storage/' . $this->featured_image)
            : asset('images/default-product.jpg'); // fallback image
    }

    // Helper: Get the average rating of the product
    public function avgRating()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    // Helper: Check if product is in stock
    public function isInStock()
    {
        return $this->stock > 0;
    }

    // Helper: Return product short description (for UI previews)
    public function shortDescription($limit = 100)
    {
        return str()->limit(strip_tags($this->description), $limit);
    }
}
