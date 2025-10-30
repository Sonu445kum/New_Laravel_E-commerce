<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $baseCategories = [
            ['name' => 'Headphones', 'slug' => 'headphones'],
            ['name' => 'Smart Watches', 'slug' => 'smart-watches'],
            ['name' => 'Televisions', 'slug' => 'televisions'],
            ['name' => 'Laptops', 'slug' => 'laptops'],
            ['name' => 'Furniture', 'slug' => 'furniture'],
            ['name' => 'Clothing', 'slug' => 'clothing'],
            ['name' => 'Wallets', 'slug' => 'wallets'],
            ['name' => 'Kitchen Appliances', 'slug' => 'kitchen-appliances'],
            ['name' => 'Home Appliances', 'slug' => 'home-appliances'],
            ['name' => 'Mobiles', 'slug' => 'mobiles'],
            ['name' => 'Tables', 'slug' => 'tables'],
            ['name' => 'Kettles', 'slug' => 'kettles'],
            ['name' => 'Earbuds', 'slug' => 'earbuds'],
            ['name' => 'Shoes', 'slug' => 'shoes'],
            ['name' => 'Water Bottles', 'slug' => 'water-bottles'],
            ['name' => 'Perfumes', 'slug' => 'perfumes'],
            ['name' => 'Baby Products', 'slug' => 'baby-products'],
            ['name' => 'Toys', 'slug' => 'toys'],
            ['name' => 'Watches', 'slug' => 'watches'],
            ['name' => 'Fitness', 'slug' => 'fitness'],
            ['name' => 'Sports', 'slug' => 'sports'],
            ['name' => 'Books', 'slug' => 'books'],
            ['name' => 'Self Help', 'slug' => 'self-help'],
            ['name' => 'Beauty', 'slug' => 'beauty'],
        ];

        // ✅ Ensure total 49 categories (for your 49 products)
        $categories = $baseCategories;

        if (count($categories) < 49) {
            for ($i = count($categories) + 1; $i <= 49; $i++) {
                $categories[] = [
                    'name' => 'Category ' . $i,
                    'slug' => Str::slug('Category ' . $i),
                ];
            }
        }

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('✅ 49 Categories seeded successfully!');
    }
}
