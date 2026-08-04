<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Mahasiswa as MahasiswaModel;
use Modules\Akademik\Models\Nilai;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Services\NilaiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class NilaiImportService
{
    public function __construct(protected NilaiService $nilaiService) {}

    public function getKelasOptions()
    {
        return KelasKuliah::with('penawaran.kurikulumMataKuliah.mataKuliah')
            ->orderBy('nama_kelas')
            ->get();
    }

    public function getNilaiQuery(?int $kelasId): Builder
    {
        return Nilai::query()
            ->with('mahasiswa')
            ->when($kelasId, fn (Builder $query) => $query->where('kelas_id', $kelasId))
            ->whereRaw($kelasId ? '1 = 1' : '1 = 0')
            ->orderBy('created_at');
    }

    /**
     * Import nilai dari CSV/Excel sebagai fallback tanpa LMS.
     * Admin bisa langsung input/import nilai per kelas.
     */
    public function importFromExcel(int $kelasId, string $filePath): array
    {
        return DB::transaction(function () use ($kelasId, $filePath) {
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            $data = $this->parseFile($filePath);

            foreach ($data as $index => $row) {
                try {
                    $nim = $row['nim'] ?? null;
                    $nilaiAngka = $row['nilai_angka'] ?? null;
                    $nilaiHuruf = $row['nilai_huruf'] ?? null;
                    if (!$nim || $nilaiAngka === null) {
                        $results['failed']++;
                        $results['errors'][] = "Baris " . ($index + 2) . ": NIM atau nilai tidak valid";
                        continue;
                    }

                    // Find mahasiswa by NIM
                    $mahasiswa = MahasiswaModel::where('nim', $nim)->first();
                    if (!$mahasiswa) {
                        $results['failed']++;
                        $results['errors'][] = "Baris " . ($index + 2) . ": NIM {$nim} tidak ditemukan";
                        continue;
                    }

                    // Determine grade letter from score
                    if (!$nilaiHuruf) {
                        $nilaiHuruf = $this->calculateGrade($nilaiAngka);
                    }

                    $bobot = $this->calculateBobot($nilaiHuruf);
                    $isLulus = $nilaiAngka >= 40; // Minimum passing score

                    $this->nilaiService->upsertFinal(
                        $this->buildPayload($kelasId, $mahasiswa->mahasiswa_id, (float) $nilaiAngka, $nilaiHuruf, $bobot, $isLulus),
                        Nilai::SOURCE_IMPORT_MANUAL,
                        'perkuliahan-import'
                    );

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                    Log::error('Import nilai error', ['row' => $index, 'error' => $e->getMessage()]);
                }
            }

            logActivity('Import nilai untuk kelas #' . $kelasId . ': ' . $results['success'] . ' berhasil, ' . $results['failed'] . ' gagal');

            return $results;
        });
    }

    /**
     * Import single nilai via form (manual input per mahasiswa)
     */
    public function importSingle(array $data): void
    {
        $mahasiswa = MahasiswaModel::where('nim', $data['nim'])->firstOrFail();

        $nilaiHuruf = $data['nilai_huruf'] ?? $this->calculateGrade($data['nilai_angka']);
        $bobot = $this->calculateBobot($nilaiHuruf);

        $this->nilaiService->upsertFinal(
            $this->buildPayload(
                (int) $data['kelas_id'],
                $mahasiswa->mahasiswa_id,
                (float) $data['nilai_angka'],
                $nilaiHuruf,
                $bobot,
                ($data['nilai_angka'] ?? 0) >= 40
            ),
            Nilai::SOURCE_IMPORT_MANUAL,
            'perkuliahan-manual'
        );
    }

    public function updateNilai(int $id, array $data): Nilai
    {
        $nilaiHuruf = ($data['nilai_huruf'] ?? null) ?: $this->calculateGrade((float) $data['nilai_angka']);
        $bobot = $this->calculateBobot($nilaiHuruf);

        $nilai = Nilai::findOrFail($id);
        $nilai->update([
            'nilai_angka' => $data['nilai_angka'],
            'nilai_huruf' => $nilaiHuruf,
            'bobot' => $bobot,
            'is_lulus' => ((float) $data['nilai_angka']) >= 40,
        ]);

        return $nilai;
    }

    public function deleteNilai(int $id): bool
    {
        return (bool) Nilai::findOrFail($id)->delete();
    }

    protected function buildPayload(int $kelasId, int $mahasiswaId, float $nilaiAngka, string $nilaiHuruf, float $bobot, bool $isLulus): array
    {
        $kelas = KelasKuliah::with('penawaran.kurikulumMataKuliah.mataKuliah')->findOrFail($kelasId);
        $penawaran = $kelas->penawaran;
        $mataKuliah = $penawaran?->kurikulumMataKuliah?->mataKuliah;

        if (! $penawaran || ! $mataKuliah) {
            throw new \RuntimeException('Kelas belum memiliki penawaran atau mata kuliah yang valid.');
        }

        return [
            'tenant_id' => sys_tenant_id(),
            'mahasiswa_id' => $mahasiswaId,
            'kelas_id' => $kelasId,
            'mata_kuliah_id' => $mataKuliah->mata_kuliah_id,
            'periode_akademik_id' => $penawaran->periode_akademik_id,
            'nilai_angka' => $nilaiAngka,
            'nilai_huruf' => $nilaiHuruf,
            'bobot' => $bobot,
            'sks' => $mataKuliah->sks,
            'is_lulus' => $isLulus,
        ];
    }

    /**
     * Parse uploaded CSV/Excel file
     */
    private function parseFile(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array(strtolower($extension), ['csv'])) {
            return $this->parseCsv($filePath);
        }

        // For Excel files, use Maatwebsite\Excel
        return $this->parseExcel($filePath);
    }

    private function parseCsv(string $filePath): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $rows;
    }

    private function parseExcel(string $filePath): array
    {
        try {
            $collection = Excel::toCollection(null, $filePath)->first();
            if (!$collection || $collection->count() < 2) return [];

            $header = $collection->first()->toArray();
            $rows = [];
            $collection->slice(1)->each(function ($row) use (&$rows, $header) {
                $rows[] = array_combine($header, $row->toArray());
            });
            return $rows;
        } catch (\Exception $e) {
            Log::error('Excel parse error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate letter grade from numeric score
     */
    private function calculateGrade(float $score): string
    {
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 40) return 'D';
        return 'E';
    }

    /**
     * Calculate bobot (weight) from letter grade
     */
    private function calculateBobot(string $grade): float
    {
        return match(strtoupper($grade)) {
            'A' => 4.0,
            'A-' => 3.7,
            'B+' => 3.3,
            'B' => 3.0,
            'B-' => 2.7,
            'C+' => 2.3,
            'C' => 2.0,
            'C-' => 1.7,
            'D' => 1.0,
            default => 0.0,
        };
    }
}
