<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\KonversiNilai;
use Illuminate\Support\Collection;

class RekognisiService
{
    public function getApprovedForTranskrip(int $mahasiswaId): Collection
    {
        return KonversiNilai::query()
            ->leftJoin('kur_mata_kuliah as mk', 'akper_konversi_nilai.mata_kuliah_id', '=', 'mk.mata_kuliah_id')
            ->where('akper_konversi_nilai.mahasiswa_id', $mahasiswaId)
            ->where('akper_konversi_nilai.status', 'disetujui')
            ->select([
                'akper_konversi_nilai.konversi_id as nilai_akhir_id',
                'akper_konversi_nilai.tenant_id',
                'akper_konversi_nilai.mahasiswa_id',
                'akper_konversi_nilai.periode_akademik_id',
                'akper_konversi_nilai.mata_kuliah_id',
                'mk.kode as kode_mk',
                'mk.nama as nama_mk',
                'akper_konversi_nilai.mata_kuliah_asal',
                'akper_konversi_nilai.sks_asal',
                'akper_konversi_nilai.sks_diakui',
                'akper_konversi_nilai.nilai_angka',
                'akper_konversi_nilai.nilai_konversi as nilai_huruf',
                'akper_konversi_nilai.bobot',
                'akper_konversi_nilai.jenis_rekognisi',
                'akper_konversi_nilai.keterangan',
            ])
            ->orderBy('akper_konversi_nilai.periode_akademik_id')
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
