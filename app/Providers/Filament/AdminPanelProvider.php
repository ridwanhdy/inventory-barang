<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use App\Filament\Widgets\InventoryHealth;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\OrderPerMonth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Inventory Barang')
            ->login()
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->font('Poppins')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.partials.readable-fonts'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.partials.theme-colors'),
            )
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                // Keep the brand colors at shade 500 and use darker shades for readable text.
                'primary' => array_replace(Color::hex('#7EC151'), [
                    600 => '80, 127, 47',
                    700 => '63, 102, 37',
                    800 => '49, 90, 31',
                    900 => '39, 71, 26',
                    950 => '24, 51, 34',
                ]),
                'info' => array_replace(Color::hex('#92EEFF'), [
                    600 => '21, 88, 107',
                    700 => '17, 73, 89',
                    800 => '13, 58, 71',
                    900 => '10, 44, 54',
                    950 => '6, 28, 34',
                ]),
                'success' => array_replace(Color::hex('#C4F7CA'), [
                    600 => '40, 90, 52',
                    700 => '33, 74, 43',
                    800 => '26, 59, 34',
                    900 => '20, 45, 26',
                    950 => '13, 29, 17',
                ]),
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                InventoryHealth::class,
                LatestOrders::class,
                OrderPerMonth::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
