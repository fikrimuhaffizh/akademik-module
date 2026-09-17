<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class MahasiswaDraftBulkDestroyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:akd_mahasiswa_draft,draft_id',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'ids'   => 'Draft',
            'ids.*' => 'Draft',
        ];
    }
}
