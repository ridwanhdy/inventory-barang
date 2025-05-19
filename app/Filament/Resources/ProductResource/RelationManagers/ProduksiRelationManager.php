<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProduksiRelationManager extends RelationManager
{
    protected static string $relationship = 'produksi';

    protected static ?string $recordTitleAttribute = 'jumlah_produksi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('jumlah_produksi')
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('produksi_mulai')
                    ->required(),
                Forms\Components\DatePicker::make('produksi_selesai'),
                Forms\Components\Select::make('status')
                    ->options([
                        'Proses' => 'Proses',
                        'Batal' => 'Batal',
                        'Selesai' => 'Selesai',
                    ])
                    ->default('Proses')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('jumlah_produksi')
            ->columns([
                Tables\Columns\TextColumn::make('jumlah_produksi')
                    ->label('Jumlah Produksi'),
                Tables\Columns\TextColumn::make('produksi_mulai')
                    ->label('Tanggal Mulai')
                    ->date(),
                Tables\Columns\TextColumn::make('produksi_selesai')
                    ->label('Tanggal Selesai')
                    ->date(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'Proses',
                        'danger' => 'Batal',
                        'success' => 'Selesai',
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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