<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class TahunAjaranRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:100',
            'tahun_mulai' => 'required|integer|min:2000|max:2100',
            'tahun_selesai' => 'required|integer|min:2000|max:2100|gte:tahun_mulai',
            'is_aktif' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'Nama Tahun Ajaran',
            'tahun_mulai' => 'Tahun Mulai',
            'tahun_selesai' => 'Tahun Selesai',
            'is_aktif' => 'Status Aktif',
        ];
    }
}
