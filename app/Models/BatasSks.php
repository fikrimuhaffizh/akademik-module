<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatasSks extends Model
{
    use BelongsToTenant, Blameable, HasFactory, HashidBinding, SoftDeletes;

    protected $table = 'akd_batas_sks';

    protected $primaryKey = 'batas_sks_id';

    protected $fillable = [
        'periode_akademik_id',
        'ipk_min',
        'ipk_max',
        'max_sks',
    ];

    protected $casts = [
        'ipk_min' => 'decimal:2',
        'ipk_max' => 'decimal:2',
        'max_sks' => 'integer',
    ];

    public function periodeAkademik()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id');
    }
}
