<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class PenawaranRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'periode_akademik_id' => 'required|exists:akd_periode_akademik,periode_akademik_id',
            'kurikulum_mata_kuliah_id' => 'required|exists:kur_kurikulum_mata_kuliah,kur_mk_id',
            'prodi_id' => 'required|integer',
            'is_aktif' => 'boolean',
            'is_wajib' => 'boolean',
            'grup_pilihan' => 'nullable|string|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'periode_akademik_id' => 'Periode Akademik',
            'kurikulum_mata_kuliah_id' => 'Mata Kuliah (Kurikulum)',
            'prodi_id' => 'Program Studi',
            'is_aktif' => 'Status Aktif',
            'is_wajib' => 'Mata Kuliah Wajib',
            'grup_pilihan' => 'Grup Pilihan',
        ];
    }
}
