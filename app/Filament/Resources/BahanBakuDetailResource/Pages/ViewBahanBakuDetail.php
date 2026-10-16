<?php

namespace App\Filament\Resources\BahanBakuDetailResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\BahanBakuResource;
use App\Support\StockPresentation;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontWeight;

class ViewBahanBakuDetail extends ViewRecord
{
    protected static string $resource = BahanBakuDetailResource::class;

    protected static ?string $title = 'Detail stok bahan baku';

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
                ->visible(fn (): bool => $this->getRecord()->bahanBaku !== null
                    && BahanBakuResource::canEdit($this->getRecord()->bahanBaku))
                ->url(fn (): ?string => $this->getRecord()->bahanBaku
                    ? BahanBakuResource::getUrl('edit', ['record' => $this->getRecord()->bahanBaku])
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
                                $record->bahanBaku?->satuan?->nama_satuan,
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
                                $record->bahanBaku?->satuan?->nama_satuan,
                            )),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir diperbarui')
                            ->timezone('Asia/Jakarta')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Identitas bahan baku')
                    ->schema([
                        Infolists\Components\TextEntry::make('bahanBaku.nama_bahan')
                            ->label('Nama bahan baku')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('bahanBaku.kategori.nama_kategori')
                            ->label('Kategori')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('bahanBaku.satuan.nama_satuan')
                            ->label('Satuan')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
