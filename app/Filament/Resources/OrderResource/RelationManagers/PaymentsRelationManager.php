<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('jumlah_bayar')
                    ->required()
                    ->numeric()
                    ->label('Jumlah Bayar')
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('sisa_bayar')
                    ->required()
                    ->numeric()
                    ->label('Sisa Bayar')
                    ->prefix('Rp'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('jumlah_bayar')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah Bayar'),
                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->money('IDR')
                    ->sortable()
                    ->label('Sisa Bayar'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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