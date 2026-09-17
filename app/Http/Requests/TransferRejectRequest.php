<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class TransferRejectRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'alasan' => 'required|string|max:1000',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'alasan' => 'Alasan Penolakan',
        ];
    }
}
