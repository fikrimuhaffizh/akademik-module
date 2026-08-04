<?php

use App\Database\Migrations\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends BaseMigration
{
    public function up(): void
    {
        Schema::create('akd_tahun_ajaran', function (Blueprint $table) {
            $table->id('tahun_ajaran_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('nama', 100);
            $table->integer('tahun_mulai');
            $table->integer('tahun_selesai');
            $table->boolean('is_aktif')->default(true);

            $this->addStandardColumns($table);

            $table->index('tenant_id');
        });

        Schema::create('akd_periode_akademik', function (Blueprint $table) {
            $table->id('periode_akademik_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('nama', 100);
            $table->year('tahun_mulai')->nullable();
            $table->year('tahun_selesai')->nullable();
            $table->enum('semester', ['ganjil','genap','pendek']);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->boolean('is_aktif')->default(true);

            $this->addStandardColumns($table);

            $table->index('tenant_id');
        });

        Schema::create('akd_kalender_akademik', function (Blueprint $table) {
            $table->id('kalender_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('periode_akademik_id');
            $table->string('nama_kegiatan', 255);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->enum('jenis', ['akademik','ujian','libur','lainnya','krs','edom'])->default('akademik');
            $table->text('keterangan')->nullable();
            $table->json('metadata')->nullable()->comment('Filter konteks KRS: {"prodi_id":[6],"angkatan":[2021]}');

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('periode_akademik_id');
        });

        Schema::create('akd_ruang_kuliah', function (Blueprint $table) {
            $table->id('ruang_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('kode', 50);
            $table->string('nama', 100);
            $table->string('gedung', 100)->nullable();
            $table->tinyInteger('lantai')->nullable();
            $table->integer('kapasitas');
            $table->enum('jenis', ['kelas','lab','aula','online'])->default('kelas');
            $table->boolean('is_aktif')->default(true);

            $this->addStandardColumns($table);

            $table->unique(['tenant_id', 'kode']);
            $table->index('tenant_id');
        });

        Schema::create('akd_batas_sks', function (Blueprint $table) {
            $table->id('batas_sks_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('periode_akademik_id');
            $table->decimal('ipk_min', 3, 2);
            $table->decimal('ipk_max', 3, 2);
            $table->unsignedTinyInteger('max_sks');

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index(['periode_akademik_id','ipk_min','ipk_max'], 'akd_batas_sks_lookup_idx');
        });

        Schema::create('akd_penawaran_mk', function (Blueprint $table) {
                    $table->id('penawaran_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('periode_akademik_id');
            $table->unsignedBigInteger('kurikulum_mata_kuliah_id'); // FK -> kur_kurikulum_mata_kuliah
            $table->unsignedBigInteger('prodi_id'); // orgunit_id
            $table->string('kurikulum_kode', 100)->nullable()->index();
            $table->boolean('is_wajib')->default(true);
            $table->string('grup_pilihan', 100)->nullable();
            $table->string('sistem_kuliah')->default('reguler');
            $table->boolean('is_aktif')->default(true);

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('periode_akademik_id');
            $table->index('kurikulum_mata_kuliah_id');
            $table->index('prodi_id');
        });

        Schema::create('akd_kelas_kuliah', function (Blueprint $table) {
            $table->id('kelas_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('penawaran_id');
            $table->unsignedBigInteger('ref_kelas_id')->nullable()->index();
            $table->string('nama_kelas', 10);
            $table->integer('kapasitas');
            $table->enum('sistem_kuliah', ['reguler','online','hybrid'])->default('reguler');
            $table->boolean('is_aktif')->default(true);

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('penawaran_id');
        });

        Schema::create('akd_jadwal_kuliah', function (Blueprint $table) {
            $table->id('jadwal_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('ruang_id')->nullable();
            $table->enum('hari', ['senin','selasa','rabu','kamis','jumat','sabtu','minggu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->enum('jenis_pertemuan', ['teori','praktikum'])->default('teori');
            $table->string('link_online', 500)->nullable();

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('kelas_id');
            $table->index('ruang_id');
        });

        Schema::create('akd_pembebanan_dosen', function (Blueprint $table) {
            $table->id('pembebanan_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('pegawai_id');
            $table->enum('peran', ['pengampu','asisten','koordinator'])->default('pengampu');

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('kelas_id');
            $table->index('pegawai_id');
        });

        Schema::create('akd_pembimbing_mahasiswa', function (Blueprint $table) {
            $table->id('pma_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('periode_akademik_id');
            $table->unsignedBigInteger('pegawai_id');
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('jenis_pembimbing')->nullable()->comment('Referensi jenis pembimbing');
            $table->index('jenis_pembimbing');

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('periode_akademik_id');
            $table->index('pegawai_id');
        });

        Schema::create('akd_setting_prodi', function (Blueprint $table) {
            $table->id('setting_prodi_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('periode_akademik_id');
            $table->unsignedBigInteger('prodi_id');
            $table->unsignedBigInteger('kurikulum_id')->nullable();
            $table->string('kurikulum_kode', 100)->nullable();
            $table->boolean('is_aktif')->default(true);

            $table->boolean('buka_krs')->default(false);
            $table->date('tgl_krs_mulai')->nullable();
            $table->date('tgl_krs_selesai')->nullable();
            $table->boolean('buka_khs')->default(false);
            $table->boolean('buka_pengisian_nilai')->default(false);
            $table->tinyInteger('min_presensi_uts')->nullable();
            $table->tinyInteger('min_presensi_uas')->nullable();
            $table->tinyInteger('jumlah_pertemuan')->nullable();
            $table->json('angkatan')->nullable();

            $this->addStandardColumns($table);

            $table->unique(['tenant_id', 'periode_akademik_id', 'prodi_id'], 'akd_setting_prodi_uniq');
            $table->index('tenant_id');
            $table->index('prodi_id');
        });

        Schema::create('akd_konversi_nilai', function (Blueprint $table) {
            $table->id('konversi_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('periode_akademik_id');
            $table->enum('jenis_rekognisi', ['transfer','rpl','pindahan','pertukaran'])->default('transfer');
            $table->string('mata_kuliah_asal', 255);
            $table->tinyInteger('sks_asal');
            $table->string('nilai_asal', 10);
            $table->unsignedBigInteger('mata_kuliah_id');
            $table->tinyInteger('sks_diakui')->nullable();
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_konversi', 10);
            $table->decimal('bobot', 3, 2)->nullable();
            $table->enum('status', ['draft','diajukan','disetujui','ditolak'])->default('draft');
            $table->string('dokumen_path', 500)->nullable();
            $table->string('divalidasi_oleh', 255)->nullable();
            $table->dateTime('divalidasi_at')->nullable();
            $table->text('keterangan')->nullable();

            $this->addStandardColumns($table);

            $table->index('tenant_id');
            $table->index('periode_akademik_id');
        });

        Schema::create('akd_publish_batch', function (Blueprint $table) {
            $table->id('publish_batch_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('source_module', 50)->default('kurikulum');
            $table->string('reference_code', 191);
            $table->unsignedInteger('total_data')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->json('metadata')->nullable();

            $this->addStandardColumns($table);

            $table->unique('reference_code');
            $table->index('tenant_id');
        });

        Schema::create('akd_edom_kelas', function (Blueprint $table) {
            $table->id('edom_kelas_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('periode_akademik_id');
            $table->unsignedBigInteger('survei_id')->nullable()->comment('FK ke survei_survei.survei_id');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->enum('status', ['draft','aktif','selesai'])->default('draft');

            $this->addStandardColumns($table);

            $table->unique(['tenant_id','kelas_id','periode_akademik_id'], 'edom_kelas_unique');
            $table->index('tenant_id');
            $table->index('kelas_id');
            $table->index('periode_akademik_id');
            $table->index('survei_id');
        });

        Schema::create('akd_edom_status', function (Blueprint $table) {
            $table->id('edom_status_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('periode_akademik_id');
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('survei_pengisian_id')->nullable()->comment('FK logical ke survei_pengisian.pengisian_id');
            $table->enum('status', ['belum_mulai','sedang_diisi','selesai'])->default('belum_mulai');
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();

            $this->addStandardColumns($table);

            $table->unique(['tenant_id','periode_akademik_id','mahasiswa_id','kelas_id'], 'edom_status_unique');
            $table->index('tenant_id');
            $table->index(['periode_akademik_id','status']);
            $table->index(['kelas_id','status']);
        });

        // ── FK pass ──
        Schema::table('akd_periode_akademik', function (Blueprint $table) {
            // no FK (tahun_ajaran dihapus dari relasi)
        });
        Schema::table('akd_kalender_akademik', function (Blueprint $table) {
            $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
        });
        Schema::table('akd_batas_sks', function (Blueprint $table) {
            $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
        });
        Schema::table('akd_penawaran_mk', function (Blueprint $table) {
                    $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
                    $table->foreign('kurikulum_mata_kuliah_id')->references('kur_mk_id')->on('kur_kurikulum_mata_kuliah')->cascadeOnDelete();
                });
                Schema::table('akd_kelas_kuliah', function (Blueprint $table) {
                    $table->foreign('penawaran_id')->references('penawaran_id')->on('akd_penawaran_mk')->cascadeOnDelete();
                });
        Schema::table('akd_jadwal_kuliah', function (Blueprint $table) {
            $table->foreign('kelas_id')->references('kelas_id')->on('akd_kelas_kuliah')->cascadeOnDelete();
            $table->foreign('ruang_id')->references('ruang_id')->on('akd_ruang_kuliah')->nullOnDelete();
        });
        Schema::table('akd_pembebanan_dosen', function (Blueprint $table) {
            $table->foreign('kelas_id')->references('kelas_id')->on('akd_kelas_kuliah')->cascadeOnDelete();
        });
        Schema::table('akd_pembimbing_mahasiswa', function (Blueprint $table) {
            $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
        });
        Schema::table('akd_konversi_nilai', function (Blueprint $table) {
            $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
        });
        Schema::table('akd_edom_kelas', function (Blueprint $table) {
            $table->foreign('kelas_id')->references('kelas_id')->on('akd_kelas_kuliah')->cascadeOnDelete();
            $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
        });
        Schema::table('akd_edom_status', function (Blueprint $table) {
            $table->foreign('kelas_id')->references('kelas_id')->on('akd_kelas_kuliah')->cascadeOnDelete();
            $table->foreign('periode_akademik_id')->references('periode_akademik_id')->on('akd_periode_akademik')->cascadeOnDelete();
        });

        // ── Views (reporting) ──
        DB::statement('DROP VIEW IF EXISTS view_ap_rekap_beban_dosen');
        DB::statement('
            CREATE OR REPLACE VIEW view_ap_rekap_beban_dosen AS
            SELECT b.tenant_id, b.pegawai_id, p.periode_akademik_id, SUM(mk.sks) as total_sks
            FROM akd_pembebanan_dosen b
            JOIN akd_kelas_kuliah k ON b.kelas_id = k.kelas_id
            JOIN akd_penawaran_mk p ON k.penawaran_id = p.penawaran_id
            JOIN kur_kurikulum_mata_kuliah ckm ON p.kurikulum_mata_kuliah_id = ckm.kur_mk_id
            JOIN kur_mata_kuliah mk ON ckm.mata_kuliah_id = mk.mata_kuliah_id
            WHERE b.deleted_at IS NULL AND k.deleted_at IS NULL AND p.deleted_at IS NULL
              AND ckm.deleted_at IS NULL AND mk.deleted_at IS NULL
            GROUP BY b.tenant_id, b.pegawai_id, p.periode_akademik_id;
        ');

    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_ap_rekap_beban_dosen');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
                    'akd_edom_status','akd_edom_kelas','akd_publish_batch','akd_konversi_nilai',
                    'akd_pembimbing_mahasiswa','akd_pembebanan_dosen','akd_jadwal_kuliah','akd_kelas_kuliah',
                    'akd_penawaran_mk','akd_batas_sks','akd_setting_prodi',
                    'akd_ruang_kuliah','akd_kalender_akademik','akd_periode_akademik','akd_tahun_ajaran',
                ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};