<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'jenis' => ['required', Rule::in(['masuk', 'keluar', 'pindah_prodi'])],
            'institusi_asal' => ['nullable', 'string', 'max:255'],
            'prodi_asal' => ['nullable', 'string', 'max:255'],
            'prodi_tujuan_id' => ['nullable', 'integer'],
            'sks_diakui' => ['nullable', 'integer', 'min:0'],
            'semester_diakui' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['pending', 'disetujui', 'ditolak'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'jenis' => 'Jenis Transfer',
            'institusi_asal' => 'Institusi Asal',
            'prodi_asal' => 'Prodi Asal',
            'prodi_tujuan_id' => 'Prodi Tujuan',
            'sks_diakui' => 'SKS Diakui',
            'semester_diakui' => 'Semester Diakui',
            'status' => 'Status',
        ];
    }
}
