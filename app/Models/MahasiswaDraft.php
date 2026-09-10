<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HashidBinding;

class MahasiswaDraft extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_mahasiswa_draft';
    protected $primaryKey = 'draft_id';

    protected $fillable = [
        'tenant_id',
        'pmb_pendaftar_id',
        'nim',
        'nama',
        'email',
        'no_hp',
        'prodi_id',
        'angkatan',
        'kurikulum_kode',
        'jenis_masuk',
        'sistem_kuliah',
        'status_draft',
        'submitted_at',
        'snapshot_json',
        'created_by', 'updated_by', 'deleted_by',
        'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = [
        'angkatan' => 'integer',
        'submitted_at' => 'datetime',
        'snapshot_json' => 'array',
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(\Modules\HrCore\Models\StrukturOrganisasi::class, 'prodi_id', 'orgunit_id');
    }

    public function getCamabaAttribute(): ?array
    {
        return $this->snapshot_json['camaba'] ?? null;
    }

    public function scopeDraft($query)
    {
        return $query->where('status_draft', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status_draft', 'submitted');
    }
}
