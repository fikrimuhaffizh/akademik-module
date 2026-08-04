<?php

namespace Modules\Akademik\Http\Requests;

use App\Http\Requests\BaseRequest;

class BiodataRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mahasiswa_id' => ['required', 'integer', 'exists:akd_mahasiswa,mahasiswa_id'],
            'nik' => ['nullable', 'string', 'max:20'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tgl_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'agama' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'provinsi_kode' => ['nullable', 'string', 'max:10'],
            'kabupaten_kode' => ['nullable', 'string', 'max:10'],
            'kecamatan_kode' => ['nullable', 'string', 'max:10'],
            'kelurahan_kode' => ['nullable', 'string', 'max:10'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ortu' => ['nullable', 'string', 'max:100'],
            'penghasilan_ortu' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mahasiswa_id' => 'Mahasiswa',
            'nik' => 'NIK',
            'tempat_lahir' => 'Tempat Lahir',
            'tgl_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'agama' => 'Agama',
            'alamat' => 'Alamat',
            'kota' => 'Kota',
            'provinsi' => 'Provinsi',
            'kabupaten' => 'Kabupaten',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan',
            'provinsi_kode' => 'Kode Provinsi',
            'kabupaten_kode' => 'Kode Kabupaten',
            'kecamatan_kode' => 'Kode Kecamatan',
            'kelurahan_kode' => 'Kode Kelurahan',
            'kode_pos' => 'Kode Pos',
            'nama_ayah' => 'Nama Ayah',
            'nama_ibu' => 'Nama Ibu',
            'pekerjaan_ortu' => 'Pekerjaan Orang Tua',
            'penghasilan_ortu' => 'Penghasilan Orang Tua',
        ];
    }
}
