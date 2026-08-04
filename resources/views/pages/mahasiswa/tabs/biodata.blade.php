<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-user me-2"></i>Biodata Mahasiswa</h3>
        <div class="card-actions">
            @can('akd.mahasiswa.update')
                <x-ui.button type="edit" class="ajax-modal-btn btn-sm" data-url="{{ route('akd.biodata.edit', encryptId($biodata->biodata_id)) }}" data-modal-title="Ubah Biodata">Ubah Data</x-ui.button>
            @endcan
        </div>
    </div>
        @if($biodata)
            <div class="row">
                <div class="col-md-6">
                    <h4 class="text-secondary mb-3">Data Pribadi</h4>
                    <table class="table table-borderless">
                        <tr><td class="text-secondary w-40">NIK</td><td>{{ $biodata->nik ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Tempat Lahir</td><td>{{ $biodata->tempat_lahir ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Tanggal Lahir</td><td>{{ $biodata->tgl_lahir?->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Jenis Kelamin</td><td>{{ $biodata->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                        <tr><td class="text-secondary">Agama</td><td>{{ $biodata->agama ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Kewarganegaraan</td><td>{{ $biodata->kewarganegaraan ?? 'WNI' }}</td></tr>
                        <tr><td class="text-secondary">Suku</td><td>{{ $biodata->suku ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">No. KK</td><td>{{ $biodata->no_kk ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h4 class="text-secondary mb-3">Alamat</h4>
                    <table class="table table-borderless">
                        <tr><td class="text-secondary w-40">Alamat</td><td>{{ $biodata->alamat ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">RT/RW</td><td>{{ ($biodata->rt ?? '-') . '/' . ($biodata->rw ?? '-') }}</td></tr>
                        <tr><td class="text-secondary">Dusun</td><td>{{ $biodata->dusun ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Kelurahan</td><td>{{ $biodata->kelurahan ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Kecamatan</td><td>{{ $biodata->kecamatan ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Kab/Kota</td><td>{{ $biodata->kabupaten ?? $biodata->kota ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Provinsi</td><td>{{ $biodata->provinsi ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Kode Pos</td><td>{{ $biodata->kode_pos ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-4">
                    <h4 class="text-secondary mb-3">Data Ayah</h4>
                    <table class="table table-borderless table-sm">
                        <tr><td class="text-secondary">Nama</td><td>{{ $biodata->nama_ayah ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">NIK</td><td>{{ $biodata->nik_ayah ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Pekerjaan</td><td>{{ $biodata->pekerjaan_ayah ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Pendidikan</td><td>{{ $biodata->pendidikan_ayah ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Penghasilan</td><td>{{ $biodata->penghasilan_ayah ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-4">
                    <h4 class="text-secondary mb-3">Data Ibu</h4>
                    <table class="table table-borderless table-sm">
                        <tr><td class="text-secondary">Nama</td><td>{{ $biodata->nama_ibu ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">NIK</td><td>{{ $biodata->nik_ibu ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Pekerjaan</td><td>{{ $biodata->pekerjaan_ibu ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Pendidikan</td><td>{{ $biodata->pendidikan_ibu ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Penghasilan</td><td>{{ $biodata->penghasilan_ibu ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-4">
                    <h4 class="text-secondary mb-3">Data Wali</h4>
                    <table class="table table-borderless table-sm">
                        <tr><td class="text-secondary">Nama</td><td>{{ $biodata->nama_wali ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">NIK</td><td>{{ $biodata->nik_wali ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Pekerjaan</td><td>{{ $biodata->pekerjaan_wali ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Pendidikan</td><td>{{ $biodata->pendidikan_wali ?? '-' }}</td></tr>
                        <tr><td class="text-secondary">Penghasilan</td><td>{{ $biodata->penghasilan_wali ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        @else
            <x-ui.empty-state icon="ti ti-user-off" title="Belum ada biodata" description="Biodata belum diinput untuk mahasiswa ini." />
        @endif
    </div>
</div>
