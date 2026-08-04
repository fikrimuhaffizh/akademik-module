<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PeriodeAkademik extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_periode_akademik';
    protected $primaryKey = 'periode_akademik_id';
    protected $fillable = [
        'tenant_id', 'nama', 'tahun_mulai', 'tahun_selesai', 'semester', 'tgl_mulai', 'tgl_selesai',
        'is_aktif',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['tahun_mulai' => 'integer', 'tahun_selesai' => 'integer', 'tgl_mulai' => 'date', 'tgl_selesai' => 'date', 'is_aktif' => 'boolean'];
}
