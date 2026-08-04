<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class RiwayatStatusRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'status_lama' => ['required', 'string', 'max:20'],
            'status_baru' => ['required', 'string', 'max:20'],
            'alasan' => ['nullable', 'string', 'max:1000'],
            'tgl_efektif' => ['required', 'date'],
            'diproses_oleh' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'status_lama' => 'Status Lama',
            'status_baru' => 'Status Baru',
            'alasan' => 'Alasan',
            'tgl_efektif' => 'Tanggal Efektif',
            'diproses_oleh' => 'Diproses Oleh',
        ];
    }
}
