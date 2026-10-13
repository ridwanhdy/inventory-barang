<?php

namespace App\Filament\Resources\ProduksiResource;

use App\Filament\Resources\ProduksiResource;
use App\Models\Produksi;
use App\Support\ProductionWorkflow;
use App\Support\StockPresentation;
use Filament\Actions\MountableAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ProductionActions
{
    public static function make(string $actionClass): array
    {
        return [
            $actionClass::make('startProduction')
                ->label('Mulai produksi')->icon('heroicon-o-play')->color('primary')
                ->visible(fn (Produksi $record): bool => ProduksiResource::canViewAny() && $record->fresh()?->isDraft())
                ->authorize(fn (): bool => ProduksiResource::canViewAny())
                ->modalHeading('Mulai produksi dan ambil bahan')
                ->modalDescription('Stok bahan akan berkurang sesuai kebutuhan yang disimpan. Pastikan jumlah bahan dan varian produk sudah benar.')
                ->modalSubmitActionLabel('Mulai dan kurangi stok bahan')
                ->form([
                    Forms\Components\Placeholder::make('ringkasan')->label('Produksi')
                        ->content(function (Produksi $record): string {
                            $record->refresh();

                            return $record->reference.' · '.ProduksiResource::productLabel($record->product).' · Target '.StockPresentation::quantity($record->jumlah_produksi, $record->product?->satuan?->nama_satuan);
                        }),
                    Forms\Components\Placeholder::make('kebutuhan')->label('Bahan yang akan diambil')
                        ->content(fn (Produksi $record): string => $record->produksiDetail()->with('bahanBaku.satuan')->get()->map(
                            fn ($detail): string => ($detail->bahanBaku?->nama_bahan ?? 'Bahan tidak tersedia').' — '.StockPresentation::quantity($detail->jumlah_digunakan, $detail->bahanBaku?->satuan?->nama_satuan),
                        )->join('; ')),
                    Forms\Components\DatePicker::make('produksi_mulai')->label('Tanggal mulai sebenarnya')
                        ->default(today('Asia/Jakarta')->toDateString())->maxDate(today('Asia/Jakarta')->toDateString())->required(),
                ])
                ->action(fn (Produksi $record, array $data, MountableAction $action) => static::run(
                    $action, $record, fn () => ProductionWorkflow::start($record, $data['produksi_mulai']),
                    'Produksi dimulai; bahan baku telah diambil.', 'produksi_mulai',
                )),
            $actionClass::make('completeProduction')
                ->label('Catat hasil & selesaikan')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (Produksi $record): bool => ProduksiResource::canViewAny() && $record->fresh()?->isProcessing())
                ->authorize(fn (): bool => ProduksiResource::canViewAny())
                ->modalHeading('Catat hasil produksi')
                ->modalDescription('Masukkan hasil pemeriksaan dan sisa bahan yang benar-benar dikembalikan. Hanya hasil layak jual yang menambah stok produk. Produksi yang ditutup menjadi riwayat tetap.')
                ->modalSubmitActionLabel('Simpan hasil dan selesaikan')
                ->form(function (Produksi $record): array {
                    $record->refresh();

                    return [
                        Forms\Components\Placeholder::make('target')->label('Target produksi')
                            ->content(StockPresentation::quantity($record->jumlah_produksi, $record->product?->satuan?->nama_satuan)),
                        Forms\Components\TextInput::make('jumlah_hasil')->label('Hasil layak jual')
                            ->integer()->minValue(0)->maxValue(999999)->required()->live(debounce: 400)
                            ->suffix($record->product?->satuan?->nama_satuan)
                            ->helperText('Jumlah barang yang lolos pemeriksaan dan siap dijual.'),
                        Forms\Components\TextInput::make('jumlah_rusak')->label('Hasil rusak / tidak layak jual')
                            ->integer()->minValue(0)->maxValue(999999)->default(0)->required()->live(debounce: 400)
                            ->suffix($record->product?->satuan?->nama_satuan),
                        Forms\Components\Placeholder::make('ringkasan_hasil')->label('Total hasil dan selisih target')
                            ->content(function (Forms\Get $get) use ($record): string {
                                $total = (int) $get('jumlah_hasil') + (int) $get('jumlah_rusak');
                                $difference = $total - $record->jumlah_produksi;
                                $unit = $record->product?->satuan?->nama_satuan;

                                return 'Total hasil: '.StockPresentation::quantity($total, $unit).'. '.($difference === 0
                                    ? 'Jumlah sesuai target.'
                                    : 'Selisih '.StockPresentation::quantity(abs($difference), $unit).' '.($difference > 0 ? 'lebih banyak' : 'lebih sedikit').' dari target. Jelaskan pada catatan hasil.');
                            }),
                        Forms\Components\DatePicker::make('produksi_selesai')->label('Tanggal selesai sebenarnya')
                            ->default(today('Asia/Jakarta')->toDateString())->minDate($record->produksi_mulai->toDateString())->maxDate(today('Asia/Jakarta')->toDateString())->required(),
                        Forms\Components\Textarea::make('catatan')->label('Catatan hasil produksi')
                            ->maxLength(5000)->rows(3)
                            ->helperText('Wajib diisi jika jumlah hasil layak jual + rusak berbeda dari target. Jelaskan selisih atau kendala produksi.'),
                        ...static::returnFields($record),
                    ];
                })
                ->action(fn (Produksi $record, array $data, MountableAction $action) => static::run(
                    $action, $record, fn () => ProductionWorkflow::complete($record, (int) $data['jumlah_hasil'], (int) $data['jumlah_rusak'], $data['produksi_selesai'], $data['catatan'] ?? null, $data['returns'] ?? []),
                    'Produksi selesai. Hasil dan pengembalian bahan tercatat.', 'catatan',
                )),
            $actionClass::make('cancelProduction')
                ->label('Batalkan produksi')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Produksi $record): bool => ProduksiResource::canViewAny() && ! $record->fresh()?->isClosed())
                ->authorize(fn (): bool => ProduksiResource::canViewAny())
                ->modalHeading('Batalkan produksi')
                ->modalDescription(fn (Produksi $record): string => $record->refresh()->isDraft()
                    ? 'Rencana akan dibatalkan dan tetap tersimpan sebagai riwayat. Stok bahan belum berkurang.'
                    : 'Isi sisa bahan yang benar-benar dikembalikan. Bahan yang sudah terpakai tetap dicatat sebagai pemakaian. Pembatalan tidak menambah stok produk.')
                ->modalSubmitActionLabel('Simpan pembatalan')
                ->form(function (Produksi $record): array {
                    $record->refresh();

                    return [
                        Forms\Components\Textarea::make('alasan_batal')->label('Alasan pembatalan')->required()->maxLength(5000)->rows(3),
                        ...($record->isProcessing() ? static::returnFields($record) : []),
                    ];
                })
                ->action(fn (Produksi $record, array $data, MountableAction $action) => static::run(
                    $action, $record, fn () => ProductionWorkflow::cancel($record, $data['alasan_batal'], $data['returns'] ?? []),
                    'Pembatalan dan pengembalian bahan tercatat.', 'alasan_batal',
                )),
        ];
    }

    private static function returnFields(Produksi $record): array
    {
        return [Forms\Components\Section::make('Sisa bahan dikembalikan')
            ->description('Isi 0 bila tidak ada pengembalian. Jumlah ini akan menambah kembali stok bahan baku.')
            ->schema($record->produksiDetail()->with('bahanBaku.satuan')->orderBy('id')->get()->map(
                fn ($detail) => Forms\Components\TextInput::make('returns.'.$detail->id)
                    ->label($detail->bahanBaku?->nama_bahan ?? 'Bahan baku')
                    ->suffix($detail->bahanBaku?->satuan?->nama_satuan)
                    ->helperText('Bahan diambil: '.StockPresentation::quantity($detail->jumlah_digunakan, $detail->bahanBaku?->satuan?->nama_satuan))
                    ->numeric()->minValue(0)->maxValue((float) $detail->jumlah_digunakan)->step(0.01)
                    ->rules(['decimal:0,2'])->default(0)->required(),
            )->all())->columns(2)];
    }

    private static function run(MountableAction $action, Produksi $record, callable $operation, string $message, string $fallbackField): void
    {
        try {
            $operation();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Produksi belum dapat diproses')
                ->body(collect($exception->errors())->flatten()->join(' '))->send();
            $livewire = $action->getLivewire();
            $form = $action instanceof \Filament\Tables\Actions\Action
                ? $livewire->getMountedTableActionForm()
                : $livewire->getMountedActionForm();
            $path = $form->getStatePath();
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $field = in_array($field, ['status', 'produksiDetail', 'product_id', 'jumlah_produksi', 'returns'], true) ? $fallbackField : $field;
                $errors[$path.'.'.$field] = array_merge($errors[$path.'.'.$field] ?? [], $messages);
            }

            throw ValidationException::withMessages($errors);
        }

        $record->refresh();
        $action->getLivewire()->dispatch('production-updated', productionId: $record->id);
        Notification::make()->success()->title($message)->send();
    }
}
