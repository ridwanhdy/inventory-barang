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
            ->databaseTransactions()
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
                'gray' => array_replace(Color::Slate, [
                    500 => '101, 112, 135',
                    600 => '78, 91, 117',
                    700 => '50, 67, 102',
                    800 => '34, 57, 111',
                    900 => '13, 28, 66',
                    950 => '1, 7, 54',
                ]),
                // Use the supplied navy shades for actions, links, and focus states.
                'primary' => array_replace(Color::hex('#22396F'), [
                    600 => '34, 57, 111',
                    700 => '13, 28, 66',
                    800 => '10, 22, 61',
                    900 => '1, 7, 54',
                    950 => '1, 5, 35',
                ]),
                'info' => Color::hex('#22396F'),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
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
