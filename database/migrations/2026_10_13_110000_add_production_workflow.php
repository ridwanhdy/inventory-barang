<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksis', function (Blueprint $table) {
            $table->enum('status', ['Draft', 'Proses', 'Batal', 'Selesai'])->default('Draft')->change();
            $table->integer('jumlah_hasil')->nullable();
            $table->integer('jumlah_rusak')->nullable();
            $table->text('catatan')->nullable();
            $table->text('catatan_hasil')->nullable();
            $table->text('alasan_batal')->nullable();
            $table->date('tanggal_batal')->nullable();
            $table->dateTime('produksi_dimulai_at')->nullable();
            $table->boolean('hasil_dari_data_lama')->default(false);
        });
        Schema::table('produksi_details', function (Blueprint $table) {
            $table->decimal('jumlah_digunakan', 12, 2)->change();
            $table->decimal('jumlah_dikembalikan', 12, 2)->default(0);
        });

        // Annotate the old record without replaying any historical stock effects or inventing damage data.
        DB::table('produksis')->where('status', 'Selesai')->update([
            'jumlah_hasil' => DB::raw('jumlah_produksi'), 'hasil_dari_data_lama' => true,
        ]);
        DB::table('produksis')->whereIn('status', ['Proses', 'Selesai', 'Batal'])
            ->update(['produksi_dimulai_at' => DB::raw('produksi_mulai')]);
        // Historical material rows remain intact. New uniqueness is enforced under the locked parent.
    }

    public function down(): void
    {
        Schema::table('produksi_details', function (Blueprint $table) {
            $table->dropColumn('jumlah_dikembalikan');
            $table->float('jumlah_digunakan')->change();
        });
        DB::table('produksis')->where('status', 'Draft')->update(['status' => 'Batal']);
        Schema::table('produksis', function (Blueprint $table) {
            $table->enum('status', ['Proses', 'Batal', 'Selesai'])->default('Proses')->change();
            $table->dropColumn(['jumlah_hasil', 'jumlah_rusak', 'catatan', 'catatan_hasil', 'alasan_batal', 'tanggal_batal', 'produksi_dimulai_at', 'hasil_dari_data_lama']);
        });
    }
};
