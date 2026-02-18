<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Vendor;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = Vendor::create(['name' => 'Acme Supplies']);

        for ($i = 1; $i <= 5000; $i++) {
            Product::create([
                'vendor_id' => $vendor->id,
                'name' => 'Product ' . $i,
                'sku' => 'SKU-' . $i,
                'price' => (string) rand(10, 500), // string on purpose
                'metadata' => json_encode(['i' => $i]),
            ]);
        }
    }
}
