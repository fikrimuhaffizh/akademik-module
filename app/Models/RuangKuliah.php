<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\SoftDeletes;

class RuangKuliah extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_ruang_kuliah';
    protected $primaryKey = 'ruang_id';
    protected $fillable = [
        'tenant_id', 'kode', 'nama', 'gedung', 'lantai', 'kapasitas', 'jenis', 'is_aktif',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['lantai' => 'integer', 'kapasitas' => 'integer', 'is_aktif' => 'boolean'];
}
