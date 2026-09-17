<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class KrsStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id'        => 'required',
            'periode_akademik_id' => 'required',
            'kelas_ids'           => 'required|array|min:1',
            'kelas_ids.*'         => 'required',
        ];
    }
}
