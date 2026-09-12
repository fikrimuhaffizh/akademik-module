<?php

namespace Modules\Akademik\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class CreateFromPmbRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'nim' => ['required', 'string', 'max:30'],
            'user_id' => ['nullable', 'integer'],
            'nama' => ['required', 'string', 'max:255'],
            'prodi_id' => ['required', 'integer'],
            'angkatan' => ['required', 'integer', 'min:2000'],
            'pmb_pendaftar_id' => ['nullable', 'integer'],
            'jenis_masuk' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'nim' => 'NIM',
            'user_id' => 'User ID',
            'nama' => 'Nama',
            'prodi_id' => 'Prodi ID',
            'angkatan' => 'Angkatan',
            'pmb_pendaftar_id' => 'PMB Pendaftar ID',
            'jenis_masuk' => 'Jenis Masuk',
        ];
    }
}
