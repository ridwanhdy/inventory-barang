<?php

namespace App\Filament\Resources\BahanBakuDetailResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBahanBakuDetail extends ViewRecord
{
    protected static string $resource = BahanBakuDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Bahan Baku')
                    ->schema([
                        Infolists\Components\TextEntry::make('bahanBaku.nama_bahan')
                            ->label('Nama Bahan Baku'),
                        Infolists\Components\TextEntry::make('bahanBaku.kategori.nama_kategori')
                            ->label('Kategori'),
                        Infolists\Components\TextEntry::make('bahanBaku.satuan.nama_satuan')
                            ->label('Satuan'),
                        Infolists\Components\TextEntry::make('bahanBaku.jenis')
                            ->label('Jenis')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'bahan baku' => 'info',
                                'bahan jadi' => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('bahanBaku.deskripsi')
                            ->label('Deskripsi')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Informasi Stok')
                    ->schema([
                        Infolists\Components\TextEntry::make('stok')
                            ->label('Stok Saat Ini')
                            ->badge()
                            ->color(fn (string $state): string => match (true) {
                                $state >= 50 => 'success',
                                $state >= 20 => 'warning',
                                default => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('stok_minimal')
                            ->label('Stok Minimal')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diupdate')
                            ->dateTime(),
                    ])
                    ->columns(4),
            ]);
    }
} 