<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KonversiNilai extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_konversi_nilai';
    protected $primaryKey = 'konversi_nilai_id';
    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'mata_kuliah_id', 'nilai_asal', 'nilai_konversi',
        'sks', 'catatan',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];
}
