<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InventoryHealth;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrderPerMonth;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Enums\MaxWidth;

class Dashboard extends BasePage
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.dashboard';

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public function getColumns(): int|string|array
    {
        return ['default' => 1, 'xl' => 2];
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            OrderPerMonth::class,
            InventoryHealth::class,
            LatestOrders::class,
        ];
    }
}
