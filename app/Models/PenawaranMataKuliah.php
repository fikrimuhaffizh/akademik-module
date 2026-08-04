<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Kurikulum\Models\KurikulumMataKuliah;

class PenawaranMataKuliah extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_penawaran_mk';
    protected $primaryKey = 'penawaran_id';
    protected $fillable = [
        'tenant_id', 'periode_akademik_id', 'kurikulum_mata_kuliah_id', 'prodi_id',
        'kurikulum_kode', 'is_wajib', 'grup_pilihan', 'sistem_kuliah', 'is_aktif',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['is_wajib' => 'boolean', 'is_aktif' => 'boolean'];

    public function periodeAkademik()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id');
    }

    public function kurikulumMataKuliah()
    {
        return $this->belongsTo(KurikulumMataKuliah::class, 'kurikulum_mata_kuliah_id', 'kur_mk_id');
    }

    public function kelasKuliahs()
    {
        return $this->hasMany(KelasKuliah::class, 'penawaran_id', 'penawaran_id');
    }
}
