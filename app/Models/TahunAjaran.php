<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunAjaran extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_tahun_ajaran';
    protected $primaryKey = 'tahun_ajaran_id';
    protected $fillable = [
        'tenant_id', 'nama', 'tahun_mulai', 'tahun_selesai', 'is_aktif',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['tahun_mulai' => 'integer', 'tahun_selesai' => 'integer', 'is_aktif' => 'boolean'];
}
