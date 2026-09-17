<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class KrsToggleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => 'required',
            'kelas_id'     => 'required',
            'ambil'        => 'required|boolean',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'kelas_id'     => 'Kelas',
            'ambil'        => 'Ambil',
        ];
    }
}
