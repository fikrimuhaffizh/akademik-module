<?php

namespace Modules\Akademik\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model pembaca untuk tabel akper_edom_kelas.
 * Hanya untuk keperluan membaca konfigurasi EDOM (survei_id, status).
 */
class EdomKelas extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;


    protected $table = 'akd_edom_kelas';
    protected $primaryKey = 'edom_kelas_id';
    protected $fillable = [
        'tenant_id',
        'kelas_id',
        'periode_akademik_id',
        'survei_id',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'edom_kelas_id';
    }
}
