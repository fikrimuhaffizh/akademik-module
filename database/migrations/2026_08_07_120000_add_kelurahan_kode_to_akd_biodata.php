<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data wilayah kini 4 level (provinsi/kabupaten/kecamatan/kelurahan) —
     * lihat data/villages.php (83k desa/kelurahan, Kemendagri via cahyadsn/wilayah).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('akd_biodata', 'kelurahan_kode')) {
            Schema::table('akd_biodata', function (Blueprint $table) {{
                $table->string('kelurahan_kode', 14)->nullable();
            }});
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('akd_biodata', 'kelurahan_kode')) {
            Schema::table('akd_biodata', function (Blueprint $table) {{
                $table->dropColumn('kelurahan_kode');
            }});
        }
    }
};
