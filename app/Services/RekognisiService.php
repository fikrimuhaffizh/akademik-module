<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\KonversiNilai;
use Illuminate\Support\Collection;

class RekognisiService
{
    public function getApprovedForTranskrip(int $mahasiswaId): Collection
    {
        return KonversiNilai::query()
            ->leftJoin('kur_mata_kuliah as mk', 'akd_konversi_nilai.mata_kuliah_id', '=', 'mk.mata_kuliah_id')
            ->where('akd_konversi_nilai.mahasiswa_id', $mahasiswaId)
            ->where('akd_konversi_nilai.status', 'disetujui')
            ->select([
                'akd_konversi_nilai.konversi_id as nilai_akhir_id',
                'akd_konversi_nilai.tenant_id',
                'akd_konversi_nilai.mahasiswa_id',
                'akd_konversi_nilai.periode_akademik_id',
                'akd_konversi_nilai.mata_kuliah_id',
                'mk.kode as kode_mk',
                'mk.nama as nama_mk',
                'akd_konversi_nilai.mata_kuliah_asal',
                'akd_konversi_nilai.sks_asal',
                'akd_konversi_nilai.sks_diakui',
                'akd_konversi_nilai.nilai_angka',
                'akd_konversi_nilai.nilai_konversi as nilai_huruf',
                'akd_konversi_nilai.bobot',
                'akd_konversi_nilai.jenis_rekognisi',
                'akd_konversi_nilai.keterangan',
            ])
            ->orderBy('akd_konversi_nilai.periode_akademik_id')
            ->get()
            ->map(function ($row) {
                $row->sks = (int) ($row->sks_diakui ?: $row->sks_asal);
                $row->is_lulus = true;
                $row->is_published = true;
                $row->sumber_nilai = 'rekognisi';

                return $row;
            });
    }

    public function getTotalSksDiakui(int $mahasiswaId): int
    {
        return (int) $this->getApprovedForTranskrip($mahasiswaId)->sum('sks');
    }
}
