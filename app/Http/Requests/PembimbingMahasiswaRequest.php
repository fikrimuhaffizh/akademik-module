<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class PembimbingMahasiswaRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'periode_akademik_id' => 'required',
            'pegawai_id' => 'required',
            'mahasiswa_id' => 'required',
            'jenis_pembimbing' => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'periode_akademik_id' => 'Periode Akademik',
            'pegawai_id' => 'Dosen Pembimbing',
            'mahasiswa_id' => 'Mahasiswa',
            'jenis_pembimbing' => 'Jenis Pembimbing',
        ];
    }
}
