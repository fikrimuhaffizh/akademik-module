<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data wilayah hanya 3 level (provinsi/kabupaten/kecamatan) — lihat data/wilayah.php.
     * Kolom kelurahan_kode tidak pernah terisi lewat cascade (endpoint /villages tidak ada),
     * jadi dihapus agar konsisten dengan sumber data.
     */
    public function up(): void
    {
        if (Schema::hasColumn('akd_biodata', 'kelurahan_kode')) {
            Schema::table('akd_biodata', function (Blueprint $table) {{
                $table->dropColumn('kelurahan_kode');
            }});
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('akd_biodata', 'kelurahan_kode')) {
            Schema::table('akd_biodata', function (Blueprint $table) {{
                $table->string('kelurahan_kode', 14)->nullable();
            }});
        }
    }
};
