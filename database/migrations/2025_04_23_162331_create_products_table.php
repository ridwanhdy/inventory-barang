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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nama_product');
            $table->foreignId('kategori_id')  // Menyimpan foreign key untuk kategori
              ->constrained('kategoris')  // Menunjukkan bahwa kategori_id merujuk ke tabel kategoris
              ->onDelete('cascade');
            $table->foreignId('satuan_id')  // Menyimpan foreign key untuk satuan
              ->constrained('satuans')   // Menunjukkan bahwa satuan_id merujuk ke tabel satuans
              ->onDelete('cascade');
            $table->string('ukuran');
            $table->string('warna');
            $table->string('bahan');
            $table->integer('stok')->default(0);
            $table->bigInteger('harga_jual');
            $table->string('foto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
