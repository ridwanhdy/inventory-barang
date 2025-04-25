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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
             $table->foreignId('order_id')  // Menyimpan foreign key untuk jenis
              ->constrained('orders')  // Menunjukkan bahwa jenis_id merujuk ke tabel jenis
              ->onDelete('cascade');
            $table->integer('jumlah_bayar');
            $table->integer('sisa_bayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
