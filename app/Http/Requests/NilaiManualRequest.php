<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class NilaiManualRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'nim' => ['required', 'string', 'exists:akd_mahasiswa,nim'],
            'kelas_id' => ['required', 'exists:akd_kelas_kuliah,kelas_id'],
            'nilai_angka' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_huruf' => ['nullable', 'string', 'max:2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nim' => 'NIM',
            'kelas_id' => 'Kelas Kuliah',
            'nilai_angka' => 'Nilai Angka',
            'nilai_huruf' => 'Nilai Huruf',
        ];
    }
}
