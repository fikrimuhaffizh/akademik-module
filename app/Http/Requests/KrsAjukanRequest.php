<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class KrsAjukanRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'krs_id' => 'required',
        ];
    }

    protected function customAttributes(): array
    {
        return [
            'krs_id' => 'KRS',
        ];
    }
}
