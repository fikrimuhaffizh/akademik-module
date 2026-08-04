<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class KalenderAkademikRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'periode_akademik_id' => 'required|integer',
            'nama_kegiatan' => 'required|string|max:200',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'jenis' => 'required|string|max:50',
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'periode_akademik_id' => 'Periode Akademik',
            'nama_kegiatan' => 'Nama Kegiatan',
            'tgl_mulai' => 'Tanggal Mulai',
            'tgl_selesai' => 'Tanggal Selesai',
            'jenis' => 'Jenis Kegiatan',
            'keterangan' => 'Keterangan',
        ];
    }
}
