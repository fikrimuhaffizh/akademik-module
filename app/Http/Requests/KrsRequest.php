<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class KrsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => 'required|integer',
            'periode_akademik_id' => 'required|exists:akd_periode_akademik,periode_akademik_id',
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'required|integer|exists:akd_kelas_kuliah,kelas_id',
            'status' => 'nullable|in:draft,diajukan,disetujui,ditolak',
            'catatan' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return ['mahasiswa_id' => 'Mahasiswa', 'periode_akademik_id' => 'Periode Akademik', 'kelas_ids' => 'Kelas Kuliah', 'status' => 'Status', 'catatan' => 'Catatan'];
    }
}
