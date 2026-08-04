<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HashidBinding;

class Transfer extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_transfer';
    protected $primaryKey = 'transfer_id';

    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'jenis', 'institusi_asal', 'prodi_asal',
        'prodi_tujuan_id', 'sks_diakui', 'semester_diakui', 'status',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['sks_diakui' => 'integer', 'semester_diakui' => 'integer'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id'); }
}
