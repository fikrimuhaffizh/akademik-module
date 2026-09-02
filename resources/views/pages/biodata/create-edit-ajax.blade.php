@extends('layouts.' . active_theme() . '.app')

@section('content')
    <form id="form-biodata" action="{{ isset($row) ? route('akd.biodata.update', encryptId($row->biodata_id)) : route('akd.biodata.store') }}" method="POST">
        @csrf
        @if(isset($row)) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <x-ui.form-input name="mahasiswa_id" label="Mahasiswa ID" type="number" value="{{ $row->mahasiswa_id ?? '' }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="nik" label="NIK" value="{{ $row->nik ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="tempat_lahir" label="Tempat Lahir" value="{{ $row->tempat_lahir ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="tgl_lahir" label="Tanggal Lahir" type="date" value="{{ $row->tgl_lahir ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-select name="jenis_kelamin" label="Jenis Kelamin" :options="['L'=>'Laki-laki','P'=>'Perempuan']" selected="{{ $row->jenis_kelamin ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="agama" label="Agama" value="{{ $row->agama ?? '' }}" />
            </div>
            <div class="col-md-12">
                <x-ui.form-textarea name="alamat" label="Alamat" value="{{ $row->alamat ?? '' }}" />
            </div>

            {{-- Wilayah cascading (komponen terpusat): provinsi → kabupaten → kecamatan --}}
            <div class="col-md-12">
                <x-ui.wilayah-cascade
                    :wilayah-data="$wilayahData"
                    :item="$row ?? null"
                    :name-fields="[
                        'provinsi_kode' => 'provinsi',
                        'kabupaten_kode' => ['kabupaten', 'kota'],
                        'kecamatan_kode' => 'kecamatan',
                        'kelurahan_kode' => 'kelurahan',
                    ]"
                />
            </div>

            {{-- Hidden inputs teks wilayah (display backward-compat, di-sync komponen) --}}
            <input type="hidden" name="provinsi" id="provinsi_name" value="{{ $row->provinsi ?? '' }}">
            <input type="hidden" name="kabupaten" id="kabupaten_name" value="{{ $row->kabupaten ?? '' }}">
            <input type="hidden" name="kota" id="kota_name" value="{{ $row->kota ?? '' }}">
            <input type="hidden" name="kecamatan" id="kecamatan_name" value="{{ $row->kecamatan ?? '' }}">
            <input type="hidden" name="kelurahan" id="kelurahan_name" value="{{ $row->kelurahan ?? '' }}">

            <div class="col-md-6">
                <x-ui.form-input name="kode_pos" label="Kode Pos" value="{{ $row->kode_pos ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="nama_ayah" label="Nama Ayah" value="{{ $row->nama_ayah ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="nama_ibu" label="Nama Ibu" value="{{ $row->nama_ibu ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="pekerjaan_ortu" label="Pekerjaan Orang Tua" value="{{ $row->pekerjaan_ortu ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="penghasilan_ortu" label="Penghasilan Orang Tua" value="{{ $row->penghasilan_ortu ?? '' }}" />
            </div>
        </div>
    </form>
@endsection
