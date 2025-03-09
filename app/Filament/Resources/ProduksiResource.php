<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProduksiResource\Pages;
use App\Models\Produksi;
use App\Models\Barang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;

class ProduksiResource extends Resource
{
    protected static ?string $model = Produksi::class;
    protected static ?string $navigationGroup = 'Manajemen Produksi';

    protected static ?string $navigationLabel = 'Produksi';

    protected static ?int $navigationSort = 5; 
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('nama_barang_id')
                    ->label('Barang Jadi')
                    ->options(Barang::where('kategori', 'Barang Jadi')->pluck('nama_barang', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('jumlah_produksi')
                    ->label('Jumlah Produksi (Pcs)')
                    ->numeric()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, $set, $get) => self::cekStokBahanBaku($state, $set, $get)),

                Select::make('bahan_baku_1_id')
                    ->label('Bahan Baku 1')
                    ->options(Barang::where('kategori', 'Bahan Baku')->pluck('nama_barang', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('bahan_baku_2_id')
                    ->label('Bahan Baku 2')
                    ->options(Barang::where('kategori', 'Bahan Baku')->pluck('nama_barang', 'id'))
                    ->searchable()
                    ->required(),

                Radio::make('status')
                    ->label('Status Produksi')
                    ->options([
                        'On Process' => 'On Process',
                        'Selesai' => 'Selesai',
                    ])
                    ->default('On Process')
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barangJadi.nama_barang')->label('Barang Jadi'),
                TextColumn::make('jumlah_produksi')->label('Jumlah Produksi'),
                TextColumn::make('bahanBaku1.nama_barang')->label('Bahan Baku 1'),
                TextColumn::make('bahanBaku2.nama_barang')->label('Bahan Baku 2'),
                TextColumn::make('status')->label('Status Produksi'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }


    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduksis::route('/'),
            'create' => Pages\CreateProduksi::route('/create'),
            'edit' => Pages\EditProduksi::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return self::prosesProduksi($data);
    }

    public static function mutateFormDataBeforeSave(array $data, $record): array
{
    // Hitungan kebutuhan bahan baku per unit produksi
    $kainPerKaos = 0.25; // 0.25 kg kain per kaos
    $benangPerKaos = 0.12; // 0.12 lusin benang per kaos

    // Ambil jumlah produksi yang diinput
    $jumlahProduksi = (float) $data['jumlah_produksi'];

    // Hitung total bahan baku yang dibutuhkan
    $kebutuhanKain = $jumlahProduksi * $kainPerKaos;
    $kebutuhanBenang = $jumlahProduksi * $benangPerKaos;

    // Ambil data bahan baku
    $bahanBaku1 = Barang::find($data['bahan_baku_1_id']);
    $bahanBaku2 = Barang::find($data['bahan_baku_2_id']);

    if (!$bahanBaku1 || !$bahanBaku2) {
        throw new \Exception('Bahan baku tidak ditemukan!');
    }

    // Cek apakah stok cukup sebelum mengurangi
    if ($bahanBaku1->stok < $kebutuhanKain || $bahanBaku2->stok < $kebutuhanBenang) {
        throw new \Exception('Stok bahan baku tidak mencukupi!');
    }

    // Jika produksi baru dibuat atau diedit, kurangi stok bahan baku
    if (!$record->exists || $data['status'] !== $record->status) {
        $bahanBaku1->stok -= $kebutuhanKain; // Kurangi kain
        $bahanBaku2->stok -= $kebutuhanBenang; // Kurangi benang
        $bahanBaku1->save();
        $bahanBaku2->save();
    }

    // Jika status berubah menjadi "Selesai" dan sebelumnya belum selesai, tambahkan stok barang jadi
    if ($data['status'] === 'Selesai' && $record->status !== 'Selesai') {
        $barangJadi = Barang::find($data['nama_barang_id']);

        if ($barangJadi) {
            $barangJadi->stok += $jumlahProduksi;
            $barangJadi->save();
        }
    }

    return $data;
}



    private static function cekStokBahanBaku($jumlah, $set, $get)
    {
        $bahanBaku1 = Barang::find($get('bahan_baku_1_id'));
        $bahanBaku2 = Barang::find($get('bahan_baku_2_id'));

        if (!$bahanBaku1 || !$bahanBaku2) return;

        if ($bahanBaku1->stok < $jumlah || $bahanBaku2->stok < $jumlah) {
            Notification::make()
                ->title('Bahan Baku Tidak Cukup!')
                ->danger()
                ->send();
            $set('jumlah_produksi', 0);
        }
    }

    private static function prosesProduksi(array $data): array
    {
        $bahanBaku1 = Barang::find($data['bahan_baku_1_id']);
        $bahanBaku2 = Barang::find($data['bahan_baku_2_id']);

        if ($bahanBaku1->stok < $data['jumlah_produksi'] || $bahanBaku2->stok < $data['jumlah_produksi']) {
            throw new \Exception('Bahan baku tidak cukup!');
        }

        // Kurangi stok bahan baku
        $bahanBaku1->stok -= $data['jumlah_produksi'];
        $bahanBaku2->stok -= $data['jumlah_produksi'];
        $bahanBaku1->save();
        $bahanBaku2->save();

        return $data;
    }
}
