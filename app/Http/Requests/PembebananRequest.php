<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class PembebananRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'kelas_id' => 'required|exists:akd_kelas_kuliah,kelas_id',
            'pegawai_id' => 'required|exists:hr_pegawai,pegawai_id',
            'peran' => 'required|in:pengampu,asisten,koordinator',
        ];
    }

    public function attributes(): array
    {
        return [
            'kelas_id' => 'Kelas Kuliah',
            'pegawai_id' => 'Dosen',
            'peran' => 'Peran',
        ];
    }
}
