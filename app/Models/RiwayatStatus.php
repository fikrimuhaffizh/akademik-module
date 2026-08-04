<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HashidBinding;

class RiwayatStatus extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_riwayat_status';
    protected $primaryKey = 'riwayat_status_id';

    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'status_lama', 'status_baru', 'alasan',
        'tgl_efektif', 'diproses_oleh',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['tgl_efektif' => 'date'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id'); }
}
