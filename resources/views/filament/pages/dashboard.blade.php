<x-filament-panels::page class="fi-dashboard-page inv-dashboard-page">
    @include('filament.partials.dashboard-styles')

    <section class="inv-hero" aria-labelledby="inventory-overview-heading">
        <div class="inv-hero-copy">
            <span class="inv-eyebrow">INVENTORY OVERVIEW</span>
            <h2 id="inventory-overview-heading">Ringkasan bisnis Anda</h2>
            <p>Pantau penjualan, ketersediaan produk, dan kebutuhan bahan baku.</p>
        </div>

        <div class="inv-hero-side">
            <span class="inv-date">
                <x-filament::icon icon="heroicon-o-calendar-days" class="inv-icon" />
                {{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}
            </span>

            <div class="inv-hero-actions">
                @if (\App\Filament\Resources\OrderResource::canCreate())
                    <a href="{{ \App\Filament\Resources\OrderResource::getUrl('create') }}" class="inv-hero-action inv-hero-action-primary">
                        <x-filament::icon icon="heroicon-o-plus" class="inv-icon" />
                        Buat order
                    </a>
                @elseif (\App\Filament\Resources\OrderResource::canViewAny())
                    <a href="{{ \App\Filament\Resources\OrderResource::getUrl() }}" class="inv-hero-action inv-hero-action-primary">
                        <x-filament::icon icon="heroicon-o-shopping-bag" class="inv-icon" />
                        Lihat order
                    </a>
                @endif

                @if (\App\Filament\Resources\ProductResource::canViewAny())
                    <a href="{{ \App\Filament\Resources\ProductResource::getUrl() }}" class="inv-hero-action">
                        <x-filament::icon icon="heroicon-o-cube" class="inv-icon" />
                        Lihat produk
                    </a>
                @endif
            </div>
        </div>
    </section>

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleWidgets()"
        class="inv-dashboard-grid"
    />
</x-filament-panels::page>
