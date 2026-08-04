<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HashidBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublishBatch extends Model
{
    use BelongsToTenant, Blameable, HashidBinding, SoftDeletes;

    protected $table = 'akd_publish_batch';
    protected $primaryKey = 'publish_batch_id';
    protected $fillable = [
        'tenant_id',
        'source_module',
        'reference_code',
        'total_data',
        'success_count',
        'conflict_count',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

}
