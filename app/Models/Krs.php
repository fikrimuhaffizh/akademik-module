<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\PeriodeAkademik;

class Krs extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_krs';
    protected $primaryKey = 'krs_id';
    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'periode_akademik_id', 'status', 'total_sks',
        'disetujui_oleh', 'tgl_disetujui', 'catatan',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['total_sks' => 'integer', 'tgl_disetujui' => 'datetime'];

    public function periodeAkademik() { return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id'); }
    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id'); }
    public function details() { return $this->hasMany(KrsDetail::class, 'krs_id', 'krs_id'); }
}
