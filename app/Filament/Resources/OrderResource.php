<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentDetailsRelationManager;
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
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin' || auth()->user()->role === 'kasir';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Order')
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
                                ->label('User')
                                ->default(auth()->id())
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\DatePicker::make('tanggal_order')
                                ->required()
                                ->label('Tanggal Order')
                                ->default(now()),

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
                        ])
                        ->columns(2)
                        ->columnSpan('full'),

                    Forms\Components\Wizard\Step::make('Tambah Product')
                        ->schema([
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
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if ($state) {
                                                $product = \App\Models\Product::find($state);
                                                if ($product) {
                                                    $set('harga', $product->harga_jual);
                                                    // Update subtotal when product changes
                                                    $quantity = $get('quantity');
                                                    if ($quantity) {
                                                        $set('subtotal', $quantity * $product->harga_jual);
                                                        // Update total immediately
                                                        $orderDetails = $get('../../orderDetails');
                                                        if ($orderDetails) {
                                                            $total = collect($orderDetails)->sum('subtotal');
                                                            $set('../../total_harga', $total);
                                                        }
                                                    }
                                                }
                                            }
                                        }),
                                    
                                    Forms\Components\TextInput::make('quantity')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->label('Quantity')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $harga = $get('harga');
                                            if ($state && $harga) {
                                                $set('subtotal', $state * $harga);
                                                // Update total immediately
                                                $orderDetails = $get('../../orderDetails');
                                                if ($orderDetails) {
                                                    $total = collect($orderDetails)->sum('subtotal');
                                                    $set('../../total_harga', $total);
                                                }
                                            }
                                        }),
                                    
                                    Forms\Components\TextInput::make('harga')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->label('Harga Product')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $quantity = $get('quantity');
                                            if ($state && $quantity) {
                                                $set('subtotal', $state * $quantity);
                                                // Update total immediately
                                                $orderDetails = $get('../../orderDetails');
                                                if ($orderDetails) {
                                                    $total = collect($orderDetails)->sum('subtotal');
                                                    $set('../../total_harga', $total);
                                                }
                                            }
                                        }),
                                    
                                    Forms\Components\TextInput::make('subtotal')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->label('Subtotal')
                                        ->prefix('Rp')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            // Update total when subtotal changes
                                            $orderDetails = $get('../../orderDetails');
                                            if ($orderDetails) {
                                                $total = collect($orderDetails)->sum('subtotal');
                                                $set('../../total_harga', $total);
                                            }
                                        }),
                                ])
                                ->columns(4)
                                ->defaultItems(1)
                                ->addActionLabel('Tambah Product')
                                ->label('Detail Order')
                                ->required()
                                ->minItems(1)
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $total = collect($state)->sum('subtotal');
                                        $set('total_harga', $total);
                                    }
                                }),
                            
                            Forms\Components\TextInput::make('total_harga')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->label('Total Harga')
                                ->prefix('Rp')
                                ->live()
                                ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                    // Calculate initial total when form is loaded
                                    $orderDetails = $get('orderDetails');
                                    if ($orderDetails) {
                                        $total = collect($orderDetails)->sum('subtotal');
                                        $set('total_harga', $total);
                                    }
                                }),
                        ])
                        ->columnSpan('full'),
                ])
                ->columnSpan('full')
                ->skippable(),
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
                
                Tables\Columns\TextColumn::make('orderDetails.product.nama_product')
                    ->listWithLineBreaks()
                    ->label('Products'),
                
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->getStateUsing(function (Order $record) {
                        return $record->orderDetails->sum('quantity');
                    }),
                
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->getStateUsing(function (Order $record) {
                        return $record->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                    }),
                
                Tables\Columns\TextColumn::make('total_bayar')
                    ->label('Total Bayar')
                    ->money('IDR')
                    ->getStateUsing(function (Order $record) {
                        return $record->payments->sum('jumlah_bayar');
                    }),
                
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
                Tables\Actions\Action::make('print')
                    ->label('Print Invoice')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.print', ['record' => $record]))
                    ->openUrlInNewTab(),
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
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'print' => Pages\PrintInvoice::route('/{record}/print'),
        ];
    }
}
