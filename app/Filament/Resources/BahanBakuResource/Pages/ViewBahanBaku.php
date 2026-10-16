<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\BahanBakuResource;
use App\Filament\Resources\PembelianResource;
use App\Models\BahanBaku;
use App\Support\MasterDataUsage;
use App\Support\StockPresentation;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBahanBaku extends ViewRecord
{
    protected static string $resource = BahanBakuResource::class;

    protected static ?string $title = 'Detail bahan baku';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Tinjau identitas bahan dan stok tersedia sebelum mencatat penerimaan atau produksi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah bahan'),
            Actions\Action::make('lihat_stok')
                ->label('Lihat stok')
                ->icon('heroicon-o-cube')
                ->color('gray')
                ->visible(fn (): bool => BahanBakuDetailResource::canViewAny())
                ->url(fn (): string => BahanBakuDetailResource::getUrl('index', [
                    'tableFilters' => ['bahanBaku' => ['value' => $this->getRecord()->getKey()]],
                ])),
            Actions\Action::make('catat_penerimaan')
                ->label('Catat bahan masuk')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->jenis === 'bahan baku' && PembelianResource::canCreate())
                ->url(PembelianResource::getUrl('create')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identitas bahan')
                ->schema([
                    Infolists\Components\TextEntry::make('nama_bahan')->label('Nama bahan')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('kategori.nama_kategori')->label('Kategori'),
                    Infolists\Components\TextEntry::make('satuan.nama_satuan')->label('Satuan'),
                ])->columns(2),
            Infolists\Components\Section::make('Stok dan pemakaian')
                ->description('Stok bertambah melalui penerimaan bahan baku dan berkurang melalui pemakaian produksi.')
                ->schema([
                    Infolists\Components\TextEntry::make('stok_tersedia')
                        ->label('Stok saat ini')
                        ->getStateUsing(fn (BahanBaku $record): string => StockPresentation::quantity(
                            $record->details()->sum('stok'),
                            $record->satuan?->nama_satuan,
                        )),
                    Infolists\Components\TextEntry::make('status_penggunaan')
                        ->label('Status penghapusan')
                        ->getStateUsing(fn (BahanBaku $record): string => MasterDataUsage::isUsed($record)
                            ? 'Tidak dapat dihapus karena sudah digunakan atau memiliki stok.'
                            : 'Belum digunakan dan tidak memiliki stok; dapat dihapus.'),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Dibuat pada')
                        ->dateTime('d M Y, H:i')
                        ->timezone('Asia/Jakarta'),
                    Infolists\Components\TextEntry::make('updated_at')
                        ->label('Terakhir diperbarui')
                        ->dateTime('d M Y, H:i')
                        ->timezone('Asia/Jakarta'),
                ])->columns(2),
        ]);
    }
}
