<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PembimbingMahasiswa extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_pembimbing_mahasiswa';
    protected $primaryKey = 'pma_id';
    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'dosen_id', 'periode_akademik_id', 'jenis_pembimbing',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    public function periodeAkademik()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id');
    }

    public function jenisPembimbing()
    {
        return $this->belongsTo(\Modules\Referensi\Models\SysRef::class, 'jenis_pembimbing', 'ref_id');
    }
}
