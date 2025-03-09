<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nama_barang_id')->constrained('barangs')->cascadeOnDelete(); // Barang jadi
            $table->integer('jumlah_produksi');
            $table->foreignId('bahan_baku_1_id')->constrained('barangs')->cascadeOnDelete(); // Bahan Baku 1
            $table->foreignId('bahan_baku_2_id')->constrained('barangs')->cascadeOnDelete(); // Bahan Baku 2
            $table->enum('status', ['On Process', 'Selesai'])->default('On Process');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksis');
    }
};
