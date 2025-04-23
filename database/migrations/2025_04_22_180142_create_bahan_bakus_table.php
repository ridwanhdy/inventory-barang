<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bahan_bakus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan');
            $table->foreignId('satuan_id')  // Menyimpan foreign key untuk satuan
              ->constrained('satuans')   // Menunjukkan bahwa satuan_id merujuk ke tabel satuans
              ->onDelete('cascade');    // Hapus bahan baku jika satuan dihapus
        $table->foreignId('kategori_id')  // Menyimpan foreign key untuk kategori
              ->constrained('kategoris')  // Menunjukkan bahwa kategori_id merujuk ke tabel kategoris
              ->onDelete('cascade');
        $table->foreignId('jenis_id')  // Menyimpan foreign key untuk jenis
              ->constrained('jenis')  // Menunjukkan bahwa jenis_id merujuk ke tabel jenis
              ->onDelete('cascade');
            $table->integer('stok');   
            $table->integer('stok_minimal'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_bakus');
    }
};
