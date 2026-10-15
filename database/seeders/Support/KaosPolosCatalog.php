<?php

namespace Database\Seeders\Support;

final class KaosPolosCatalog
{
    public static function sizes(): array
    {
        return ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];
    }

    public static function colors(): array
    {
        return [
            'Hitam', 'Putih', 'Navy', 'Abu-abu Misty', 'Abu-abu Tua', 'Merah',
            'Maroon', 'Biru Royal', 'Biru Muda', 'Hijau Botol', 'Hijau Army',
            'Kuning', 'Oranye', 'Cokelat', 'Beige', 'Ungu',
        ];
    }

    /** @return array<string, int> Harga jual dasar per potong dalam rupiah. */
    public static function fabrics(): array
    {
        return [
            'Cotton Combed 20s' => 55000,
            'Cotton Combed 24s' => 50000,
            'Cotton Combed 30s' => 45000,
            'Cotton Combed 40s' => 50000,
            'Cotton Carded 24s' => 35000,
            'CVC' => 38000,
            'TC' => 30000,
            'Cotton Bamboo' => 75000,
        ];
    }

    public static function fabricName(string $fabric, string $color): string
    {
        return "Kain {$fabric} - {$color}";
    }

    public static function ribName(string $color): string
    {
        return "Rib Cotton 1x1 - {$color}";
    }
}
