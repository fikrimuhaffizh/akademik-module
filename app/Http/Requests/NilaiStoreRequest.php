<?php

namespace Modules\Akademik\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NilaiStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mahasiswa_id'       => 'required',
            'mata_kuliah_id'     => 'required',
            'kelas_id'           => 'nullable',
            'periode_akademik_id' => 'required',
            'nilai_angka'        => 'required|numeric|min:0|max:4',
            'sks'                => 'required|integer|min:1',
        ];
    }
}
