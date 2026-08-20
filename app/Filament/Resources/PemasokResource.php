<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PemasokResource\Pages;
use App\Models\Pemasok;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PemasokResource extends Resource
{
    protected static ?string $model = Pemasok::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Pemasok';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_pemasok')
                    ->label('Nama Pemasok')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nomor_telepon')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\Textarea::make('alamat')
                    ->label('Alamat')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pemasok')
                    ->label('Nama Pemasok')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_telepon')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPemasoks::route('/'),
            'create' => Pages\CreatePemasok::route('/create'),
            'view' => Pages\ViewPemasok::route('/{record}'),
            'edit' => Pages\EditPemasok::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }
}
