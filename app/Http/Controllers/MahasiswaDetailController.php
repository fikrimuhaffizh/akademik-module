<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Kurikulum\Models\Kurikulum;
use Modules\Kurikulum\Services\KurikulumService;
use Modules\Akademik\Services\MahasiswaService;
use Modules\Akademik\Services\NilaiService;
use Modules\Akademik\Services\CekalService;
use Modules\Akademik\Services\CutiService;
use Modules\Akademik\Services\RiwayatStatusService;
use Modules\Akademik\Services\StatusSemesterService;

class MahasiswaDetailController extends Controller
{
    private const TABS = [
        'biodata' => ['label' => 'Biodata', 'icon' => 'ti ti-user'],
        'kurikulum' => ['label' => 'Kurikulum Mahasiswa', 'icon' => 'ti ti-book'],
        'status-semester' => ['label' => 'Status Semester', 'icon' => 'ti ti-calendar-check'],
        'khs' => ['label' => 'Kartu Hasil Studi', 'icon' => 'ti ti-report'],
        'riwayat-status' => ['label' => 'Riwayat Status', 'icon' => 'ti ti-history'],
        'cekal' => ['label' => 'Cekal', 'icon' => 'ti ti-ban'],
        'cuti' => ['label' => 'Cuti Akademik', 'icon' => 'ti ti-bed'],
    ];

    public function __construct(
        protected MahasiswaService $mahasiswaService,
        protected NilaiService $nilaiService,
        protected KurikulumService $kurikulumService,
    ) {}

    public function show(string $id, string $tab = 'biodata')
    {
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'biodata';
        }

        $mahasiswa = $this->mahasiswaService->findById($id);

        $tabData = match ($tab) {
            'biodata' => ['biodata' => $mahasiswa->biodata],
            'kurikulum' => $this->loadKurikulum($mahasiswa),
            'status-semester' => $this->loadStatusSemester($mahasiswa),
            'khs' => $this->loadKhs($mahasiswa),
            'riwayat-status' => $this->loadRiwayatStatus($mahasiswa),
            'cekal' => $this->loadCekal($mahasiswa),
            'cuti' => $this->loadCuti($mahasiswa),
        };

        return view('akademik::pages.mahasiswa.detail', [
            'mahasiswa' => $mahasiswa,
            'biodata' => $mahasiswa->biodata,
            'activeTab' => $tab,
            'tabs' => self::TABS,
            'tabData' => $tabData,
        ]);
    }

    /**
     * Load kurikulum mahasiswa dari kur_kurikulum_mata_kuliah pivot.
     * Resolve kurikulum via kurikulum_kode (business key) dari akmhs_mahasiswa.
     * Group by semester, tampilkan status lulus per MK dari NilaiService.
     */
    private function loadKurikulum($mahasiswa): array
    {
        $kurikulumId = null;
        $kurikulum = null;

        if (! empty($mahasiswa->kurikulum_kode)) {
            $kurikulum = $this->kurikulumService->findByKode($mahasiswa->kurikulum_kode);
            $kurikulumId = $kurikulum?->kurikulum_id;
        }

        $nilaiCollection = $this->nilaiService->getFilteredQuery([
            'mahasiswa_id' => encryptId($mahasiswa->mahasiswa_id),
        ])->get();

        $nilaiMap = $nilaiCollection->keyBy('mata_kuliah_id');

        $semesterData = [];
        $totalSksKurikulum = 0;
        $totalSksLulus = 0;

        if ($kurikulumId) {
            $mkItems = $this->kurikulumService->getMataKuliahs($kurikulumId);

            foreach ($mkItems as $item) {
                $sem = $item->semester;
                $mk = $item->mataKuliah;
                if (! $mk) {
                    continue;
                }

                $sks = $mk->sks;
                $nilai = $nilaiMap->get($mk->mata_kuliah_id);
                $isLulus = $nilai && $nilai->is_lulus;

                $semesterData[$sem][] = [
                    'mata_kuliah' => $mk,
                    'is_wajib' => $item->is_wajib,
                    'grup_pilihan' => $item->grup_pilihan,
                    'nilai' => $nilai,
                    'is_lulus' => $isLulus,
                    'sks' => $sks,
                ];

                $totalSksKurikulum += $sks;
                if ($isLulus) {
                    $totalSksLulus += $sks;
                }
            }
        }

        ksort($semesterData);

        return [
            'kurikulum' => $kurikulum,
            'semesters' => $semesterData,
            'nilaiMap' => $nilaiMap,
            'totalSksKurikulum' => $totalSksKurikulum,
            'totalSksLulus' => $totalSksLulus,
            'totalSksBelum' => $totalSksKurikulum - $totalSksLulus,
            'ipk' => $this->nilaiService->hitungIpk($mahasiswa->mahasiswa_id),
        ];
    }

    private function loadStatusSemester($mahasiswa): array
    {
        return [
            'records' => (new StatusSemesterService)
                ->getFilteredQuery(['mahasiswa_id' => encryptId($mahasiswa->mahasiswa_id)])
                ->get(),
        ];
    }

    private function loadKhs($mahasiswa): array
    {
        $nilai = $this->nilaiService->getFilteredQuery([
            'mahasiswa_id' => encryptId($mahasiswa->mahasiswa_id),
        ])->get();

        return [
            'byPeriode' => $nilai->groupBy('periode_akademik_id'),
            'ipk' => $this->nilaiService->hitungIpk($mahasiswa->mahasiswa_id),
            'transkrip' => $this->nilaiService->getTranskrip($mahasiswa->mahasiswa_id),
        ];
    }

    private function loadRiwayatStatus($mahasiswa): array
    {
        return [
            'records' => (new RiwayatStatusService)
                ->getFilteredQuery(['mahasiswa_id' => encryptId($mahasiswa->mahasiswa_id)])
                ->get(),
        ];
    }

    private function loadCekal($mahasiswa): array
    {
        return [
            'records' => (new CekalService)
                ->getFilteredQuery(['mahasiswa_id' => encryptId($mahasiswa->mahasiswa_id)])
                ->get(),
        ];
    }

    private function loadCuti($mahasiswa): array
    {
        return [
            'records' => (new CutiService)
                ->getFilteredQuery(['mahasiswa_id' => encryptId($mahasiswa->mahasiswa_id)])
                ->get(),
        ];
    }
}
