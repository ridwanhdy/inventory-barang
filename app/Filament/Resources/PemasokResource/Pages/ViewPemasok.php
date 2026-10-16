<?php

namespace App\Filament\Resources\PemasokResource\Pages;

use App\Filament\Resources\PemasokResource;
use App\Filament\Resources\PembelianResource;
use App\Models\Pemasok;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPemasok extends ViewRecord
{
    protected static string $resource = PemasokResource::class;

    protected static ?string $title = 'Detail pemasok';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Lihat kontak pemasok dan ringkasan penerimaan bahan baku untuk menelusuri kerja sama.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah pemasok'),
            Actions\Action::make('riwayat_penerimaan')
                ->label('Riwayat penerimaan')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->visible(fn (): bool => PembelianResource::canViewAny())
                ->url(fn (): string => PembelianResource::getUrl('index', [
                    'tableFilters' => ['pemasok' => ['value' => $this->getRecord()->getKey()]],
                ])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pemasok')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_pemasok')
                            ->label('Nama Pemasok'),
                        Infolists\Components\TextEntry::make('nomor_telepon')
                            ->label('Nomor Telepon')
                            ->copyable()
                            ->copyMessage('Nomor telepon disalin'),
                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Riwayat penerimaan')
                    ->description('Pemasok yang sudah memiliki penerimaan tidak dapat dihapus agar riwayat transaksi tetap tersimpan.')
                    ->schema([
                        Infolists\Components\TextEntry::make('jumlah_penerimaan')
                            ->label('Jumlah penerimaan')
                            ->getStateUsing(fn (Pemasok $record): int => $record->pembelians()->count()),
                        Infolists\Components\TextEntry::make('penerimaan_terakhir')
                            ->label('Penerimaan terakhir')
                            ->getStateUsing(fn (Pemasok $record) => $record->pembelians()->max('tanggal_pembelian'))
                            ->date('d M Y')
                            ->placeholder('Belum ada penerimaan'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i')
                            ->timezone('Asia/Jakarta'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir diperbarui')
                            ->dateTime('d M Y, H:i')
                            ->timezone('Asia/Jakarta'),
                    ])
                    ->columns(2),
            ]);
    }
}
