<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class SetKurikulumRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'draft_ids'    => ['required', 'array', 'min:1'],
            'draft_ids.*'  => ['integer'],
            'mode'         => ['required', 'in:auto,manual'],
            'kurikulum_kode' => ['nullable', 'string', 'max:50', 'required_if:mode,manual'],
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'draft_ids'      => 'Draft',
            'mode'           => 'Mode',
            'kurikulum_kode' => 'Kode Kurikulum',
        ];
    }

    public function messages(): array
    {
        return [
            'draft_ids.required' => 'Pilih minimal satu draft (centang di kolom pertama).',
        ];
    }
}
