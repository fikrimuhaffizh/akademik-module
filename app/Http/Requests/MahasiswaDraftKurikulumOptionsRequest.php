<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class MahasiswaDraftKurikulumOptionsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'draft_ids'   => ['required', 'array', 'min:1'],
            'draft_ids.*' => ['integer'],
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'draft_ids'   => 'Draft',
            'draft_ids.*' => 'Draft',
        ];
    }
}
