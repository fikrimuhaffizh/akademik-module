<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class SetStatusDraftRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'draft_ids'    => ['required', 'array', 'min:1'],
            'draft_ids.*'  => ['integer'],
            'status_draft' => ['required', 'in:draft,terima,batal'],
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'draft_ids'    => 'Draft',
            'status_draft' => 'Status Draft',
        ];
    }

    public function messages(): array
    {
        return [
            'draft_ids.required' => 'Pilih minimal satu draft (centang di kolom pertama).',
        ];
    }
}
