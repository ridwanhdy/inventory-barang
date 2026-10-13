<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use App\Filament\Resources\ProduksiResource\ProductionActions;
use App\Models\Produksi;
use App\Support\StockPresentation;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProduksi extends ViewRecord
{
    protected static string $resource = ProduksiResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Rincian Produksi '.$this->record->reference;
    }

    public function getSubheading(): string
    {
        return match ($this->record->status) {
            Produksi::DRAFT => 'Langkah berikutnya: periksa kebutuhan bahan, lalu pilih Mulai produksi. Stok belum berubah.',
            Produksi::PROCESS => 'Langkah berikutnya: catat tambahan bahan bila diperlukan, lalu catat hasil dan sisa bahan saat pekerjaan selesai.',
            Produksi::COMPLETED => $this->record->hasil_dari_data_lama
                ? 'Riwayat produksi selesai dari data lama. Jumlah hasil mengikuti jumlah produksi yang tersimpan.'
                : 'Produksi telah selesai. Stok produk disesuaikan dengan hasil layak jual dan riwayat dikunci.',
            default => $this->record->hasStarted()
                ? 'Produksi dibatalkan. Pemakaian dan pengembalian bahan tetap tersimpan sebagai riwayat.'
                : 'Rencana dibatalkan sebelum produksi dimulai. Tidak ada bahan diambil dan stok tidak berubah.',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah rencana'),
            ...array_map(fn ($action) => $action->record($this->record), ProductionActions::make(Actions\Action::class)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\ViewEntry::make('alur')->view('filament.resources.produksi-resource.workflow-guide')->columnSpanFull(),
            Infolists\Components\Section::make('Informasi batch produksi')->schema([
                Infolists\Components\TextEntry::make('reference')->label('Nomor produksi'),
                Infolists\Components\TextEntry::make('status')->label('Tahap produksi')->badge()
                    ->formatStateUsing(fn (string $state): string => Produksi::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Produksi::PROCESS => 'warning', Produksi::COMPLETED => 'success', Produksi::CANCELLED => 'danger', default => 'gray'
                    }),
                Infolists\Components\TextEntry::make('product.nama_product')->label('Produk')->columnSpanFull(),
                Infolists\Components\TextEntry::make('product.warna')->label('Warna'),
                Infolists\Components\TextEntry::make('product.ukuran')->label('Ukuran'),
                Infolists\Components\TextEntry::make('jumlah_produksi')->label('Target produksi')
                    ->formatStateUsing(fn ($state, Produksi $record): string => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan)),
                Infolists\Components\TextEntry::make('produksi_mulai')
                    ->label(fn (Produksi $record): string => ! $record->hasStarted() ? 'Rencana tanggal mulai' : 'Tanggal mulai')->date('d M Y'),
                Infolists\Components\TextEntry::make('catatan')->label('Catatan produksi')->placeholder('—')->columnSpanFull(),
            ])->columns(2),
            Infolists\Components\Section::make('Hasil produksi')->visible(fn (Produksi $record): bool => $record->status === Produksi::COMPLETED)
                ->description(fn (Produksi $record): string => $record->hasil_dari_data_lama
                    ? 'Hasil data lama mengikuti jumlah produksi yang tersimpan. Jumlah rusak belum dicatat. Stok lama tidak dihitung ulang.'
                    : 'Stok produk bertambah hanya dari hasil layak jual. Barang rusak tidak masuk stok penjualan.')
                ->schema([
                    Infolists\Components\TextEntry::make('jumlah_hasil')->label('Hasil layak jual')
                        ->formatStateUsing(fn ($state, Produksi $record): string => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan)),
                    Infolists\Components\TextEntry::make('jumlah_rusak')->label('Hasil rusak / tidak layak jual')
                        ->placeholder('Belum dicatat pada data lama')
                        ->formatStateUsing(fn ($state, Produksi $record): string => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan)),
                    Infolists\Components\TextEntry::make('produksi_selesai')->label('Tanggal selesai')->date('d M Y'),
                    Infolists\Components\TextEntry::make('catatan_hasil')->label('Catatan hasil produksi')->placeholder('—')->columnSpanFull(),
                ])->columns(3),
            Infolists\Components\Section::make('Pembatalan')->visible(fn (Produksi $record): bool => $record->status === Produksi::CANCELLED)
                ->schema([
                    Infolists\Components\TextEntry::make('alasan_batal')->label('Alasan pembatalan')->placeholder('Tidak dicatat pada data lama'),
                    Infolists\Components\TextEntry::make('tanggal_batal')->label('Tanggal dibatalkan')->date('d M Y')->placeholder('Tidak dicatat pada data lama'),
                ])->columns(2),
        ]);
    }
}
