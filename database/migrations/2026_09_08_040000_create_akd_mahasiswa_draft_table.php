<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akd_mahasiswa_draft', function (Blueprint $table) {
            $table->id('draft_id');
            $table->unsignedBigInteger('tenant_id')->default(1)->index();

            // PMB Bridge (unique per tenant — prevents double sync)
            $table->unsignedBigInteger('pmb_pendaftar_id');
            $table->unique(['tenant_id', 'pmb_pendaftar_id'], 'akd_draft_tenant_pmb_unique');

            // Academic identity
            $table->string('nim', 50)->nullable();
            $table->string('nama', 255);
            $table->string('email', 255)->nullable();
            $table->string('no_hp', 50)->nullable();

            // Academic info
            $table->unsignedBigInteger('prodi_id')->nullable()->index();
            $table->unsignedInteger('angkatan');
            $table->string('kurikulum_kode', 50)->nullable();
            $table->string('jenis_masuk', 50)->nullable();
            $table->string('sistem_kuliah', 50)->nullable();

            // Status
            $table->string('status_draft', 20)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();

            // Full PMB data snapshot (kandidat, ortu, alamat, sekolah, nilai, etc.)
            $table->json('snapshot_json')->nullable();

            $this->addStandardColumns($table);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akd_mahasiswa_draft');
    }

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
