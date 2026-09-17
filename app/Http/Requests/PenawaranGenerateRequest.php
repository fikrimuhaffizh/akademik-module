<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class PenawaranGenerateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'periode_akademik_id' => 'required|integer',
            'prodi_id'            => 'required|integer',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'periode_akademik_id' => 'Periode Akademik',
            'prodi_id'            => 'Program Studi',
        ];
    }
}
