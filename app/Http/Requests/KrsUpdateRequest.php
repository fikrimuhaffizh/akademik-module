<?php

namespace Modules\Akademik\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KrsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:draft,diajukan,disetujui,ditolak',
        ];
    }
}
