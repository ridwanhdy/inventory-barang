<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Order';
     protected static ?string $navigationGroup = 'Toko';
     protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Customer'),
                
                Forms\Components\Select::make('users_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('User'),
                
                Forms\Components\Repeater::make('orderDetails')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'nama_product')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Product')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = \App\Models\Product::find($state);
                                    if ($product) {
                                        $set('harga', $product->harga_jual);
                                    }
                                }
                            }),
                        
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->label('Quantity'),
                        
                        Forms\Components\TextInput::make('harga')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->label('Harga Product'),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Product')
                    ->label('Detail Order')
                    ->required()
                    ->minItems(1),
                
                Forms\Components\Radio::make('status_transaksi')
                    ->options([
                        'proses' => 'Proses',
                        'batal' => 'Batal',
                        'selesai' => 'Selesai',
                    ])
                    ->required()
                    ->label('Status Transaksi')
                    ->inline()
                    ->default('proses')
                    ->descriptions([
                        'proses' => 'Order sedang diproses',
                        'batal' => 'Order dibatalkan',
                        'selesai' => 'Order selesai',
                    ]),
                
                Forms\Components\Select::make('status_pembayaran')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'cicilan' => 'Cicilan',
                        'lunas' => 'Lunas',
                    ])
                    ->required()
                    ->label('Status Pembayaran'),
                
                Forms\Components\DatePicker::make('tanggal_order')
                    ->required()
                    ->label('Tanggal Order')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.nama')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('User'),
                
                Tables\Columns\TextColumn::make('orderDetails.product.nama_product')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->label('Products'),
                
                Tables\Columns\TextColumn::make('orderDetails.quantity')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->label('Quantities'),
                
                Tables\Columns\TextColumn::make('orderDetails.harga')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->money('IDR')
                    ->label('Harga'),
                
                Tables\Columns\TextColumn::make('status_transaksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'proses' => 'warning',
                        'batal' => 'danger',
                        'selesai' => 'success',
                    })
                    ->searchable()
                    ->sortable()
                    ->label('Status Transaksi'),
                
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->searchable()
                    ->sortable()
                    ->label('Status Pembayaran'),
                
                Tables\Columns\TextColumn::make('tanggal_order')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Order'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
