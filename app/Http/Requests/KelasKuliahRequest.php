<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;
use Modules\Referensi\Models\SysRef;

class KelasKuliahRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'penawaran_id' => 'required|exists:akd_penawaran_mk,penawaran_id',
            'ref_kelas_id' => 'required|exists:sys_refs,ref_id',
            'kapasitas' => 'required|integer|min:1|max:500',
            'sistem_kuliah' => 'required|in:reguler,online,hybrid',

            // Dosen pengampu dinamis (minimal 1)
            'pembebanan' => 'required|array|min:1',
            'pembebanan.*.pegawai_id' => 'required',
            'pembebanan.*.peran' => 'required|in:pengampu,asisten,koordinator',

            // Jadwal mingguan dinamis (boleh kosong)
            'jadwals' => 'nullable|array',
            'jadwals.*.hari' => 'required_with:jadwals|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'jadwals.*.jam_mulai' => 'required_with:jadwals|date_format:H:i',
            'jadwals.*.jam_selesai' => 'required_with:jadwals|date_format:H:i|after:jadwals.*.jam_mulai',
            'jadwals.*.ruang_id' => 'nullable|exists:akd_ruang_kuliah,ruang_id',
            'jadwals.*.jenis_pertemuan' => 'required_with:jadwals|in:teori,praktikum',
            'jadwals.*.link_online' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'penawaran_id' => 'Penawaran MK',
            'ref_kelas_id' => 'Kelas',
            'kapasitas' => 'Kapasitas',
            'sistem_kuliah' => 'Sistem Kuliah',
            'pembebanan' => 'Dosen Pengampu',
            'pembebanan.*.pegawai_id' => 'Dosen',
            'pembebanan.*.peran' => 'Peran',
            'jadwals' => 'Jadwal Mingguan',
            'jadwals.*.hari' => 'Hari',
            'jadwals.*.jam_mulai' => 'Jam Mulai',
            'jadwals.*.jam_selesai' => 'Jam Selesai',
            'jadwals.*.ruang_id' => 'Ruang',
            'jadwals.*.jenis_pertemuan' => 'Jenis Pertemuan',
        ];
    }

    /**
     * Setelah validasi, denormalisasi nama_kelas dari label referensi
     * (kelas_perkuliahan) agar tampilan di jadwal/KRS/EDOM tetap konsisten.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (isset($data['ref_kelas_id'])) {
            $ref = SysRef::find($data['ref_kelas_id']);
            $data['nama_kelas'] = $ref?->label ?? $data['ref_kelas_id'];
        }

        return $key !== null ? ($data[$key] ?? $default) : $data;
    }
}
