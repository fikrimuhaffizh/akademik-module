<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HashidBinding;

class Cekal extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_cekal';
    protected $primaryKey = 'cekal_id';

    protected $fillable = [
        'tenant_id', 'mahasiswa_id', 'jenis', 'alasan', 'tgl_mulai', 'tgl_selesai',
        'is_aktif', 'dicabut_oleh', 'tgl_dicabut',
        'created_by', 'updated_by', 'deleted_by', 'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = ['tgl_mulai' => 'date', 'tgl_selesai' => 'date', 'is_aktif' => 'boolean', 'tgl_dicabut' => 'date'];

    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id'); }
}
