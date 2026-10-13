<?php

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class ProductUploadPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs(ProductUploadPanelUser::findOrFail($user->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('public');

    $this->productData = [
        'nama_product' => 'Kaos pengujian',
        'kategori_id' => DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos']),
        'satuan_id' => DB::table('satuans')->insertGetId(['nama_satuan' => 'Pcs']),
        'ukuran' => 'M',
        'warna' => 'Hitam',
        'bahan' => 'Katun',
        'harga_jual' => 30000,
    ];
});

function productUploadPng(): string
{
    $chunk = fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    $pixels = str_repeat("\x00" . str_repeat("\x6F\xAE\x45", 40), 60);

    return "\x89PNG\r\n\x1A\n"
        . $chunk('IHDR', pack('NNCCCCC', 40, 60, 8, 2, 0, 0, 0))
        . $chunk('IDAT', gzcompress($pixels))
        . $chunk('IEND', '');
}

test('product photos with identical names are stored separately and remain readable', function () {
    foreach (['Kaos pertama', 'Kaos kedua'] as $name) {
        Livewire::test(CreateProduct::class)
            ->fillForm(array_replace($this->productData, [
                'nama_product' => $name,
                'foto' => UploadedFile::fake()->createWithContent('foto-sama.png', productUploadPng()),
            ]))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    $photos = Product::query()->pluck('foto');

    expect($photos)->toHaveCount(2)
        ->and($photos->unique())->toHaveCount(2);

    foreach ($photos as $photo) {
        expect($photo)->toStartWith('produk/');
        Storage::disk('public')->assertExists($photo);
        expect(Storage::disk('public')->get($photo))->toBe(productUploadPng());
    }
});

test('products can be created and edited without an optional photo', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm($this->productData)
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->sole();
    expect($product->foto)->toBe('');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['harga_jual' => 35000, 'foto' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->foto)->toBe('')
        ->and($product->fresh()->harga_jual)->toBe(35000);
});

test('product upload rejects unsupported file types', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm(array_replace($this->productData, [
            'foto' => UploadedFile::fake()->createWithContent('catatan.txt', 'Bukan foto'),
        ]))
        ->call('create')
        ->assertHasFormErrors(['foto']);

    expect(Product::query()->count())->toBe(0);
});
