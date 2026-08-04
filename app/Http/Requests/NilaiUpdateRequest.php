<?php

namespace Modules\Akademik\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NilaiUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nilai_angka' => 'required|numeric|min:0|max:4',
        ];
    }
}
