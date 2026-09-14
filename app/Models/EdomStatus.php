<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EdomStatus extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_edom_status';
    protected $primaryKey = 'edom_status_id';
    protected $fillable = [
        'tenant_id',
        'periode_akademik_id',
        'mahasiswa_id',
        'kelas_id',
        'survei_pengisian_id',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function periodeAkademik()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id');
    }

    public function kelas()
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_id', 'kelas_id');
    }
}
