<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

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
                Infolists\Components\Section::make('Informasi Pembelian')
                    ->schema([
                        Infolists\Components\TextEntry::make('pemasok.nama_pemasok')
                            ->label('Pemasok'),
                        Infolists\Components\TextEntry::make('tanggal_pembelian')
                            ->label('Tanggal Pembelian')
                            ->date(),
                        Infolists\Components\TextEntry::make('catatan')
                            ->label('Catatan')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Detail Pembelian')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('pembelianDetails')
                            ->schema([
                                Infolists\Components\TextEntry::make('bahanBaku.nama_bahan')
                                    ->label('Bahan Baku'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantity'),
                                Infolists\Components\TextEntry::make('bahanBaku.satuan.nama_satuan')
                                    ->label('Satuan'),
                                Infolists\Components\TextEntry::make('harga')
                                    ->label('Harga Satuan')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR'),
                            ])
                            ->columns(5),
                    ]),
                
                Infolists\Components\Section::make('Total')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_harga')
                            ->label('Total Harga')
                            ->money('IDR')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])
                    ->columns(1),
                
                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diupdate')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
} 