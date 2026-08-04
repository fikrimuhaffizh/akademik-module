<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Akademik\Models\KelasKuliah;
use App\Traits\HashidBinding;

class KrsDetail extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_krs_detail';
    protected $primaryKey = 'krs_detail_id';

    protected $fillable = [
        'tenant_id', 'krs_id', 'kelas_id', 'status',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    public function krs() { return $this->belongsTo(Krs::class, 'krs_id', 'krs_id'); }
    public function kelas() { return $this->belongsTo(KelasKuliah::class, 'kelas_id', 'kelas_id'); }
}
