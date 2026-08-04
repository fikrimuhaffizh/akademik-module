<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Referensi\Models\SysRef;

class KelasKuliah extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_kelas_kuliah';
    protected $primaryKey = 'kelas_id';
    protected $fillable = [
        'tenant_id', 'penawaran_id', 'ref_kelas_id', 'nama_kelas', 'kapasitas',
        'is_aktif',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    /**
     * sistem_kuliah diambil dari penawaran (sumber per-MK/kurikulum),
     * bukan disimpan di kelas — hindari duplikasi.
     */
    public function getSistemKuliahAttribute(): ?string
    {
        return $this->penawaran?->sistem_kuliah ?? 'reguler';
    }

    protected $casts = [
        'kapasitas' => 'integer',
        'is_aktif' => 'boolean',
    ];

    public function penawaran() { return $this->belongsTo(PenawaranMataKuliah::class, 'penawaran_id', 'penawaran_id'); }
    public function penawaranMataKuliah() { return $this->penawaran(); }
    public function refKelas() { return $this->belongsTo(SysRef::class, 'ref_kelas_id', 'ref_id'); }
    public function pembebananDosens() { return $this->hasMany(PembebananDosen::class, 'kelas_id', 'kelas_id'); }
    public function jadwalKuliahs() { return $this->hasMany(JadwalKuliah::class, 'kelas_id', 'kelas_id'); }
    public function krsDetails() { return $this->hasMany(KrsDetail::class, 'kelas_id', 'kelas_id'); }

    public function periodeAkademik()
    {
        return $this->penawaran?->periodeAkademik();
    }
}
