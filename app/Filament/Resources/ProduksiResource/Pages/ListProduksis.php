<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use App\Models\Produksi;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProduksis extends ListRecords
{
    protected static string $resource = ProduksiResource::class;

    protected ?string $heading = 'Produksi';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Pantau rencana, bahan yang diambil, dan hasil nyata setiap batch produksi.';
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Semua')];
        foreach (Produksi::statusLabels() as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(Produksi::query()->where('status', $status)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Buat rencana produksi')];
    }
}
