<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class NilaiRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'kelas_id' => ['nullable', 'integer', 'exists:akd_kelas_kuliah,kelas_id'],
            'mata_kuliah_id' => ['required', 'integer', 'exists:akd_mata_kuliah,mata_kuliah_id'],
            'periode_akademik_id' => ['required', 'integer', 'exists:akd_periode_akademik,periode_akademik_id'],
            'nilai_angka' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_huruf' => ['nullable', 'string', 'max:5'],
            'bobot' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'sks' => ['required', 'integer', 'min:1', 'max:20'],
            'source_reference' => ['nullable', 'string', 'max:191'],
            'is_lulus' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'kelas_id' => 'Kelas',
            'mata_kuliah_id' => 'Mata Kuliah',
            'periode_akademik_id' => 'Periode Akademik',
            'nilai_angka' => 'Nilai Angka',
            'nilai_huruf' => 'Nilai Huruf',
            'bobot' => 'Bobot',
            'sks' => 'SKS',
            'status' => 'Status',
            'is_lulus' => 'Lulus',
        ];
    }
}
