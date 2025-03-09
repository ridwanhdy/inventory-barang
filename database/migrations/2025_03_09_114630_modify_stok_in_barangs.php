<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->decimal('stok', 10, 2)->change(); // 10 digit, 2 angka desimal
            $table->decimal('stok_minimum', 10, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->integer('stok')->change();
            $table->integer('stok_minimum')->change();
        });
    }
};
