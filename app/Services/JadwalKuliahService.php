<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\JadwalKuliah;
use Modules\Akademik\Models\RuangKuliah;
use Modules\Akademik\Models\PembebananDosen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JadwalKuliahService
{
    public function getBaseQuery(): Builder
    {
        return JadwalKuliah::query()
            ->with(['kelas.penawaranMataKuliah', 'ruang'])
            ->select([
                'jadwal_id',
                'tenant_id',
                'kelas_id',
                'ruang_id',
                'hari',
                'jam_mulai',
                'jam_selesai',
                'jenis_pertemuan',
                'created_at',
                'updated_at',
            ])
            ->orderBy('hari')
            ->orderBy('jam_mulai');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();

        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', decryptIdIfEncrypted($filters['kelas_id']));
        }

        if (! empty($filters['hari'])) {
            $query->where('hari', $filters['hari']);
        }

        return $query;
    }

    public function getAll(): Collection
    {
        return JadwalKuliah::with(['kelas', 'ruang'])->orderBy('hari')->orderBy('jam_mulai')->get();
    }

    public function findById(string|int $id): JadwalKuliah
    {
        return JadwalKuliah::with(['kelas', 'ruang'])
            ->findOrFail(decryptIdIfEncrypted($id));
    }

    public function create(array $data): JadwalKuliah
    {
        $conflicts = $this->findConflicts($data);
        if (! empty($conflicts)) {
            throw ValidationException::withMessages($conflicts);
        }

        return DB::transaction(function () use ($data) {
            $entity = JadwalKuliah::create($data);

            logActivity('perkuliahan', 'Menambah jadwal kuliah', $entity);

            return $entity;
        });
    }

    public function update(string|int $id, array $data): JadwalKuliah
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);

            // Gabungkan dgn nilai lama utk cek konflik (termasuk kolom yg tdk diubah).
            $merged = array_merge($entity->only([
                'kelas_id', 'ruang_id', 'hari', 'jam_mulai', 'jam_selesai',
            ]), $data);

            $conflicts = $this->findConflicts($merged, $entity->jadwal_id);
            if (! empty($conflicts)) {
                throw ValidationException::withMessages($conflicts);
            }

            $entity->update($data);

            logActivity('perkuliahan', 'Memperbarui jadwal kuliah', $entity);

            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);

            logActivity('perkuliahan', 'Menghapus jadwal kuliah', null);

            return $entity->delete();
        });
    }

    public function getHariOptions(): array
    {
        return [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
        ];
    }

    public function checkOverlap(JadwalKuliah $jadwal): bool
    {
        if ($jadwal->isOnline() || !$jadwal->ruang_id) {
            return false;
        }

        // Bentrok ruang
        $ruangClash = JadwalKuliah::where('jadwal_id', '!=', $jadwal->jadwal_id)
            ->where('hari', $jadwal->hari)
            ->where('ruang_id', $jadwal->ruang_id)
            ->where('jam_mulai', '<', $jadwal->jam_selesai)
            ->where('jam_selesai', '>', $jadwal->jam_mulai)
            ->exists();

        if ($ruangClash) {
            return true;
        }

        // Bentrok dosen (pembebanan) di kelas lain
        $pegawaiIds = PembebananDosen::where('kelas_id', $jadwal->kelas_id)->pluck('pegawai_id');

        if ($pegawaiIds->isNotEmpty()) {
            return JadwalKuliah::where('jadwal_id', '!=', $jadwal->jadwal_id)
                ->where('hari', $jadwal->hari)
                ->where('jam_mulai', '<', $jadwal->jam_selesai)
                ->where('jam_selesai', '>', $jadwal->jam_mulai)
                ->where('kelas_id', '!=', $jadwal->kelas_id)
                ->whereHas('kelas.pembebananDosens', fn ($q) => $q->whereIn('pegawai_id', $pegawaiIds))
                ->exists();
        }

        return false;
    }

    /**
     * Cek konflik jadwal sebelum simpan (create/update).
     *
     * Mengembalikan array keyed by field form yg bentrok:
     *   - 'ruang_id'  : ruang sama, hari sama, waktu irisan (kelas lain)
     *   - 'kelas_id'  : dosen pengampu sama, hari sama, waktu irisan (kelas lain)
     *
     * Online (is_online) diabaikan. Jadwal dengan id = $ignoreJadwalId
     * dikecualikan (utk update record itu sendiri).
     *
     * @param array $input harus berisi kelas_id, hari, jam_mulai, jam_selesai,
     *                       metode_pembelajaran (string), dan ruang_id (nullable)
     */
    public function findConflicts(array $input, ?int $ignoreJadwalId = null): array
    {
        $kelasId = (int) ($input['kelas_id'] ?? 0);
        $hari = $input['hari'] ?? null;
        $mulai = $input['jam_mulai'] ?? null;
        $selesai = $input['jam_selesai'] ?? null;
        $isOnline = in_array($input['metode_pembelajaran'] ?? 'offline', ['online', 'hybrid']);
        $ruangId = ! empty($input['ruang_id']) ? (int) $input['ruang_id'] : null;

        $conflicts = [];

        // 1) Bentrok RUANG (kelas lain, hari & waktu sama)
        if (! $isOnline && $ruangId && $hari && $mulai && $selesai) {
            $ruang = RuangKuliah::find($ruangId);
            $bentrok = JadwalKuliah::with('kelas')
                ->where('jadwal_id', '!=', $ignoreJadwalId ?? 0)
                ->where('ruang_id', $ruangId)
                ->where('hari', $hari)
                ->where('jam_mulai', '<', $selesai)
                ->where('jam_selesai', '>', $mulai)
                ->first();

            if ($bentrok) {
                $conflicts['ruang_id'] = 'Ruang "' . ($ruang?->nama ?? $ruangId)
                    . '" sudah dipakai kelas ' . ($bentrok->kelas?->nama_kelas ?? $bentrok->kelas_id)
                    . ' pada ' . ucfirst($hari) . ' ' . $bentrok->jam_mulai . '-' . $bentrok->jam_selesai . '.';
            }
        }

        // 2) Bentrok DOSEN (pembebanan) — kelas lain, hari & waktu sama
        if (! $isOnline && $hari && $mulai && $selesai) {
            $pegawaiIds = PembebananDosen::where('kelas_id', $kelasId)->pluck('pegawai_id');

            if ($pegawaiIds->isNotEmpty()) {
                $bentrok = JadwalKuliah::with(['kelas', 'kelas.pembebananDosens'])
                    ->where('jadwal_id', '!=', $ignoreJadwalId ?? 0)
                    ->where('hari', $hari)
                    ->where('jam_mulai', '<', $selesai)
                    ->where('jam_selesai', '>', $mulai)
                    ->where('kelas_id', '!=', $kelasId)
                    ->whereHas('kelas.pembebananDosens', fn ($q) => $q->whereIn('pegawai_id', $pegawaiIds))
                    ->first();

                if ($bentrok) {
                    $conflicts['kelas_id'] = 'Dosen pengampu kelas ini bentrok: sudah mengajar kelas '
                        . ($bentrok->kelas?->nama_kelas ?? $bentrok->kelas_id)
                        . ' pada ' . ucfirst($hari) . ' ' . $bentrok->jam_mulai . '-' . $bentrok->jam_selesai . '.';
                }
            }
        }

        return $conflicts;
    }
}
