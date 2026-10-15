<?php

namespace App\Filament\Resources\Concerns;

use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

trait InteractsWithReportPeriod
{
    public function bootInteractsWithReportPeriod(): void
    {
        app()->setLocale('id');
    }

    public function applyTableFilters(): void
    {
        ReportPeriod::validator(
            $this->tableDeferredFilters['periode'] ?? [],
            'tableDeferredFilters.periode.',
        )->validate();
        $this->getTableFiltersForm()->getState();

        parent::applyTableFilters();
    }

    protected function reportPdfAction(string $view, string $filename, string $collectionKey): Action
    {
        return Action::make('cetak_pdf')
            ->label('Cetak PDF')
            ->icon('heroicon-o-printer')
            ->tooltip('Cetak seluruh hasil sesuai periode, pencarian, dan filter yang dipilih')
            ->action(function () use ($view, $filename, $collectionKey) {
                // Also apply pending edits so printing always respects the visible controls.
                $this->applyTableFilters();
                $records = $this->getFilteredSortedTableQuery()->get();

                if ($records->isEmpty()) {
                    Notification::make()
                        ->title('Tidak ada data untuk dicetak')
                        ->body('Ubah periode, pencarian, atau filter laporan, lalu coba lagi.')
                        ->warning()
                        ->send();

                    return null;
                }

                $period = $this->getTableFilterState('periode') ?? [];
                [$start, $end] = ReportPeriod::bounds($period);
                $filterLabels = [];

                foreach ($this->getTable()->getFilters() as $name => $filter) {
                    if ($name !== 'periode') {
                        foreach ($filter->getIndicators() as $indicator) {
                            $filterLabels[] = strip_tags((string) $indicator->getLabel());
                        }
                    }
                }

                if (filled($this->getTableSearch())) {
                    $filterLabels[] = 'Pencarian: '.$this->getTableSearch();
                }

                $data = [
                    $collectionKey => $records,
                    'periodeLabel' => ReportPeriod::label($period),
                    'filterLabel' => $filterLabels === [] ? 'Semua data sesuai periode' : implode(' · ', $filterLabels),
                ];

                if ($collectionKey === 'orders') {
                    $data += [
                        'totalPenjualan' => $records->sum('total_harga'),
                        'tanggalAwal' => $start?->toDateString(),
                        'tanggalAkhir' => $end?->toDateString(),
                    ];
                }

                $pdf = Pdf::loadView($view, $data)->setPaper('a4');

                return response()->streamDownload(function () use ($pdf): void {
                    echo $pdf->output();
                }, $filename);
            });
    }
}
