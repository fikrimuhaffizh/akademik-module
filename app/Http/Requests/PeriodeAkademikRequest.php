<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class PeriodeAkademikRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:100',
            'tahun_mulai' => 'required|digits:4|integer|lte:tahun_selesai',
            'tahun_selesai' => 'required|digits:4|integer|gte:tahun_mulai',
            'semester' => 'required|in:ganjil,genap,pendek',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'is_aktif' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'Nama Periode',
            'tahun_mulai' => 'Tahun Mulai',
            'tahun_selesai' => 'Tahun Selesai',
            'semester' => 'Semester',
            'tgl_mulai' => 'Tanggal Mulai',
            'tgl_selesai' => 'Tanggal Selesai',
            'is_aktif' => 'Status Aktif',
        ];
    }
}
