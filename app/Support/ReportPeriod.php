<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class ReportPeriod
{
    public static function filter(string $column): Filter
    {
        return Filter::make('periode')
            ->form([
                Section::make('Periode laporan')
                    ->description($column === 'tanggal_order'
                        ? 'Pilih rentang tanggal, bulan, atau tahun penjualan, lalu terapkan filter atau cetak PDF.'
                        : 'Periode berdasarkan pembaruan stok (WIB). Angka yang ditampilkan adalah stok saat ini.')
                    ->compact()
                    ->columns(['sm' => 2, 'lg' => 4])
                    ->schema([
                        Select::make('jenis')
                            ->label('Jenis periode')
                            ->options([
                                'all' => 'Semua periode',
                                'tanggal' => 'Rentang tanggal',
                                'bulan' => 'Rentang bulan',
                                'tahun' => 'Rentang tahun',
                            ])
                            ->default('all')
                            ->selectablePlaceholder(false)
                            ->live()
                            ->columnSpanFull(),
                        DatePicker::make('tanggal_awal')
                            ->label('Tanggal awal')
                            ->displayFormat('d/m/Y')
                            ->default(fn () => now('Asia/Jakarta')->startOfMonth()->toDateString())
                            ->visible(fn (Get $get) => $get('jenis') === 'tanggal')
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal akhir')
                            ->displayFormat('d/m/Y')
                            ->default(fn () => now('Asia/Jakarta')->toDateString())
                            ->visible(fn (Get $get) => $get('jenis') === 'tanggal')
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                        Select::make('bulan_awal')
                            ->label('Bulan awal')
                            ->options(self::months())
                            ->default(fn () => now('Asia/Jakarta')->month)
                            ->visible(fn (Get $get) => $get('jenis') === 'bulan'),
                        TextInput::make('tahun_awal')
                            ->label('Tahun awal')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(9999)
                            ->placeholder('Contoh: 2026')
                            ->default(fn () => now('Asia/Jakarta')->year)
                            ->visible(fn (Get $get) => in_array($get('jenis'), ['bulan', 'tahun'], true)),
                        Select::make('bulan_akhir')
                            ->label('Bulan akhir')
                            ->options(self::months())
                            ->default(fn () => now('Asia/Jakarta')->month)
                            ->visible(fn (Get $get) => $get('jenis') === 'bulan'),
                        TextInput::make('tahun_akhir')
                            ->label('Tahun akhir')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(9999)
                            ->placeholder('Contoh: 2026')
                            ->default(fn () => now('Asia/Jakarta')->year)
                            ->visible(fn (Get $get) => in_array($get('jenis'), ['bulan', 'tahun'], true)),
                    ]),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                if (blank($data['jenis'] ?? null) || $data['jenis'] === 'all') {
                    return $query;
                }

                [$start, $end] = self::bounds($data);

                // Incomplete or tampered query-string filters must never widen the report.
                if ($start === null || $end === null) {
                    return $query->whereRaw('1 = 0');
                }

                $qualified = $query->getModel()->qualifyColumn($column);

                if ($column === 'tanggal_order') {
                    return $query->whereDate($qualified, '>=', $start->toDateString())
                        ->whereDate($qualified, '<=', $end->toDateString());
                }

                return $query->whereBetween($qualified, [
                    $start->setTimezone(config('app.timezone'))->toDateTimeString(),
                    $end->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s.u'),
                ]);
            })
            ->indicateUsing(fn (array $data): array => blank($data['jenis'] ?? null) || $data['jenis'] === 'all'
                ? []
                : ['Periode: '.self::label($data)])
            ->columnSpanFull();
    }

    public static function months(): array
    {
        return [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    }

    public static function validator(array $data, string $prefix = ''): \Illuminate\Contracts\Validation\Validator
    {
        $rules = ['jenis' => ['nullable', 'in:all,tanggal,bulan,tahun']];

        if (($data['jenis'] ?? null) === 'tanggal') {
            $rules += [
                'tanggal_awal' => ['required', 'date_format:Y-m-d'],
                'tanggal_akhir' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$prefix.'tanggal_awal'],
            ];
        }

        if (in_array($data['jenis'] ?? null, ['bulan', 'tahun'], true)) {
            $rules += [
                'tahun_awal' => ['required', 'integer', 'between:1900,9999'],
                'tahun_akhir' => ['required', 'integer', 'between:1900,9999', 'gte:'.$prefix.'tahun_awal'],
            ];
        }

        if (($data['jenis'] ?? null) === 'bulan') {
            $rules += [
                'bulan_awal' => ['required', 'integer', 'between:1,12'],
                'bulan_akhir' => ['required', 'integer', 'between:1,12'],
            ];
        }

        $attributes = [];
        foreach (array_keys($rules) as $field) {
            $attributes[$prefix.$field] = str_replace('_', ' ', $field);
        }

        $values = $prefix === '' ? $data : \Illuminate\Support\Arr::undot(
            collect($data)->mapWithKeys(fn ($value, $key) => [$prefix.$key => $value])->all(),
        );

        $validator = Validator::make($values, collect($rules)->mapWithKeys(
            fn ($rule, $field) => [$prefix.$field => $rule],
        )->all(), [
            'required' => ':attribute wajib diisi.',
            'date_format' => ':attribute harus berupa tanggal yang valid.',
            'after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'between' => ':attribute harus antara :min dan :max.',
            'gte' => 'Tahun akhir harus sama dengan atau setelah tahun awal.',
            'in' => 'Jenis periode tidak valid.',
        ], $attributes);

        $validator->after(function ($validator) use ($data, $prefix): void {
            if ($validator->errors()->isNotEmpty() || ($data['jenis'] ?? null) !== 'bulan') {
                return;
            }

            if ((int) $data['tahun_awal'] === (int) $data['tahun_akhir']
                && (int) $data['bulan_akhir'] < (int) $data['bulan_awal']) {
                $validator->errors()->add($prefix.'bulan_akhir', 'Bulan akhir harus sama dengan atau setelah bulan awal.');
            }
        });

        return $validator;
    }

    /** @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable} */
    public static function bounds(array $data): array
    {
        if (blank($data['jenis'] ?? null) || $data['jenis'] === 'all' || self::validator($data)->fails()) {
            return [null, null];
        }

        return match ($data['jenis']) {
            'tanggal' => [
                CarbonImmutable::parse($data['tanggal_awal'], 'Asia/Jakarta')->startOfDay(),
                CarbonImmutable::parse($data['tanggal_akhir'], 'Asia/Jakarta')->endOfDay(),
            ],
            'bulan' => [
                CarbonImmutable::create((int) $data['tahun_awal'], (int) $data['bulan_awal'], 1, 0, 0, 0, 'Asia/Jakarta'),
                CarbonImmutable::create((int) $data['tahun_akhir'], (int) $data['bulan_akhir'], 1, 0, 0, 0, 'Asia/Jakarta')->endOfMonth(),
            ],
            'tahun' => [
                CarbonImmutable::create((int) $data['tahun_awal'], 1, 1, 0, 0, 0, 'Asia/Jakarta'),
                CarbonImmutable::create((int) $data['tahun_akhir'], 12, 31, 0, 0, 0, 'Asia/Jakarta')->endOfDay(),
            ],
        };
    }

    public static function label(array $data): string
    {
        if (blank($data['jenis'] ?? null) || $data['jenis'] === 'all') {
            return 'Semua periode';
        }

        [$start, $end] = self::bounds($data);
        if ($start === null || $end === null) {
            return 'Lengkapi periode laporan';
        }

        return match ($data['jenis']) {
            'tanggal' => $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
            'bulan' => $start->locale('id')->translatedFormat('F Y').' - '.$end->locale('id')->translatedFormat('F Y'),
            'tahun' => $start->format('Y').' - '.$end->format('Y'),
        };
    }
}
