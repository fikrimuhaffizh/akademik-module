<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Kurikulum\Models\MataKuliah;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Models\PeriodeAkademik;

class Nilai extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    public const SOURCE_PUBLISH_LMS = 'publish_lms';
    public const SOURCE_IMPORT_MANUAL = 'import_manual';

    protected $table = 'akd_nilai_akhir';
    protected $primaryKey = 'nilai_akhir_id';
    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'kelas_id', 'mata_kuliah_id', 'periode_akademik_id',
        'nilai_angka', 'nilai_huruf', 'bobot', 'sks', 'is_lulus',
        'source_type', 'source_reference', 'published_at',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = [
        'nilai_angka' => 'decimal:2',
        'bobot' => 'decimal:2',
        'sks' => 'integer',
        'is_lulus' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id'); }

    public function mataKuliah() { return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id', 'mata_kuliah_id'); }
    public function kelas() { return $this->belongsTo(KelasKuliah::class, 'kelas_id', 'kelas_id'); }
    public function periodeAkademik() { return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id'); }
}
