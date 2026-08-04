<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CekalRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'jenis' => ['required', Rule::in(['keuangan', 'akademik', 'administrasi'])],
            'alasan' => ['required', 'string', 'max:1000'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['nullable', 'date', 'after_or_equal:tgl_mulai'],
            'is_aktif' => ['sometimes', 'boolean'],
            'dicabut_oleh' => ['nullable', 'string', 'max:255'],
            'tgl_dicabut' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'jenis' => 'Jenis Cekal',
            'alasan' => 'Alasan',
            'tgl_mulai' => 'Tanggal Mulai',
            'tgl_selesai' => 'Tanggal Selesai',
            'is_aktif' => 'Status Aktif',
            'dicabut_oleh' => 'Dicabut Oleh',
            'tgl_dicabut' => 'Tanggal Dicabut',
        ];
    }
}
