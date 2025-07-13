<?php

namespace App\Filament\Resources\ProduksiDetailRelationManagerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\CreateAction;
use Filament\Notifications\Notification;

class ProduksiDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'ProduksiDetail';
    
    protected static ?string $title = 'Bahan Baku';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('bahan_baku_id')
    ->label('Bahan Baku')
    ->relationship('bahanBaku', 'nama_bahan') // ini yang benar
    ->searchable()
    ->required()
    ->reactive()
    ->afterStateUpdated(function ($state, Set $set) {
        $bahan = \App\Models\BahanBaku::find($state);
        $set('satuan', optional($bahan?->satuan)->nama_satuan);
    }),
                TextInput::make('jumlah_digunakan') // Jumlah yang digunakan dalam produksi
                    ->numeric()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $bahanBakuId = $get('bahan_baku_id');
                        if ($bahanBakuId) {
                            $bahan = \App\Models\BahanBaku::find($bahanBakuId);
                            if ($bahan && $state > $bahan->stok) {
                                Notification::make()
                                    ->title('Stok Bahan Baku Tidak Cukup')
                                    ->danger()
                                    ->send();
                                $set('jumlah_digunakan', null);
                            }
                        }
                    })
                    ->rule(function (callable $get) {
                        return function ($attribute, $value, $fail) use ($get) {
                            $bahanBakuId = $get('bahan_baku_id');
                            if ($bahanBakuId) {
                                $bahan = \App\Models\BahanBaku::find($bahanBakuId);
                                if ($bahan && $value > $bahan->stok) {
                                    $fail('Stok bahan baku tidak cukup.');
                                }
                            }
                        };
                    }),
                    TextInput::make('satuan')
                    ->label('Satuan')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('jumlah_digunakan')
            ->columns([
                TextColumn::make('bahanBaku.nama_bahan')->label('Bahan Baku'), // Menampilkan nama bahan baku
                TextColumn::make('jumlah_digunakan'),
                TextColumn::make('bahanBaku.satuan.nama_satuan')->label('Satuan'),
                
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Tambah Bahan Baku'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
