<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StatusSemesterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'periode_akademik_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(['aktif', 'cuti', 'non_aktif'])],
            'semester_ke' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'periode_akademik_id' => 'Periode Akademik',
            'status' => 'Status',
            'semester_ke' => 'Semester Ke',
        ];
    }
}
