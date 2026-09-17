<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class MahasiswaDraftUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'nim'            => 'nullable|string|max:50',
            'nama'           => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'kurikulum_kode' => 'nullable|string|max:50',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'nim'            => 'NIM',
            'nama'           => 'Nama',
            'email'          => 'Email',
            'kurikulum_kode' => 'Kode Kurikulum',
        ];
    }
}
