<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [];
    }
} 