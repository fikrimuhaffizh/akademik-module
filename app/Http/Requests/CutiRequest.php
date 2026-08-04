<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CutiRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'periode_akademik_id' => ['required', 'integer'],
            'alasan' => ['required', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(['pending', 'disetujui', 'ditolak'])],
            'disetujui_oleh' => ['nullable', 'string', 'max:255'],
            'tgl_disetujui' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'periode_akademik_id' => 'Periode Akademik',
            'alasan' => 'Alasan',
            'status' => 'Status',
            'disetujui_oleh' => 'Disetujui Oleh',
            'tgl_disetujui' => 'Tanggal Disetujui',
        ];
    }
}
