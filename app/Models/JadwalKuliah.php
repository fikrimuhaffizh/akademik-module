<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JadwalKuliah extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_jadwal_kuliah';

    protected $primaryKey = 'jadwal_id';

    protected $fillable = [
        'tenant_id', 'kelas_id', 'ruang_id', 'hari', 'jam_mulai', 'jam_selesai',
        'jenis_pertemuan', 'link_online',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = [
        'jenis_pertemuan' => 'string',
    ];

    public function kelas()
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_id', 'kelas_id');
    }

    public function ruang()
    {
        return $this->belongsTo(RuangKuliah::class, 'ruang_id', 'ruang_id');
    }

    /** Semua jadwal dianggap offline sejak metode_pembelajaran dihapus. */
    public function isOnline(): bool
    {
        return false;
    }
}
