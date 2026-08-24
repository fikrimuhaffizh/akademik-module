<?php
namespace Modules\Akademik\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Akademik\Models\JadwalKuliah;
use Modules\Akademik\Models\KalenderAkademik;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\KrsDetail;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Services\MahasiswaService;
use Modules\Akademik\Services\NilaiService;
use Modules\Kurikulum\Models\PrasyaratMataKuliah;

class KrsService
{
    public function __construct(
        protected BatasSksService $batasSksService,
        protected MahasiswaService $mahasiswaService,
        protected NilaiService $nilaiService,
    ) {}

    public function getBaseQuery(): Builder
    {
        return Krs::query()->with(['periodeAkademik', 'mahasiswa'])->select(['krs_id', 'tenant_id', 'mahasiswa_id', 'periode_akademik_id', 'status', 'total_sks', 'disetujui_oleh', 'tgl_disetujui', 'catatan', 'created_at', 'updated_at'])->orderByDesc('created_at');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['periode_akademik_id'])) {
            $query->where('periode_akademik_id', decryptIdIfEncrypted($filters['periode_akademik_id']));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    public function getAll(): Collection
    {
        return Krs::with('periodeAkademik')->get();
    }

    public function findById(string | int $id): Krs
    {
        return Krs::with(['periodeAkademik', 'details'])->findOrFail(decryptIdIfEncrypted($id));
    }

    public function getKelasOptions(): Collection
    {
        return KelasKuliah::with('penawaran.kurikulumMataKuliah.mataKuliah', 'penawaran.periodeAkademik')
            ->where('is_aktif', true)
            ->orderBy('nama_kelas')
            ->get();
    }

    public function create(array $data): Krs
    {
        return DB::transaction(function () use ($data) {
            $kelasIds = $data['kelas_ids'] ?? [];
            unset($data['kelas_ids']);

            $this->validateKrs($data['mahasiswa_id'], $data['periode_akademik_id'], $kelasIds);
            $data['total_sks'] = $this->calculateTotalSks($kelasIds);
            $entity            = Krs::create($data);
            $this->syncDetails($entity, $kelasIds);
            logActivity('perkuliahan', 'Menambah KRS', $entity);

            return $entity;
        });
    }

    public function update(string | int $id, array $data): Krs
    {
        return DB::transaction(function () use ($id, $data) {
            $entity   = $this->findById($id);
            $kelasIds = $data['kelas_ids'] ?? $entity->details()->where('status', 'aktif')->pluck('kelas_id')->all();
            unset($data['kelas_ids']);

            $this->validateKrs($data['mahasiswa_id'] ?? $entity->mahasiswa_id, $data['periode_akademik_id'] ?? $entity->periode_akademik_id, $kelasIds, $entity->krs_id);
            $data['total_sks'] = $this->calculateTotalSks($kelasIds);
            $entity->update($data);
            $this->syncDetails($entity, $kelasIds);
            logActivity('perkuliahan', 'Memperbarui KRS', $entity);

            return $entity;
        });
    }

    public function delete(string | int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', 'Menghapus KRS', null);

            return $entity->delete();
        });
    }

    /**
     * DataTables source for the admin KRS list (all KRS).
     */
    public function getAdminQuery(): Builder
    {
        return Krs::with(['mahasiswa.prodi', 'periodeAkademik'])
            ->select('akd_krs.*');
    }

    /**
     * Find latest KRS for a mahasiswa in a given periode.
     */
    public function findByMahasiswaPeriode(int $mahasiswaId, int $periodeAkademikId): ?Krs
    {
        return Krs::where('mahasiswa_id', $mahasiswaId)
            ->where('periode_akademik_id', $periodeAkademikId)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Riwayat KRS (periode + status) untuk tampilan mahasiswa.
     */
    public function getRiwayatByMahasiswa(Mahasiswa $mahasiswa): array
    {
        return Krs::with('periodeAkademik')
            ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Krs $krs) => [
                'krs_id'    => $krs->encrypted_krs_id,
                'periode'   => $krs->periodeAkademik?->nama ?? '-',
                'status'    => $krs->status,
                'total_sks' => $krs->total_sks,
                'is_aktif'  => $krs->periodeAkademik?->is_aktif ?? false,
            ])->all();
    }

    /**
     * Kelas kuliah (aktif, disetujui) yang diambil mahasiswa di periode EDOM.
     */
    public function getKelasIdsByMahasiswaPeriode(int $mahasiswaId, int $periodeAkademikId): array
    {
        return KrsDetail::query()
            ->where('status', 'aktif')
            ->whereHas('krs', fn ($q) => $q->where('mahasiswa_id', $mahasiswaId)
                ->where('periode_akademik_id', $periodeAkademikId)
                ->where('status', 'disetujui'))
            ->pluck('kelas_id')
            ->unique()
            ->values()
            ->all();
    }

    protected function validateKrs(int $mahasiswaId, int $periodeAkademikId, array $kelasIds, ?int $ignoreKrsId = null): void
    {
        $mahasiswa = Mahasiswa::findOrFail($mahasiswaId);

        if ($this->mahasiswaService->isCekal($mahasiswaId)) {
            if ($this->mahasiswaService->isCutiAktif($mahasiswaId, $periodeAkademikId)) {
                $this->failValidation('Mahasiswa sedang cuti dan tidak dapat mengisi KRS.');
            }
            $this->failValidation('Mahasiswa sedang dicekal dan tidak dapat mengisi KRS.');
        }

        $angkatan       = $mahasiswa->angkatan;
        $prodiOrgunitId = $mahasiswa->prodi_id;

        if (! $this->isKrsOpen($periodeAkademikId, $prodiOrgunitId, $angkatan)) {
            $this->failValidation('Periode pengisian KRS belum dibuka untuk prodi/angkatan Anda.');
        }

        $kelasIds = array_values(array_unique(array_map('intval', $kelasIds)));
        $kelas    = KelasKuliah::with('penawaran.kurikulumMataKuliah.mataKuliah', 'jadwalKuliahs')->whereIn('kelas_id', $kelasIds)->get();

        if ($kelas->count() !== count($kelasIds)) {
            $this->failValidation('Ada kelas kuliah yang tidak valid.');
        }

        $invalidPeriode = $kelas->contains(fn($item) => (int) $item->penawaran?->periode_akademik_id !== $periodeAkademikId);
        if ($invalidPeriode) {
            $this->failValidation('Semua kelas harus berasal dari periode akademik yang sama dengan KRS.');
        }

        $duplicateMk = $kelas->pluck('penawaran.kurikulum_mata_kuliah_id')->filter()->duplicates()->isNotEmpty();
        if ($duplicateMk) {
            $this->failValidation('Satu mata kuliah hanya boleh diambil satu kelas.');
        }

        $duplicateEnrollment = KrsDetail::query()
            ->whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->whereHas('krs', function (Builder $query) use ($mahasiswaId, $periodeAkademikId, $ignoreKrsId) {
                $query->where('mahasiswa_id', $mahasiswaId)
                    ->where('periode_akademik_id', $periodeAkademikId);

                if ($ignoreKrsId) {
                    $query->where('krs_id', '!=', $ignoreKrsId);
                }
            })
            ->exists();

        if ($duplicateEnrollment) {
            $this->failValidation('Mahasiswa sudah mengambil salah satu kelas tersebut.');
        }

        // === Kuota: tiap kelas gak boleh lewat kapasitas (terisi+1 > kapasitas) ===
        $terisi = DB::table('akd_krs_detail as d')
            ->join('akd_krs as k', 'd.krs_id', '=', 'k.krs_id')
            ->whereIn('d.kelas_id', $kelasIds)
            ->where('d.status', 'aktif')
            ->whereNull('d.deleted_at')
            ->whereNull('k.deleted_at')
            ->select('d.kelas_id', DB::raw('COUNT(*) as terisi'))
            ->groupBy('d.kelas_id')
            ->pluck('terisi', 'kelas_id');

        foreach ($kelas as $item) {
            $sudah     = (int) ($terisi[$item->kelas_id] ?? 0);
            $kapasitas = (int) ($item->kapasitas ?? 0);
            if ($kapasitas > 0 && $sudah + 1 > $kapasitas) {
                $namaMk = $item->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? ('Kelas ' . $item->nama_kelas);
                $this->failValidation("Kuota kelas {$namaMk} penuh ({$sudah}/{$kapasitas}).");
            }
        }

        $totalSks = $kelas->sum(fn($item) => (int) ($item->penawaran?->kurikulumMataKuliah?->mataKuliah?->sks ?? 0));
        $batasSks = $this->batasSksService->getBatasByIpk($this->nilaiService->hitungIpk($mahasiswaId), $periodeAkademikId);

        if ($totalSks > $batasSks) {
            $this->failValidation("Total SKS {$totalSks} melebihi batas {$batasSks} SKS.");
        }

        // === NEW: Prasyarat validation ===
        $this->validatePrasyarat($mahasiswaId, $kelas);

        // === NEW: Jadwal overlap validation ===
        $this->validateJadwalOverlap($kelas, $mahasiswaId, $ignoreKrsId);
    }

    /**
     * Check that all prerequisite MK are passed before allowing KRS.
     */
    protected function validatePrasyarat(int $mahasiswaId, SupportCollection $kelas): void
    {
        $selectedMkIds = $kelas
            ->pluck('penawaran.kurikulum_mata_kuliah_id')
            ->filter()
            ->unique()
            ->values();

        // Map selected kur_mk_id -> mata_kuliah_id (prasyarat pakai mata_kuliah_id)
        $kurMkToMk = \Modules\Kurikulum\Models\KurikulumMataKuliah::whereIn('kur_mk_id', $selectedMkIds)
            ->pluck('mata_kuliah_id', 'kur_mk_id');
        $selectedMataKuliahIds = $kurMkToMk->values()->unique()->values();

        // Load prasyarat for all selected MK at once
        $prasyaratList = PrasyaratMataKuliah::with('prasyaratMataKuliah')
            ->whereIn('mata_kuliah_id', $selectedMataKuliahIds)
            ->get();

        foreach ($prasyaratList as $prasyarat) {
            $namaMk        = $prasyarat->mataKuliah?->nama ?? 'MK ID ' . $prasyarat->mata_kuliah_id;
            $namaPrasyarat = $prasyarat->prasyaratMataKuliah?->nama ?? 'MK ID ' . $prasyarat->prasyarat_mk_id;

            if (! $this->nilaiService->hasLulusPrasyarat($mahasiswaId, $prasyarat->prasyarat_mk_id)) {
                $this->failValidation(
                    "Prasyarat tidak terpenuhi: {$namaMk} membutuhkan lulus {$namaPrasyarat}."
                );
            }
        }
    }

    /**
     * Check that selected kelas don't have overlapping schedules with each other
     * or with previously enrolled kelas in the same periode.
     */
    protected function validateJadwalOverlap(SupportCollection $newKelas, int $mahasiswaId, ?int $ignoreKrsId = null): void
    {
        // Gather all existing active kelas for this mahasiswa in this periode
        $existingKelasIds = KrsDetail::where('status', 'aktif')
            ->whereHas('krs', function ($q) use ($mahasiswaId, $ignoreKrsId) {
                $q->where('mahasiswa_id', $mahasiswaId);
                if ($ignoreKrsId) {
                    $q->where('krs_id', '!=', $ignoreKrsId);
                }
            })
            ->pluck('kelas_id');

        // Load all jadwal for new + existing kelas
        $allKelasIds = $newKelas->pluck('kelas_id')->concat($existingKelasIds)->unique()->values();

        $allJadwal = JadwalKuliah::whereIn('kelas_id', $allKelasIds)->get();

        // Group by kelas_id
        $jadwalPerKelas = $allJadwal->groupBy('kelas_id');

        // Check overlap: new vs existing
        $existingJadwal = $allJadwal->whereIn('kelas_id', $existingKelasIds);

        foreach ($newKelas as $kelas) {
            $newJadwals = $jadwalPerKelas[$kelas->kelas_id] ?? collect();

            foreach ($newJadwals as $jadwal) {
                // Skip online classes (no room conflict possible)
                if ($jadwal->isOnline()) {
                    continue;
                }

                // Check against existing enrollments
                $conflict = $existingJadwal->first(function ($existing) use ($jadwal) {
                    return $existing->hari === $jadwal->hari
                    && $existing->jam_mulai < $jadwal->jam_selesai
                    && $existing->jam_selesai > $jadwal->jam_mulai;
                });

                if ($conflict) {
                    $mkBaru     = $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? 'MK';
                    $mkExisting = $conflict->kelas?->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? 'MK';
                    $this->failValidation(
                        "Bentrok jadwal: {$mkBaru} ({$jadwal->hari} {$jadwal->jam_mulai}-{$jadwal->jam_selesai}) beririsan dengan {$mkExisting}."
                    );
                }
            }
        }

        // Check overlap within new selections
        $newJadwalFlat = collect();
        foreach ($newKelas as $kelas) {
            $jadwals = $jadwalPerKelas[$kelas->kelas_id] ?? collect();
            foreach ($jadwals as $jadwal) {
                if ($jadwal->isOnline()) {
                    continue;
                }
                $mkBaru = $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? 'MK';

                foreach ($newJadwalFlat as $existing) {
                    if ($existing['jadwal']->hari === $jadwal->hari
                        && $existing['jadwal']->jam_mulai < $jadwal->jam_selesai
                        && $existing['jadwal']->jam_selesai > $jadwal->jam_mulai) {
                        $this->failValidation(
                            "Bentrok jadwal internal: {$mkBaru} ({$jadwal->hari} {$jadwal->jam_mulai}-{$jadwal->jam_selesai}) beririsan dengan {$existing['mk']}."
                        );
                    }
                }

                $newJadwalFlat->push(['jadwal' => $jadwal, 'mk' => $mkBaru]);
            }
        }
    }

    protected function calculateTotalSks(array $kelasIds): int
    {
        return (int) KelasKuliah::with('penawaran.kurikulumMataKuliah.mataKuliah')
            ->whereIn('kelas_id', array_values(array_unique(array_map('intval', $kelasIds))))
            ->get()
            ->sum(fn($item) => (int) ($item->penawaran?->kurikulumMataKuliah?->mataKuliah?->sks ?? 0));
    }

    protected function syncDetails(Krs $krs, array $kelasIds): void
    {
        $kelasIds = array_values(array_unique(array_map('intval', $kelasIds)));
        $krs->details()->whereNotIn('kelas_id', $kelasIds)->update(['status' => 'dibatalkan']);

        foreach ($kelasIds as $kelasId) {
            $krs->details()->updateOrCreate(
                ['kelas_id' => $kelasId],
                ['tenant_id' => sys_tenant_id(), 'status' => 'aktif']
            );
        }
    }

    protected function failValidation(string $message): never
    {
        throw ValidationException::withMessages(['krs' => $message]);
    }

    /**
     * KRS terbuka bila ada event kalender akademik jenis='krs' untuk periode tsb,
     * tanggal sekarang dalam range, dan (bila metadata diisi) prodi + angkatan
     * mahasiswa termasuk dalam daftar. metadata null/kosong = semua.
     */
    protected function isKrsOpen(int $periodeAkademikId, int $prodiOrgunitId, ?int $angkatan): bool
    {
        $event = KalenderAkademik::where('periode_akademik_id', $periodeAkademikId)
            ->where('jenis', 'krs')
            ->whereDate('tgl_mulai', '<=', now())
            ->whereDate('tgl_selesai', '>=', now())
            ->get();

        if ($event->isEmpty()) {
            return false;
        }

        return $event->contains(function ($e) use ($prodiOrgunitId, $angkatan) {
            $meta       = $e->metadata ?? [];
            $prodiOk    = empty($meta['prodi_id']) || in_array($prodiOrgunitId, (array) $meta['prodi_id'], true);
            $angkatanOk = empty($meta['angkatan']) || $angkatan === null
            || in_array($angkatan, (array) $meta['angkatan'], false);
            return $prodiOk && $angkatanOk;
        });
    }

    /**
     * Guard: KRS hanya bisa diisi bila event kalender akademik jenis='krs'
     * untuk periode tsb aktif (lihat isKrsOpen).
     */
    protected function assertKrsOpen(int $mahasiswaId, int $periodeAkademikId): void
    {
        $mahasiswa      = Mahasiswa::findOrFail($mahasiswaId);
        $prodiOrgunitId = $mahasiswa->prodi_id;

        if (! $this->isKrsOpen($periodeAkademikId, $prodiOrgunitId, $mahasiswa->angkatan)) {
            $this->failValidation('Pengisian KRS belum dibuka untuk prodi/angkatan Anda.');
        }
    }

    /**
     * Kelas kuliah yang boleh diambil mahasiswa sesuai penawaran MK
     * (per prodi & periode akademik aktif), lengkap dengan status ambil
     * (aktif/dibatalkan) dari KRS terakhir mahasiswa di periode tsb.
     */
    public function getMkKelasPenawaranMhs(int $mahasiswaId, int $periodeAkademikId): SupportCollection
    {
        $mahasiswa      = Mahasiswa::findOrFail($mahasiswaId);
        $prodiOrgunitId = $mahasiswa->prodi_id;

        $krs = Krs::where('mahasiswa_id', $mahasiswaId)
            ->where('periode_akademik_id', $periodeAkademikId)
            ->orderByDesc('created_at')
            ->first();

        $ambilKelasIds = $krs
            ? $krs->details()->where('status', 'aktif')->pluck('kelas_id')->all()
            : [];

        return KelasKuliah::with([
            'penawaran.kurikulumMataKuliah.mataKuliah',
            'penawaran.periodeAkademik',
            'jadwalKuliahs',
            'pembebananDosens.pegawai',
        ])
            ->where('is_aktif', true)
            ->whereHas('penawaran', fn(Builder $q) => $q
                    ->where('periode_akademik_id', $periodeAkademikId)
                    ->where('prodi_id', $prodiOrgunitId)
                    ->where('kurikulum_kode', $mahasiswa->kurikulum_kode))
            ->withCount(['krsDetails as terisi' => fn(Builder $q) => $q->where('status', 'aktif')])
            ->orderBy('nama_kelas')
            ->get()
            ->map(function (KelasKuliah $kelas) use ($ambilKelasIds) {
                $mk    = $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah;
                $dosen = $kelas->pembebananDosens
                    ->filter(fn($p) => ($p->peran ?? 'dosen') === 'dosen')
                    ->first()?->pegawai;

                return [
                    'kelas_id'           => $kelas->kelas_id,
                    'encrypted_kelas_id' => encryptId($kelas->kelas_id),
                    'nama_kelas'         => $kelas->nama_kelas,
                    'kode_mk'            => $mk?->kode,
                    'nama_mk'            => $mk?->nama,
                    'sks'                => (int) ($mk?->sks ?? 0),
                    'kapasitas'          => (int) ($kelas->kapasitas ?? 0),
                    'terisi'             => (int) ($kelas->terisi ?? 0),
                    'sisa'               => max(0, (int) ($kelas->kapasitas ?? 0) - (int) ($kelas->terisi ?? 0)),
                    'ruang'              => $kelas->jadwalKuliahs->filter(fn($j) => $j->ruang)->first()?->ruang?->nama ?? $kelas->jadwalKuliahs->first()?->ruang?->nama ?? '-',
                    'dosen'              => $dosen?->nama,
                    'jadwal'             => $kelas->jadwalKuliahs->map(fn($j) => [
                        'hari'        => $j->hari,
                        'jam_mulai'   => $j->jam_mulai,
                        'jam_selesai' => $j->jam_selesai,
                    ]),
                    'sudah_ambil'        => in_array($kelas->kelas_id, $ambilKelasIds, true),
                ];
            });
    }

    /**
     * Toggle satu kelas dalam KRS mahasiswa (draft). Membuat KRS baru bila
     * belum ada, lalu jalankan validasi penuh sebelum simpan.
     * Mengembalikan entity KRS terbaru.
     */
    public function toggleKelas(int $mahasiswaId, int $periodeAkademikId, int $kelasId, bool $ambil): Krs
    {
        return DB::transaction(function () use ($mahasiswaId, $periodeAkademikId, $kelasId, $ambil) {
            $krs = Krs::where('mahasiswa_id', $mahasiswaId)
                ->where('periode_akademik_id', $periodeAkademikId)
                ->orderByDesc('created_at')
                ->first();

            if (! $krs) {
                $krs = Krs::create([
                    'tenant_id'           => sys_tenant_id(),
                    'mahasiswa_id'        => $mahasiswaId,
                    'periode_akademik_id' => $periodeAkademikId,
                    'status'              => 'draft',
                    'total_sks'           => 0,
                ]);
            }

            $this->assertKrsOpen($mahasiswaId, $periodeAkademikId);

            $kelasIds = $krs->details()->where('status', 'aktif')->pluck('kelas_id')->all();

            if ($ambil) {
                if (! in_array($kelasId, $kelasIds, true)) {
                    $kelasIds[] = $kelasId;
                }
            } else {
                $kelasIds = array_values(array_diff($kelasIds, [$kelasId]));
            }

            $this->validateKrs($mahasiswaId, $periodeAkademikId, $kelasIds, $krs->krs_id);
            $krs->update(['total_sks' => $this->calculateTotalSks($kelasIds)]);
            $this->syncDetails($krs, $kelasIds);
            logActivity('perkuliahan', $ambil ? 'Menambah kelas ke KRS' : 'Menghapus kelas dari KRS', $krs);

            return $krs->fresh();
        });
    }

    /**
     * Ajukan KRS (draft -> diajukan).
     */
    public function ajukan(int $krsId): Krs
    {
        return DB::transaction(function () use ($krsId) {
            $krs = $this->findById($krsId);

            if ($krs->status === 'diajukan' || $krs->status === 'disetujui') {
                $this->failValidation('KRS sudah diajukan/selesai.');
            }

            $kelasIds = $krs->details()->where('status', 'aktif')->pluck('kelas_id')->all();
            $this->validateKrs($krs->mahasiswa_id, $krs->periode_akademik_id, $kelasIds, $krs->krs_id);

            $krs->update(['status' => 'diajukan']);
            logActivity('perkuliahan', 'Mengajukan KRS', $krs);

            return $krs->fresh();
        });
    }

    /**
     * Monitoring pengisian KRS per angkatan & prodi untuk periode akademik.
     * Menghitung jumlah mahasiswa per status KRS (belum/terisi/diajukan/
     * disetujui) serta total SKS terisi.
     */
    public function getMonitoring(int $periodeAkademikId): SupportCollection
    {
        $mahasiswas = Mahasiswa::with([
            'krs' => fn($q) => $q->where('periode_akademik_id', $periodeAkademikId),
        ])
            ->where('status', 'aktif')
            ->orderBy('angkatan')
            ->orderBy('prodi_id')
            ->get();

        $grouped = $mahasiswas->groupBy(
            fn($m) => $m->angkatan . '|' . $m->prodi_id
        );

        $prodiIds  = $grouped->keys()->map(fn($k) => (int) explode('|', $k)[1])->unique()->values();
        $prodiNama = \Modules\HrCore\Models\StrukturOrganisasi::whereIn('orgunit_id', $prodiIds)
            ->pluck('name', 'orgunit_id');

        $result = collect();
        foreach ($grouped as $key => $items) {
            [$angkatan, $prodiId] = explode('|', $key);

            $belum     = 0;
            $terisi    = 0;
            $diajukan  = 0;
            $disetujui = 0;
            $totalSks  = 0;

            foreach ($items as $m) {
                $krs = $m->krs->first();
                if (! $krs) {
                    $belum++;
                    continue;
                }
                $totalSks += (int) $krs->total_sks;
                if ($krs->status === 'disetujui') {
                    $disetujui++;
                } elseif ($krs->status === 'diajukan') {
                    $diajukan++;
                } else {
                    $terisi++;
                }
            }

            $result->push([
                'angkatan'   => $angkatan,
                'prodi_id'   => (int) $prodiId,
                'prodi_nama' => $prodiNama[$prodiId] ?? '-',
                'total'      => $items->count(),
                'belum'      => $belum,
                'terisi'     => $terisi,
                'diajukan'   => $diajukan,
                'disetujui'  => $disetujui,
                'total_sks'  => $totalSks,
            ]);
        }

        return $result;
    }
}
