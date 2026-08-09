<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Akademik\Models\Krs;
use Modules\HrCore\Models\StrukturOrganisasi;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mahasiswa extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_mahasiswa';
    protected $primaryKey = 'mahasiswa_id';
    protected $fillable = [
        'tenant_id',

        // Academic Identity
        'nim',
        'nama',
        'email',
        'no_hp',
        'prodi_id',
        'angkatan',
        'kurikulum_kode',
        'status',

        // Enrollment
        'jenis_masuk',
        'jenis_masuk_detail',
        'semester_masuk',
        'tanggal_awal_masuk',
        'tanggal_daftar_ulang',
        'sistem_kuliah',
        'sks_diakui_awal',

        // Transfer / Previous Institution
        'institusi_asal',
        'prodi_asal',

        // PMB Bridge
        'pmb_pendaftar_id',
        'user_id',

        // Profile
        'foto',
        'metadata',

        // Blameable
        'created_by', 'updated_by', 'deleted_by',
        'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = [
        'angkatan' => 'integer',
        'semester_masuk' => 'integer',
        'sks_diakui_awal' => 'integer',
        'tanggal_awal_masuk' => 'date',
        'tanggal_daftar_ulang' => 'date',
        'metadata' => 'array',
    ];

    public function biodata(): HasOne
    {
        return $this->hasOne(Biodata::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(StrukturOrganisasi::class, 'prodi_id', 'orgunit_id');
    }

    public function krs(): HasMany
    {
        return $this->hasMany(Krs::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function cekals(): HasMany
    {
        return $this->hasMany(Cekal::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function cutis(): HasMany
    {
        return $this->hasMany(Cuti::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Status keaktifan mahasiswa: kolom status 'aktif' DAN tidak sedang
     * dicekal / cuti aktif.
     */
    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'aktif') {
            return false;
        }

        return ! ($this->cekalAktif()->exists() || $this->cutiAktif()->exists());
    }

    protected function cekalAktif(): HasMany
    {
        return $this->hasMany(Cekal::class, 'mahasiswa_id', 'mahasiswa_id')->where('is_aktif', true);
    }

    protected function cutiAktif(): HasMany
    {
        return $this->hasMany(Cuti::class, 'mahasiswa_id', 'mahasiswa_id')->where('status', 'disetujui');
    }
}