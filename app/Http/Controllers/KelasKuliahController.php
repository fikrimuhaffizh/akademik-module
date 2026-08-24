<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\KelasKuliahRequest;
use Modules\Akademik\Models\KelasKuliah;
use Modules\HrCore\Services\PegawaiService;
use Modules\Akademik\Services\JadwalKuliahService;
use Modules\Akademik\Services\KelasKuliahService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class KelasKuliahController extends Controller
{
    public function __construct(
        protected PegawaiService $pegawaiService,
        protected JadwalKuliahService $jadwalService,
        protected KelasKuliahService $kelasKuliahService,
    ) {
        $this->middleware('permission:akd.kelas-akd.view')->only(['index', 'data']);
        $this->middleware('permission:akd.kelas-akd.create')->only(['create', 'store']);
        $this->middleware('permission:akd.kelas-akd.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.kelas-akd.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.kelas-akd.index', [
            'total' => $this->kelasKuliahService->getCount(),
        ]);
    }

    public function data(Request $request)
    {
        $query = $this->kelasKuliahService->getFilteredQuery(parseFilters($request, []));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('penawaran', fn ($r) => $r->penawaran?->kurikulumMataKuliah?->mataKuliah ? ($r->penawaran->kurikulumMataKuliah->mataKuliah->kode . ' - ' . $r->penawaran->kurikulumMataKuliah->mataKuliah->nama) : '-')
            ->editColumn('nama_kelas', fn ($r) => $r->nama_kelas ?? ($r->refKelas?->label ?? '-'))
            ->editColumn('sistem_kuliah', fn ($r) => ucfirst($r->sistem_kuliah))
            ->addColumn('dosen', fn ($r) => $r->pembebananDosens->where('peran', 'pengampu')->count() . ' pengampu')
            ->addColumn('jadwal', fn ($r) => $r->jadwalKuliahs->count() . ' jadwal')
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? '<span class="status status-success">Aktif</span>' : '<span class="status status-danger">Non Aktif</span>')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', [
                'editUrl' => route('akd.kelas-akd.edit', $r->encrypted_kelas_id),
                'editModal' => true,
                'editModalSize' => 'modal-xl',
                'deleteUrl' => route('akd.kelas-akd.destroy', $r->encrypted_kelas_id),
            ])->render())
            ->rawColumns(['is_aktif', 'action'])
            ->make(true);
    }

    public function create()
    {
        return view('akademik::pages.kelas-akd.create-edit-ajax', $this->formData());
    }

    public function store(KelasKuliahRequest $request)
    {
        $data = $request->validated();
        $pembebanan = collect($data['pembebanan'] ?? [])->map(function ($p) {
            return [
                'pegawai_id' => decryptIdIfEncrypted($p['pegawai_id']),
                'peran' => $p['peran'],
            ];
        })->all();
        $jadwals = $data['jadwals'] ?? [];
        unset($data['pembebanan'], $data['jadwals']);

        $this->assertNoScheduleConflict(null, $pembebanan, $jadwals);

        $this->kelasKuliahService->createWithRelations($data, $pembebanan, $jadwals);

        return jsonSuccess('Kelas kuliah berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(KelasKuliah $kelas_kuliah)
    {
        $kelas_kuliah->load(['pembebananDosens', 'jadwalKuliahs']);
        $data = $this->formData();
        $data['item'] = $kelas_kuliah;

        return view('akademik::pages.kelas-akd.create-edit-ajax', $data);
    }

    public function update(KelasKuliahRequest $request, KelasKuliah $kelas_kuliah)
    {
        $data = $request->validated();
        $pembebanan = collect($data['pembebanan'] ?? [])->map(function ($p) {
            return [
                'pegawai_id' => decryptIdIfEncrypted($p['pegawai_id']),
                'peran' => $p['peran'],
            ];
        })->all();
        $jadwals = $data['jadwals'] ?? [];
        unset($data['pembebanan'], $data['jadwals']);

        $this->assertNoScheduleConflict($kelas_kuliah->kelas_id, $pembebanan, $jadwals);

        $this->kelasKuliahService->updateWithRelations($kelas_kuliah, $data, $pembebanan, $jadwals);

        return jsonSuccess('Kelas kuliah berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(KelasKuliah $kelas_kuliah)
    {
        $this->kelasKuliahService->delete($kelas_kuliah);

        return jsonSuccess('Kelas kuliah berhasil dihapus.', null, ['reload' => true]);
    }

    /**
     * Cek bentrok jadwal mingguan sebelum simpan (create/update).
     * - Antar jadwal dalam 1 submit (kelas yang sama): hari + jam irisan
     *   dengan ruang offline sama ATAU dosen pengampu sama → bentrok.
     * - Vs jadwal kelas LAIN: pakai JadwalKuliahService::findConflicts
     *   (ruang sama / dosen sama, hari + jam irisan, online diabaikan).
     * $ignoreKelasId untuk update (abaikan kelas ini sendiri).
     *
     * @throws ValidationException
     */
    protected function assertNoScheduleConflict(?int $ignoreKelasId, array $pembebanan, array $jadwals): void
    {
        if (empty($jadwals)) {
            return;
        }

        $pegawaiIds = collect($pembebanan)->pluck('pegawai_id')->all();
        $errors = [];

        foreach ($jadwals as $i => $j) {
            if (empty($j['hari']) || empty($j['jam_mulai']) || empty($j['jam_selesai'])) {
                continue;
            }
            $isOnline = in_array($j['metode_pembelajaran'] ?? 'offline', ['online', 'hybrid']);
            $ruangId = ! empty($j['ruang_id']) ? (int) $j['ruang_id'] : null;

            // 1) Bentrok vs jadwal kelas LAIN (DB)
            $dbConflict = $this->jadwalService->findConflicts(
                array_merge($j, ['kelas_id' => $ignoreKelasId ?? 0]),
                null
            );
            if (! empty($dbConflict)) {
                $errors["jadwals.{$i}.ruang_id"] = reset($dbConflict);
                continue;
            }

            // 2) Bentrok internal: jadwal sebelumnya dalam submit yg sama
            for ($k = 0; $k < $i; $k++) {
                $prev = $jadwals[$k];
                if (empty($prev['hari']) || $prev['hari'] !== $j['hari']) {
                    continue;
                }
                $prevMulai = $prev['jam_mulai'];
                $prevSelesai = $prev['jam_selesai'];
                $iris = ($j['jam_mulai'] < $prevSelesai) && ($j['jam_selesai'] > $prevMulai);
                if (! $iris) {
                    continue;
                }
                $prevOnline = in_array($prev['metode_pembelajaran'] ?? 'offline', ['online', 'hybrid']);
                $prevRuang = ! empty($prev['ruang_id']) ? (int) $prev['ruang_id'] : null;

                $ruangBentrok = ! $isOnline && ! $prevOnline && $ruangId && $prevRuang && $ruangId === $prevRuang;
                $dosenBentrok = ! empty($pegawaiIds)
                    && $this->kelasKuliahService->existsDosenConflict($ignoreKelasId, $pegawaiIds);

                if ($ruangBentrok) {
                    $errors["jadwals.{$i}.ruang_id"] = 'Ruang ini bentrok dengan jadwal ke-' . ($k + 1)
                        . ' (' . ucfirst($j['hari']) . ' ' . $prevMulai . '-' . $prevSelesai . ').';
                    break;
                }
                if ($dosenBentrok) {
                    $errors["jadwals.{$i}.jam_mulai"] = 'Dosen pengampu bentrok dengan jadwal ke-' . ($k + 1)
                        . ' (' . ucfirst($j['hari']) . ' ' . $prevMulai . '-' . $prevSelesai . ').';
                    break;
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function formData(): array
    {
        return array_merge($this->kelasKuliahService->getFormData(), [
            'peranOptions' => [
                'pengampu' => 'Pengampu',
                'asisten' => 'Asisten',
                'koordinator' => 'Koordinator',
            ],
            'hariOptions' => [
                'senin' => 'Senin',
                'selasa' => 'Selasa',
                'rabu' => 'Rabu',
                'kamis' => 'Kamis',
                'jumat' => 'Jumat',
                'sabtu' => 'Sabtu',
                'minggu' => 'Minggu',
            ],
            'jenisPertemuanOptions' => [
                'teori' => 'Teori',
                'praktikum' => 'Praktikum',
            ],
        ]);
    }
}
