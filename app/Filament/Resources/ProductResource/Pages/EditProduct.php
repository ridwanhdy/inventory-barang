<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'Perbarui identitas, varian, harga jual, dan foto produk agar katalog tetap sesuai dengan produk yang dijual.';
    }

    public function boot(): void
    {
        app()->setLocale('id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus produk')
                ->modalDescription('Produk yang belum digunakan akan dihapus. Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Hapus produk')
                ->modalCancelActionLabel('Batal'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan perubahan')
                ->submit('save')
                ->keyBindings(['mod+s']),
            Actions\Action::make('cancel')
                ->label('Batal')
                ->url(fn () => $this->getResource()::getUrl())
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['foto'] = $data['foto'] ?? '';

        return $data;
    }
}
