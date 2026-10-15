<?php

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\BahanBakuDetailResource\Pages\EditBahanBakuDetail;
use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can open and save material stock using metadata from the material master', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $categoryId = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kategori bahan pengujian']);
    $unitId = DB::table('satuans')->insertGetId(['nama_satuan' => 'meter pengujian']);
    $materialId = DB::table('bahan_bakus')->insertGetId([
        'nama_bahan' => 'Kain untuk formulir stok',
        'kategori_id' => $categoryId,
        'satuan_id' => $unitId,
        'jenis' => 'bahan baku',
    ]);
    $stock = BahanBakuDetail::create(['bahan_baku_id' => $materialId, 'stok' => 3]);

    $this->get(BahanBakuDetailResource::getUrl('edit', ['record' => $stock]))->assertOk();

    Livewire::test(EditBahanBakuDetail::class, ['record' => $stock->getRouteKey()])
        ->assertSee('meter pengujian')
        ->assertSee('Kategori bahan pengujian')
        ->fillForm(['stok' => 4.25])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($stock->fresh()->stok)->toBe(4.25)
        ->and($stock->fresh()->bahan_baku_id)->toBe($materialId)
        ->and(BahanBaku::findOrFail($materialId)->satuan_id)->toBe($unitId)
        ->and(BahanBaku::findOrFail($materialId)->kategori_id)->toBe($categoryId);
});
