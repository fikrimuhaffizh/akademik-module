<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PembebananDosen extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_pembebanan_dosen';

    protected $primaryKey = 'pembebanan_id';

    protected $fillable = [
        'tenant_id', 'kelas_id', 'pegawai_id', 'peran',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    public function kelas()
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_id', 'kelas_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(\Modules\HrCore\Models\Pegawai::class, 'pegawai_id', 'pegawai_id');
    }
}
