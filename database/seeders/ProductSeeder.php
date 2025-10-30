<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //  Step 1: Define the path of the product data file
        $dataFile = database_path('seeders/data/products.php');

        //  Step 2: Check if file exists to avoid errors
        if (!File::exists($dataFile)) {
            $this->command->error("❌ Product data file not found at: {$dataFile}");
            return;
        }

        //  Step 3: Include product data
        $products = include $dataFile;

        //  Step 4: Validate data format (must be an array)
        if (!is_array($products)) {
            $this->command->error('❌ Invalid product data format. Expected an array.');
            return;
        }

        //  Step 5: Seed products into the database
        foreach ($products as $product) {
            try {
                Product::create($product);
            } catch (\Exception $e) {
                // Catch errors for individual entries
                $this->command->warn("⚠️ Failed to insert product: " . ($product['title'] ?? 'Unknown'));
                $this->command->warn("Error: " . $e->getMessage());
            }
        }

        //  Step 6: Success message
        $this->command->info('✅ ' . count($products) . ' products seeded successfully!');
    }
}
