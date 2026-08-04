<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KalenderAkademik extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_kalender_akademik';
    protected $primaryKey = 'kalender_id';
    protected $fillable = [
        'tenant_id', 'periode_akademik_id', 'nama_kegiatan', 'tgl_mulai', 'tgl_selesai',
        'jenis', 'keterangan',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['tgl_mulai' => 'date', 'tgl_selesai' => 'date'];

    public function periodeAkademik()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id');
    }
}
