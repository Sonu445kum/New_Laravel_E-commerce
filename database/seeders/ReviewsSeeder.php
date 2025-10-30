<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ✅ Load all review data from your data file
        $reviews = include database_path('seeders/data/reviews.php');

        // ✅ Loop through and insert into DB
        foreach ($reviews as $review) {
            Review::create([
                'user_id'     => $review['user_id'],
                'product_id'  => $review['product_id'],
                'rating'      => $review['rating'],
                'comment'     => $review['comment'],
                'images'      => $review['images'] ?? null,
                'is_approved' => $review['is_approved'] ?? false,
            ]);
        }

        // Optional: You can log a message in console
        $this->command->info('✅ Reviews table seeded successfully!');
    }
}
