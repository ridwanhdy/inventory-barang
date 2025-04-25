<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('users_id')->constrained('users')->onDelete('cascade');
            $table->enum('status_transaksi', ['proses', 'batal', 'selesai'])->default('proses');
            $table->enum('status_pembayaran', ['belum_bayar', 'cicilan', 'lunas'])->default('belum_bayar');
            $table->enum('metode_pembayaran', ['cash', 'bank'])->default('cash');
            $table->date('tanggal_order');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}; 