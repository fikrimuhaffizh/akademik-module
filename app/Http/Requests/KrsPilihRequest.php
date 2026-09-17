<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class KrsPilihRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => 'required',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
        ];
    }
}
