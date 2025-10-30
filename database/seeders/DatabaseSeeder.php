<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting full database seeding process...');

        // ✅ Step 1: Create a default admin/test user
        $this->seedDefaultUser();

        // ✅ Step 2: Seed categories, products, and reviews
        $this->call([
        CategorySeeder::class,
        ProductSeeder::class,
        UserSeeder::class,
        ReviewsSeeder::class,
    ]);

        $this->command->info('✅ All database tables seeded successfully!');
    }

    /**
     * Create a default user for testing/admin access.
     */
    protected function seedDefaultUser(): void
    {
        // ✅ Check if the user already exists
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);

            $this->command->info('👤 Default user created: test@example.com | password');
        } else {
            $this->command->warn('⚠️ Default user already exists, skipping user creation...');
        }
    }
}
