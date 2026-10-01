<?php

namespace Database\Seeders;

use App\Models\ProductCategories;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Beverages',
            'Snacks',
            'Canned Goods',
            'Rice & Grains',
            'Condiments',
            'Personal Care',
            'Household',
            'Frozen Goods',
        ];

        foreach ($categories as $name) {
            ProductCategories::updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
