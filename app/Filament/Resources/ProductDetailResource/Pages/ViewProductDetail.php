<?php

namespace App\Filament\Resources\ProductDetailResource\Pages;

use App\Filament\Resources\ProductDetailResource;
use App\Filament\Resources\ProductResource;
use App\Support\StockPresentation;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontWeight;

class ViewProductDetail extends ViewRecord
{
    protected static string $resource = ProductDetailResource::class;

    protected static ?string $title = 'Detail stok produk';

    protected static ?string $breadcrumb = 'Detail stok';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ubah_data_master')
                ->label('Ubah data master')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->product !== null
                    && ProductResource::canEdit($this->getRecord()->product))
                ->url(fn (): ?string => $this->getRecord()->product
                    ? ProductResource::getUrl('edit', ['record' => $this->getRecord()->product])
                    : null),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Ringkasan stok')
                    ->schema([
                        Infolists\Components\TextEntry::make('stok')
                            ->label('Stok saat ini')
                            ->getStateUsing(fn ($record): string => StockPresentation::quantity(
                                $record->stok,
                                $record->product?->satuan?->nama_satuan,
                            ))
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('status_stok')
                            ->label('Status stok')
                            ->getStateUsing(fn ($record): string => StockPresentation::status(
                                $record->stok,
                                StockPresentation::minimum($record),
                            ))
                            ->badge()
                            ->color(fn ($record): string => StockPresentation::color(
                                $record->stok,
                                StockPresentation::minimum($record),
                            )),
                        Infolists\Components\TextEntry::make('stok_minimal')
                            ->label('Stok minimum')
                            ->visible(fn ($record): bool => StockPresentation::minimum($record) !== null)
                            ->getStateUsing(fn ($record): string => StockPresentation::quantity(
                                StockPresentation::minimum($record),
                                $record->product?->satuan?->nama_satuan,
                            )),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir diperbarui')
                            ->timezone('Asia/Jakarta')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Identitas produk')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.nama_product')
                            ->label('Nama produk')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('product.kategori.nama_kategori')
                            ->label('Kategori')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('product.satuan.nama_satuan')
                            ->label('Satuan')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('product.ukuran')
                            ->label('Ukuran')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('product.warna')
                            ->label('Warna')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('product.bahan')
                            ->label('Bahan')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('product.harga_jual')
                            ->label('Harga jual')
                            ->money('IDR'),
                    ])
                    ->columns(2),
            ]);
    }
}
