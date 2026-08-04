<?php

namespace Modules\Akademik\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HashidBinding;

class Biodata extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes, HashidBinding;

    protected $table = 'akd_biodata';
    protected $primaryKey = 'biodata_id';

    protected $fillable = [
        'tenant_id',
        'mahasiswa_id',

        // Identity
        'nik',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',

        // Demographic
        'agama',
        'kewarganegaraan',
        'suku',
        'no_kk',
        'berkebutuhan_khusus',
        'kebutuhan_khusus',

        // Address
        'alamat',
        'rt',
        'rw',
        'dusun',
        'kota',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'kecamatan',
        'kelurahan',

        // Wilayah kode (BPS codes)
        'provinsi_kode',
        'kabupaten_kode',
        'kecamatan_kode',
        'kelurahan_kode',
        'jenis_tinggal',
        'alat_transportasi',

        // Socioeconomic
        'penerima_kps',
        'no_kps',

        // Father
        'nama_ayah',
        'nik_ayah',
        'tgl_lahir_ayah',
        'pendidikan_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',

        // Mother
        'nama_ibu',
        'nik_ibu',
        'tgl_lahir_ibu',
        'pendidikan_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',

        // Guardian (Wali)
        'nama_wali',
        'nik_wali',
        'tgl_lahir_wali',
        'pendidikan_wali',
        'pekerjaan_wali',
        'penghasilan_wali',

        // Legacy combined fields (deprecated, use individual fields above)
        'pekerjaan_ortu',
        'penghasilan_ortu',

        // Blameable
        'created_by', 'updated_by', 'deleted_by',
        'created_by_id', 'updated_by_id', 'deleted_by_id',
    ];

    protected $casts = [
        'tgl_lahir' => 'date',
        'tgl_lahir_ayah' => 'date',
        'tgl_lahir_ibu' => 'date',
        'tgl_lahir_wali' => 'date',
        'berkebutuhan_khusus' => 'boolean',
        'penerima_kps' => 'boolean',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id');
    }
}
