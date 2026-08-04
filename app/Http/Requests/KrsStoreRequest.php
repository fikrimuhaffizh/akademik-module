<?php

namespace Modules\Akademik\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KrsStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
