<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class JadwalKuliahRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'kelas_id' => 'required|exists:akd_kelas_kuliah,kelas_id',
            'ruang_id' => 'nullable|exists:akd_ruang_kuliah,ruang_id',
            'hari' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'metode_pembelajaran' => 'required|in:offline,online,hybrid',
            'jenis_pertemuan' => 'required|in:teori,praktikum',
            'link_online' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'kelas_id' => 'Kelas Kuliah',
            'ruang_id' => 'Ruang Kuliah',
            'hari' => 'Hari',
            'jam_mulai' => 'Jam Mulai',
            'jam_selesai' => 'Jam Selesai',
            'metode_pembelajaran' => 'Metode Pembelajaran',
            'jenis_pertemuan' => 'Jenis Pertemuan',
            'link_online' => 'Link Online',
        ];
    }
}
