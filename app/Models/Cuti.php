<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HashidBinding;

class Cuti extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_cuti';
    protected $primaryKey = 'cuti_id';

    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'periode_akademik_id', 'alasan', 'status',
        'disetujui_oleh', 'tgl_disetujui',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['tgl_disetujui' => 'date'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id'); }
}
