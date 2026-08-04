<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── akd_mahasiswa ───
        Schema::create('akd_mahasiswa', function (Blueprint $table) {
            $table->id('mahasiswa_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();

            // Academic Identity
            $table->string('nim', 50)->unique();
            $table->string('nama', 255);
            $table->string('email', 255)->nullable();
            $table->string('no_hp', 50)->nullable();
            $table->unsignedBigInteger('prodi_id')->nullable()->index();
            $table->string('angkatan', 10)->nullable();
            $table->string('kurikulum_kode', 50)->nullable();
            $table->string('status', 50)->default('aktif');

            // Enrollment
            $table->string('jenis_masuk', 50)->nullable();
            $table->string('jenis_masuk_detail', 255)->nullable();
            $table->unsignedSmallInteger('semester_masuk')->default(1);
            $table->date('tanggal_awal_masuk')->nullable();
            $table->date('tanggal_daftar_ulang')->nullable();
            $table->string('sistem_kuliah', 50)->nullable();
            $table->unsignedSmallInteger('sks_diakui_awal')->default(0);

            // Transfer
            $table->string('institusi_asal', 255)->nullable();
            $table->string('prodi_asal', 255)->nullable();

            // PMB Bridge
            $table->unsignedBigInteger('pmb_pendaftar_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // Profile
            $table->string('foto', 255)->nullable();
            $table->json('metadata')->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_biodata ───
        Schema::create('akd_biodata', function (Blueprint $table) {
            $table->id('biodata_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();

            // Identity
            $table->string('nik', 50)->nullable();
            $table->string('tempat_lahir', 255)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->char('jenis_kelamin', 1)->nullable();

            // Demographic
            $table->string('agama', 50)->nullable();
            $table->string('kewarganegaraan', 50)->nullable();
            $table->string('suku', 100)->nullable();
            $table->string('no_kk', 50)->nullable();
            $table->boolean('berkebutuhan_khusus')->default(false);
            $table->string('kebutuhan_khusus', 255)->nullable();

            // Address
            $table->text('alamat')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('dusun', 255)->nullable();
            $table->string('kota', 255)->nullable();
            $table->string('kabupaten', 255)->nullable();
            $table->string('provinsi', 255)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('kecamatan', 255)->nullable();
            $table->string('kelurahan', 255)->nullable();

            // Wilayah kode
            $table->string('provinsi_kode', 10)->nullable();
            $table->string('kabupaten_kode', 10)->nullable();
            $table->string('kecamatan_kode', 10)->nullable();
            $table->string('kelurahan_kode', 10)->nullable();
            $table->string('jenis_tinggal', 100)->nullable();
            $table->string('alat_transportasi', 100)->nullable();

            // Socioeconomic
            $table->boolean('penerima_kps')->default(false);
            $table->string('no_kps', 50)->nullable();

            // Father
            $table->string('nama_ayah', 255)->nullable();
            $table->string('nik_ayah', 50)->nullable();
            $table->date('tgl_lahir_ayah')->nullable();
            $table->string('pendidikan_ayah', 50)->nullable();
            $table->string('pekerjaan_ayah', 100)->nullable();
            $table->string('penghasilan_ayah', 100)->nullable();

            // Mother
            $table->string('nama_ibu', 255)->nullable();
            $table->string('nik_ibu', 50)->nullable();
            $table->date('tgl_lahir_ibu')->nullable();
            $table->string('pendidikan_ibu', 50)->nullable();
            $table->string('pekerjaan_ibu', 100)->nullable();
            $table->string('penghasilan_ibu', 100)->nullable();

            // Guardian
            $table->string('nama_wali', 255)->nullable();
            $table->string('nik_wali', 50)->nullable();
            $table->date('tgl_lahir_wali')->nullable();
            $table->string('pendidikan_wali', 50)->nullable();
            $table->string('pekerjaan_wali', 100)->nullable();
            $table->string('penghasilan_wali', 100)->nullable();

            // Legacy
            $table->string('pekerjaan_ortu', 100)->nullable();
            $table->string('penghasilan_ortu', 100)->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_krs ───
        Schema::create('akd_krs', function (Blueprint $table) {
            $table->id('krs_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->unsignedBigInteger('periode_akademik_id')->index();
            $table->string('status', 50)->default('draft');
            $table->unsignedSmallInteger('total_sks')->default(0);
            $table->unsignedBigInteger('disetujui_oleh')->nullable();
            $table->timestamp('tgl_disetujui')->nullable();
            $table->text('catatan')->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_krs_detail ───
        Schema::create('akd_krs_detail', function (Blueprint $table) {
            $table->id('krs_detail_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('krs_id')->index();
            $table->unsignedBigInteger('kelas_id')->index();
            $table->string('status', 50)->default('aktif');

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_nilai_akhir ───
        Schema::create('akd_nilai_akhir', function (Blueprint $table) {
            $table->id('nilai_akhir_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->unsignedBigInteger('kelas_id')->nullable()->index();
            $table->unsignedBigInteger('mata_kuliah_id')->index();
            $table->unsignedBigInteger('periode_akademik_id')->index();
            $table->decimal('nilai_angka', 3, 2)->nullable();
            $table->string('nilai_huruf', 10)->nullable();
            $table->decimal('bobot', 3, 2)->nullable();
            $table->unsignedSmallInteger('sks')->default(0);
            $table->boolean('is_lulus')->default(false);
            $table->string('source_type', 100)->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->timestamp('published_at')->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_cekal ───
        Schema::create('akd_cekal', function (Blueprint $table) {
            $table->id('cekal_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->string('jenis', 50)->default('akademik');
            $table->text('alasan');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->unsignedBigInteger('dicabut_oleh')->nullable();
            $table->date('tgl_dicabut')->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_cuti ───
        Schema::create('akd_cuti', function (Blueprint $table) {
            $table->id('cuti_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->unsignedBigInteger('periode_akademik_id')->index();
            $table->text('alasan');
            $table->string('status', 50)->default('pending');
            $table->unsignedBigInteger('disetujui_oleh')->nullable();
            $table->date('tgl_disetujui')->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_transfer ───
        Schema::create('akd_transfer', function (Blueprint $table) {
            $table->id('transfer_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->string('jenis', 50);
            $table->string('institusi_asal', 255);
            $table->string('prodi_asal', 255);
            $table->unsignedBigInteger('prodi_tujuan_id')->nullable();
            $table->unsignedSmallInteger('sks_diakui')->default(0);
            $table->unsignedSmallInteger('semester_diakui')->default(0);
            $table->string('status', 50)->default('pending');

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_riwayat_status ───
        Schema::create('akd_riwayat_status', function (Blueprint $table) {
            $table->id('riwayat_status_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->string('status_lama', 50)->nullable();
            $table->string('status_baru', 50);
            $table->text('alasan');
            $table->date('tgl_efektif');
            $table->string('diproses_oleh', 255);

            $this->addStandardColumns($table);
            $table->softDeletes();
        });

        // ─── akd_status_semester ───
        Schema::create('akd_status_semester', function (Blueprint $table) {
            $table->id('status_semester_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->unsignedBigInteger('periode_akademik_id')->nullable()->index();
            $table->string('status', 50)->default('aktif');
            $table->unsignedSmallInteger('semester_ke');

            $this->addStandardColumns($table);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akd_status_semester');
        Schema::dropIfExists('akd_riwayat_status');
        Schema::dropIfExists('akd_transfer');
        Schema::dropIfExists('akd_cuti');
        Schema::dropIfExists('akd_cekal');
        Schema::dropIfExists('akd_nilai_akhir');
        Schema::dropIfExists('akd_krs_detail');
        Schema::dropIfExists('akd_krs');
        Schema::dropIfExists('akd_biodata');
        Schema::dropIfExists('akd_mahasiswa');
    }

    /**
     * Standard columns for all tables (Blameable pattern).
     */
    protected function addStandardColumns(Blueprint $table): void
    {
        $table->string('created_by', 100)->nullable();
        $table->string('updated_by', 100)->nullable();
        $table->string('deleted_by', 100)->nullable();
        $table->unsignedBigInteger('created_by_id')->nullable();
        $table->unsignedBigInteger('updated_by_id')->nullable();
        $table->unsignedBigInteger('deleted_by_id')->nullable();
        $table->timestamps();
    }
};
