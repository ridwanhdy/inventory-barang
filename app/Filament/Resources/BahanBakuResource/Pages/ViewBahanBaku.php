<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuResource;
use App\Models\BahanBakuHistory;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;

class ViewBahanBaku extends ViewRecord
{
    protected static string $resource = BahanBakuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('increaseStock')
                ->label('Tambah Stok')
                ->icon('heroicon-o-plus')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->minValue(0.01),
                    \Filament\Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'stok' => $this->record->stok + $data['amount']
                    ]);

                    // Simpan history
                    BahanBakuHistory::create([
                        'bahan_baku_id' => $this->record->id,
                        'jumlah_perubahan' => $data['amount'],
                        'tipe_perubahan' => 'tambah',
                        'keterangan' => $data['keterangan'] ?? null,
                    ]);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Bahan Baku')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_bahan')
                            ->label('Nama Bahan'),
                        Infolists\Components\TextEntry::make('satuan.nama_satuan')
                            ->label('Satuan'),
                        Infolists\Components\TextEntry::make('kategori.nama_kategori')
                            ->label('Kategori'),
                        Infolists\Components\TextEntry::make('jenis.nama_jenis')
                            ->label('Jenis'),
                    ])->columns(2),

                Infolists\Components\Section::make('Informasi Stok')
                    ->schema([
                        Infolists\Components\TextEntry::make('stok')
                            ->label('Stok Saat Ini')
                            ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->satuan->nama_satuan),
                        Infolists\Components\TextEntry::make('stok_minimal')
                            ->label('Stok Minimal')
                            ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->satuan->nama_satuan),
                    ])->columns(2),

                Infolists\Components\Section::make('Riwayat Stok Masuk')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('histories')
                            ->schema([
                                Infolists\Components\TextEntry::make('jumlah_perubahan')
                                    ->label('Jumlah Masuk')
                                    ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->bahanBaku->satuan->nama_satuan),
                                Infolists\Components\TextEntry::make('keterangan')
                                    ->label('Keterangan'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Tanggal Masuk')
                                    ->dateTime('d M Y H:i'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
} 