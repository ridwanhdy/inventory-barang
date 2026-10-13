<?php

namespace App\Filament\Resources\ProduksiDetailRelationManagerResource\RelationManagers;

use App\Filament\Resources\ProduksiResource;
use App\Models\ProduksiDetail;
use App\Support\ProductionWorkflow;
use App\Support\StockPresentation;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class ProduksiDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'produksiDetail';

    protected static ?string $title = 'Rincian bahan produksi';

    protected static bool $isLazy = false;

    #[On('production-updated')]
    public function refreshProduction(int $productionId): void
    {
        if ($this->getOwnerRecord()->id === $productionId) {
            $this->getOwnerRecord()->refresh();
            $this->resetTable();
        }
    }

    public function isReadOnly(): bool
    {
        return ! ProduksiResource::canViewAny() || ! $this->getOwnerRecord()->fresh()?->isProcessing();
    }

    public function form(Form $form): Form
    {
        $fields = ProduksiResource::materialFields();
        $fields[1]->label('Jumlah tambahan yang diambil')
            ->helperText('Isi hanya jumlah tambahan. Jumlah ini langsung mengurangi stok bahan baku.');

        return $form->schema($fields)->columns(2);
    }

    public function table(Table $table): Table
    {
        $planned = ! $this->getOwnerRecord()->hasStarted();

        return $table->modelLabel('bahan produksi')->pluralModelLabel('bahan produksi')
            ->description($planned
                ? 'Ini kebutuhan bahan yang direncanakan. Stok belum dikurangi. Rencana yang aktif dapat diubah melalui tombol Ubah rencana.'
                : 'Bahan diambil dikurangi dari stok. Pemakaian akhir = diambil − dikembalikan. Pengembalian dicatat ketika produksi diselesaikan atau dibatalkan.')
            ->modifyQueryUsing(fn ($query) => $query->with('bahanBaku.satuan'))
            ->columns([
                Tables\Columns\TextColumn::make('bahanBaku.nama_bahan')->label('Bahan baku'),
                Tables\Columns\TextColumn::make('jumlah_digunakan')->label($planned ? 'Kebutuhan rencana' : 'Bahan diambil')
                    ->formatStateUsing(fn ($state, ProduksiDetail $record): string => StockPresentation::quantity($state, $record->bahanBaku?->satuan?->nama_satuan)),
                Tables\Columns\TextColumn::make('jumlah_dikembalikan')->label('Dikembalikan')->hidden($planned)
                    ->formatStateUsing(fn ($state, ProduksiDetail $record): string => StockPresentation::quantity($state, $record->bahanBaku?->satuan?->nama_satuan)),
                Tables\Columns\TextColumn::make('pemakaian')->label('Pemakaian akhir')
                    ->getStateUsing(fn (ProduksiDetail $record): string => $this->getOwnerRecord()->isClosed()
                        ? StockPresentation::quantity((float) $record->jumlah_digunakan - (float) $record->jumlah_dikembalikan, $record->bahanBaku?->satuan?->nama_satuan)
                        : 'Belum ditutup')->hidden($planned),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ambil bahan tambahan')
                    ->visible(fn (): bool => ProduksiResource::canViewAny() && $this->getOwnerRecord()->fresh()?->isProcessing())
                    ->modalHeading('Ambil bahan tambahan untuk produksi')
                    ->modalDescription('Untuk bahan yang sudah ada, jumlah tambahan akan digabung ke jumlah yang sudah diambil.')
                    ->modalSubmitActionLabel('Ambil dan kurangi stok')
                    ->using(function (array $data, $action): ProduksiDetail {
                        try {
                            return ProductionWorkflow::issue($this->getOwnerRecord(), (int) $data['bahan_baku_id'], (float) $data['jumlah_digunakan']);
                        } catch (ValidationException $exception) {
                            $errors = [];
                            foreach ($exception->errors() as $field => $messages) {
                                $errors[$this->getMountedTableActionForm()->getStatePath().'.'.(in_array($field, ['produksiDetail', 'status'], true) ? 'jumlah_digunakan' : $field)] = $messages;
                            }
                            throw ValidationException::withMessages($errors);
                        }
                    }),
            ])->actions([])->bulkActions([])
            ->emptyStateHeading('Belum ada rincian bahan')
            ->emptyStateDescription('Lengkapi kebutuhan bahan melalui Ubah rencana sebelum mulai produksi.');
    }
}
