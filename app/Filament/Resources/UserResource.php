<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\MasterDataUsage;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->label('Kata sandi')
                    ->password()
                    ->maxLength(255)
                    ->afterStateHydrated(fn (Forms\Components\TextInput $component) => $component->state(null))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText(fn (string $operation): ?string => $operation === 'edit'
                        ? 'Kosongkan jika kata sandi tidak diubah.'
                        : null),
                Forms\Components\Select::make('role')
                    ->label('Peran')
                    ->options([
                        'admin' => 'Admin',
                        'kasir' => 'Kasir',
                    ])
                    ->default('kasir')
                    ->in(['admin', 'kasir'])
                    ->helperText('Admin mengakses seluruh fitur. Kasir mengakses dashboard, penjualan, laporan, dan stok.')
                    ->required(),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'admin'
            && ! $record->is(auth()->user())
            && ! MasterDataUsage::isUsed($record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'kasir' => 'Kasir',
                        default => 'Peran tidak dikenal',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'admin' => 'danger',
                        'kasir' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
