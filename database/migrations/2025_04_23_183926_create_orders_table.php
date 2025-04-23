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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
        $table->foreignId('users_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->enum('status_transaksi', ['proses', 'batal', 'selesai']);
        $table->enum('status_pembayaran', ['belum_bayar', 'cicilan', 'lunas']);
        $table->date('tanggal_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
