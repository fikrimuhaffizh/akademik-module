<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MahasiswaRequest extends BaseRequest
{
    public function rules(): array
    {
        $mahasiswaId = $this->route('id') ? decryptIdIfEncrypted($this->route('id')) : null;

        return [
            'nim' => ['nullable', 'string', 'max:20', Rule::unique('akd_mahasiswa', 'nim')->ignore($mahasiswaId, 'mahasiswa_id')->where('tenant_id', tenantId())],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'prodi_id' => ['required', 'integer'],
            'angkatan' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 2)],
            'kurikulum_kode' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['calon', 'aktif', 'cuti', 'non_aktif', 'do', 'undur_diri', 'lulus'])],
            'jenis_masuk' => ['sometimes', Rule::in(['reguler', 'transfer', 'rpl', 'pindahan'])],
            'semester_masuk' => ['sometimes', 'integer', 'min:1', 'max:14'],
            'sks_diakui_awal' => ['sometimes', 'integer', 'min:0', 'max:300'],
            'institusi_asal' => ['nullable', 'string', 'max:255'],
            'prodi_asal' => ['nullable', 'string', 'max:255'],
            'pmb_pendaftar_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'foto' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'json'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nim' => 'NIM',
            'nama' => 'Nama',
            'email' => 'Email',
            'no_hp' => 'No. HP',
            'prodi_id' => 'Program Studi',
            'angkatan' => 'Angkatan',
            'kurikulum_kode' => 'Kode Kurikulum',
            'status' => 'Status',
            'jenis_masuk' => 'Jenis Masuk',
            'semester_masuk' => 'Semester Masuk',
            'sks_diakui_awal' => 'SKS Diakui Awal',
            'institusi_asal' => 'Institusi Asal',
            'prodi_asal' => 'Program Studi Asal',
            'pmb_pendaftar_id' => 'Pendaftar PMB',
            'user_id' => 'User',
            'foto' => 'Foto',
            'metadata' => 'Metadata',
        ];
    }
}
