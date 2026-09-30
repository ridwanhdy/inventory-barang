<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Support\DashboardMetrics;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->check() && OrderResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Penjualan terbaru')
            ->description('Lima transaksi terakhir berdasarkan tanggal order.')
            ->query(
                Order::query()
                    ->withSum('orderDetails as calculated_total', 'subtotal')
                    ->orderByDesc('tanggal_order')
                    ->orderByDesc('id')
                    ->limit(5)
            )
            ->paginated(false)
            ->striped()
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no_transaksi')
                    ->label('No. transaksi')
                    ->visibleFrom('md')
                    ->weight('medium')
                    ->placeholder('Belum tersedia'),
                TextColumn::make('nama_customer')
                    ->label('Pelanggan')
                    ->wrap()
                    ->limit(28)
                    ->tooltip(fn (Order $record): ?string => $record->nama_customer)
                    ->placeholder('Tanpa nama'),
                TextColumn::make('total_harga')
                    ->label('Nilai transaksi')
                    ->getStateUsing(fn (Order $record): float => DashboardMetrics::orderTotal($record))
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('status_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'belum_bayar' => 'Belum bayar',
                        'cicilan' => 'Cicilan',
                        'lunas' => 'Lunas',
                        default => 'Status tidak diketahui',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'lunas' => 'success',
                        'cicilan' => 'warning',
                        'belum_bayar' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tanggal_order')
                    ->label('Tanggal order')
                    ->visibleFrom('md')
                    ->date('d M Y')
                    ->placeholder('Belum tersedia'),
            ])
            ->headerActions([
                Action::make('allOrders')
                    ->label('Lihat semua')
                    ->icon('heroicon-m-arrow-up-right')
                    ->link()
                    ->url(fn (): ?string => OrderResource::canViewAny() ? OrderResource::getUrl('index') : null)
                    ->visible(fn (): bool => OrderResource::canViewAny()),
            ])
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading('Belum ada penjualan')
            ->emptyStateDescription('Transaksi yang dicatat akan muncul di sini.');
    }
}
