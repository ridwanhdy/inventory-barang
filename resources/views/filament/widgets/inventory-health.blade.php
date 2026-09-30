<x-filament-widgets::widget class="inv-stock-widget">
    <x-filament::section class="inv-stock-card">
        <x-slot name="heading">Pantauan inventaris</x-slot>
        <x-slot name="description">
            {{ $hasMinimum ? 'Stok minimum & aktivitas produksi' : 'Pemantauan stok habis & aktivitas produksi' }}
        </x-slot>

        <div class="inv-stock-body">
            <div class="inv-stock-summary {{ $lowStockCount > 0 ? 'inv-stock-summary-alert' : 'inv-stock-summary-safe' }}">
                <div class="inv-stock-count">{{ number_format($lowStockCount, 0, ',', '.') }}</div>
                <div class="inv-stock-copy">
                    <strong>{{ $hasMinimum ? 'bahan perlu perhatian' : 'bahan habis' }}</strong>
                    <span>
                        {{ $hasMinimum ? 'Stok mencapai batas minimum.' : 'Stok tersisa nol atau kurang.' }}
                    </span>
                </div>
                <x-filament::icon
                    :icon="$lowStockCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-shield-check'"
                    class="inv-stock-summary-icon"
                />
            </div>

            @if ($materials->isNotEmpty())
                <ul class="inv-stock-list" aria-label="Bahan yang perlu perhatian">
                    @foreach ($materials as $material)
                        @php
                            $unit = $material->bahanBaku->satuan?->nama_satuan ?? 'satuan';
                            $minimum = $hasMinimum ? (float) $material->stok_minimal : 0;
                            $percentage = $minimum > 0 ? max(0, min(100, $material->stok / $minimum * 100)) : 0;
                        @endphp
                        <li class="inv-stock-item">
                            <div class="inv-stock-item-heading">
                                <span class="inv-stock-name">{{ $material->bahanBaku->nama_bahan }}</span>
                                <span class="inv-stock-status">{{ $material->stok <= 0 ? 'Habis' : 'Menipis' }}</span>
                            </div>
                            <div class="inv-stock-quantity">
                                <span>Stok {{ $this->formatQuantity($material->stok) }} {{ $unit }}</span>
                                @if ($hasMinimum)
                                    <span>Minimum {{ $this->formatQuantity($minimum) }} {{ $unit }}</span>
                                @endif
                            </div>
                            @if ($hasMinimum && $minimum > 0)
                                <div class="inv-stock-track" aria-hidden="true">
                                    <div class="inv-stock-fill" style="width: {{ $percentage }}%"></div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($lowStockCount > $materials->count())
                    <p class="inv-stock-more">+{{ $lowStockCount - $materials->count() }} bahan lainnya perlu perhatian.</p>
                @endif
            @else
                <div class="inv-stock-empty">
                    <x-filament::icon
                        :icon="$totalMaterials > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-cube'"
                        class="inv-stock-empty-icon"
                    />
                    <strong>
                        {{ $totalMaterials === 0 ? 'Belum ada stok bahan baku' : ($hasMinimum ? 'Semua stok terpantau aman' : 'Tidak ada bahan yang habis') }}
                    </strong>
                    <p>
                        {{ $totalMaterials === 0 ? 'Data stok akan tampil setelah bahan baku dicatat.' : ($hasMinimum ? 'Stok berada di atas batas minimum.' : 'Semua bahan terpantau masih memiliki stok.') }}
                    </p>
                </div>
            @endif

            <div class="inv-stock-footer">
                <div class="inv-stock-production">
                    @if ($productionCount !== null)
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="inv-stock-production-icon" />
                        <span><strong>{{ $productionCount }}</strong> produksi berjalan</span>
                    @else
                        <span>{{ $totalMaterials }} bahan terpantau</span>
                    @endif
                </div>
                @if ($stockUrl)
                    <a href="{{ $stockUrl }}" class="inv-stock-link">
                        Lihat stok
                        <x-filament::icon icon="heroicon-m-arrow-up-right" class="inv-stock-link-icon" />
                    </a>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
