<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class MahasiswaDraftSubmitRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'draft_ids'   => 'required|array',
            'draft_ids.*' => 'integer|exists:akd_mahasiswa_draft,draft_id',
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
