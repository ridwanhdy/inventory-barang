<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Models\Order;
use App\Models\Product;
use App\Support\StockPresentation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $modelLabel = 'Penjualan Produk';
    protected static ?string $pluralModelLabel = 'Penjualan Produk';
    protected static ?string $navigationLabel = 'Penjualan Produk';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $activeNavigationIcon = 'heroicon-s-shopping-cart';

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'kasir'], true);
    }

    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canView(Model $record): bool { return static::canViewAny(); }
    public static function canEdit(Model $record): bool { return static::canViewAny(); }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function rupiah(float|int|string|null $amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }

    protected static function updateLineTotal(Forms\Get $get, Forms\Set $set): void
    {
        $set('subtotal', (int) $get('quantity') * (int) $get('harga'));
        $items = $get('../../orderDetails') ?? [];
        $set('../../total_harga', collect($items)->sum(fn ($item) => (int) ($item['quantity'] ?? 0) * (int) ($item['harga'] ?? 0)));
    }

    public static function form(Form $form): Form
    {
        $locked = fn (?Order $record): bool => ($sale = $record?->fresh()) !== null && ! $sale->canEditItems();

        return $form->schema([
            Forms\Components\Hidden::make('status_transaksi')
                ->default('proses')
                ->dehydrated(fn (string $operation): bool => $operation === 'create'),
            Forms\Components\Section::make('Pelanggan dan tanggal')
                ->schema([
                    Forms\Components\TextInput::make('nama_customer')
                        ->label('Nama pelanggan')
                        ->placeholder('Nama pelanggan atau toko')
                        ->required()->maxLength(255)->disabled($locked),
                    Forms\Components\DatePicker::make('tanggal_order')
                        ->label('Tanggal penjualan')
                        ->default(now('Asia/Jakarta'))->required()->disabled($locked),
                    Forms\Components\Select::make('users_id')
                        ->label('Petugas kasir')
                        ->relationship('user', 'name')
                        ->default(auth()->id())->disabled()->dehydrated()->required(),
                    Forms\Components\TextInput::make('no_transaksi')
                        ->label('Nomor penjualan')
                        ->placeholder('Dibuat otomatis setelah disimpan')
                        ->disabled()->dehydrated(false),
                ])->columns(2),
            Forms\Components\Section::make('Produk yang dijual')
                ->description(fn (?Order $record): string => ($sale = $record?->fresh()) !== null && ! $sale->canEditItems()
                    ? 'Daftar produk dikunci setelah pembayaran dicatat atau barang diserahkan.'
                    : 'Pilih varian dan jumlah. Stok produk berkurang ketika penjualan disimpan.')
                ->schema([
                    Forms\Components\Repeater::make('orderDetails')
                        ->label('Daftar produk')
                        ->disabled($locked)
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Produk, warna, dan ukuran')
                                ->relationship('product', 'nama_product', modifyQueryUsing: fn (Builder $query) => $query->with('satuan')->withSum('details', 'stok'))
                                ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->nama_product . ' · ' . $record->warna . ' · ' . $record->ukuran . ' · Stok ' . StockPresentation::quantity($record->details_sum_stok, $record->satuan?->nama_satuan))
                                ->searchable(['nama_product', 'warna', 'ukuran'])->preload()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required()->live()->columnSpanFull()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                    $set('harga', Product::find($state)?->harga_jual ?? 0);
                                    static::updateLineTotal($get, $set);
                                }),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Jumlah dijual')->integer()->minValue(1)->default(1)->required()
                                ->live(debounce: 400)
                                ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => static::updateLineTotal($get, $set)),
                            Forms\Components\TextInput::make('harga')
                                ->label('Harga per produk')->prefix('Rp')->numeric()->disabled()->dehydrated()->required(),
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')->prefix('Rp')->numeric()->disabled()->dehydrated(false),
                        ])->columns(3)->defaultItems(1)->minItems(1)->required()->reorderable(false)
                        ->addActionLabel('Tambah produk')->live()
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('total_harga', collect($state ?? [])->sum(fn ($item) => (int) ($item['quantity'] ?? 0) * (int) ($item['harga'] ?? 0)))),
                    Forms\Components\TextInput::make('total_harga')
                        ->label('Total penjualan')->prefix('Rp')->disabled()->dehydrated(false)
                        ->afterStateHydrated(fn (Forms\Set $set, Forms\Get $get) => $set('total_harga', collect($get('orderDetails') ?? [])->sum(fn ($item) => (int) ($item['quantity'] ?? 0) * (int) ($item['harga'] ?? 0)))),
                ]),
            Forms\Components\Section::make('Status penjualan')
                ->visible(fn (?Order $record): bool => $record?->fresh() !== null)
                ->schema([
                    Forms\Components\Placeholder::make('status_barang')
                        ->label('Penyerahan barang')
                        ->content(fn (?Order $record): string => match ($record?->fresh()?->status_transaksi) {
                            'selesai' => 'Barang sudah diserahkan',
                            'batal' => 'Penjualan dibatalkan',
                            default => 'Barang sedang disiapkan',
                        }),
                    Forms\Components\Placeholder::make('status_bayar')
                        ->label('Pembayaran')
                        ->content(fn (?Order $record): string => match ($record?->fresh()?->status_pembayaran) {
                            'lunas' => 'Lunas',
                            'cicilan' => 'Dibayar sebagian',
                            default => 'Belum dibayar',
                        }),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum('orderDetails', 'subtotal')
                ->withSum('payments', 'jumlah_bayar')
                ->withSum('payments', 'kembalian'))
            ->searchPlaceholder('Cari nomor atau pelanggan')->defaultSort('tanggal_order', 'desc')->striped()
            ->columns([
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->label('Penjualan')->weight(FontWeight::Medium)
                    ->description(fn (Order $record): string => $record->nama_customer)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('no_transaksi', 'like', "%{$search}%")->orWhere('nama_customer', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total')->getStateUsing(fn (Order $record): float => (float) $record->order_details_sum_subtotal)
                    ->formatStateUsing(fn ($state): string => static::rupiah($state)),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Pembayaran')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lunas' => 'Lunas', 'cicilan' => 'Dibayar sebagian', default => 'Belum dibayar',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lunas' => 'success', 'cicilan' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sisa_tagihan')
                    ->label('Sisa tagihan')->getStateUsing(fn (Order $record): float => $record->status_transaksi === 'batal'
                        ? 0.0
                        : max(0, (float) $record->order_details_sum_subtotal - max(0, (float) $record->payments_sum_jumlah_bayar - (float) $record->payments_sum_kembalian)))
                    ->formatStateUsing(fn ($state): string => static::rupiah($state)),
                Tables\Columns\TextColumn::make('status_transaksi')
                    ->label('Barang')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'selesai' => 'Diserahkan', 'batal' => 'Dibatalkan', default => 'Disiapkan',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'selesai' => 'success', 'batal' => 'danger', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('tanggal_order')
                    ->label('Tanggal penjualan')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_transaksi')
                    ->label('Penyerahan barang')
                    ->options(['proses' => 'Disiapkan', 'selesai' => 'Diserahkan', 'batal' => 'Dibatalkan']),
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Pembayaran')
                    ->options(['belum_bayar' => 'Belum dibayar', 'cicilan' => 'Dibayar sebagian', 'lunas' => 'Lunas']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Detail / bayar'),
                Tables\Actions\Action::make('print')->label('Cetak struk')->icon('heroicon-o-printer')
                    ->url(fn (Order $record): string => route('order.print-struk', $record))->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada penjualan yang ditampilkan')
            ->emptyStateDescription('Buat penjualan baru atau ubah pencarian dan filter.');
    }

    public static function getRelations(): array { return [PaymentsRelationManager::class]; }

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
