<?php

namespace App\Filament\Resources\PemasokResource\Pages;

use App\Filament\Resources\PemasokResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPemasok extends ViewRecord
{
    protected static string $resource = PemasokResource::class;

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
                Infolists\Components\Section::make('Informasi Pemasok')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_pemasok')
                            ->label('Nama Pemasok'),
                        Infolists\Components\TextEntry::make('nomor_telepon')
                            ->label('Nomor Telepon'),
                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
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