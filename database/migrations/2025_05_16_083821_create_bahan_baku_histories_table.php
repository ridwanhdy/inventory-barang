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
        Schema::create('bahan_baku_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_baku_id')->constrained()->onDelete('cascade');
            $table->integer('jumlah_perubahan');
            $table->string('tipe_perubahan'); // 'tambah' atau 'kurang'
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_baku_histories');
    }
};
