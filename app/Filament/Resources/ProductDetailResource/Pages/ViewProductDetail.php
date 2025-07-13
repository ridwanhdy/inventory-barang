<?php

namespace App\Filament\Resources\ProductDetailResource\Pages;

use App\Filament\Resources\ProductDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewProductDetail extends ViewRecord
{
    protected static string $resource = ProductDetailResource::class;

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
                Infolists\Components\Section::make('Informasi Produk')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.nama_product')
                            ->label('Nama Produk'),
                        Infolists\Components\TextEntry::make('product.kategori.nama_kategori')
                            ->label('Kategori'),
                        Infolists\Components\TextEntry::make('product.satuan.nama_satuan')
                            ->label('Satuan'),
                        Infolists\Components\TextEntry::make('product.ukuran')
                            ->label('Ukuran'),
                        Infolists\Components\TextEntry::make('product.warna')
                            ->label('Warna'),
                        Infolists\Components\TextEntry::make('product.bahan')
                            ->label('Bahan'),
                        Infolists\Components\TextEntry::make('product.harga_jual')
                            ->label('Harga Jual')
                            ->money('IDR'),
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
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diupdate')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }
} 