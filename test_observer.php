<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductDetail;

echo "Testing ProductObserver...\n";

echo "Before creating product: " . ProductDetail::count() . " ProductDetails\n";

// Create a new product
$product = Product::create([
    'nama_product' => 'Test Product Auto',
    'kategori_id' => 1,
    'satuan_id' => 1,
    'ukuran' => 'M',
    'warna' => 'Hitam',
    'bahan' => 'Katun',
    'harga_jual' => 50000,
    'foto' => ''
]);

echo "After creating product: " . ProductDetail::count() . " ProductDetails\n";
echo "New Product ID: " . $product->id . "\n";
echo "Auto-created ProductDetail stok: " . $product->details->first()->stok . "\n";

// Clean up - delete the test product
$product->delete();
echo "Test completed and cleaned up!\n"; 