<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class RuangKuliahRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('ruang_kuliah')?->ruang_id;
        return [
            'kode' => 'required|string|max:50|unique:akd_ruang_kuliah,kode,' . ($id ?? 'NULL') . ',ruang_id',
            'nama' => 'required|string|max:100',
            'gedung' => 'nullable|string|max:100',
            'lantai' => 'nullable|integer|min:1|max:50',
            'kapasitas' => 'required|integer|min:1',
            'jenis' => 'required|in:kelas,lab,aula,online',
            'is_aktif' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return ['kode' => 'Kode Ruang', 'nama' => 'Nama Ruang', 'gedung' => 'Gedung', 'lantai' => 'Lantai', 'kapasitas' => 'Kapasitas', 'jenis' => 'Jenis Ruang', 'is_aktif' => 'Status Aktif'];
    }
}
