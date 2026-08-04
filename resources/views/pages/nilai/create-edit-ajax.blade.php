@extends('akademik::layouts.akademik-layout')

@section('content')
    <form id="form-nilai" action="{{ isset($row) ? route('akd.nilai.update', encryptId($row->nilai_id)) : route('akd.nilai.store') }}" method="POST">
        @csrf
        @if(isset($row)) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <x-ui.form-input name="mahasiswa_id" label="Mahasiswa ID" type="number" value="{{ $row->mahasiswa_id ?? '' }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="kelas_id" label="Kelas ID" type="number" value="{{ $row->kelas_id ?? '' }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="mata_kuliah_id" label="Mata Kuliah ID" type="number" value="{{ $row->mata_kuliah_id ?? '' }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="periode_akademik_id" label="Periode Akademik ID" type="number" value="{{ $row->periode_akademik_id ?? '' }}" required />
            </div>
            <div class="col-md-4">
                <x-ui.form-input name="nilai_angka" label="Nilai Angka" type="number" step="0.01" min="0" max="4" value="{{ $row->nilai_angka ?? '' }}" />
            </div>
            <div class="col-md-4">
                <x-ui.form-input name="nilai_huruf" label="Nilai Huruf" value="{{ $row->nilai_huruf ?? '' }}" />
            </div>
            <div class="col-md-4">
                <x-ui.form-input name="bobot" label="Bobot" type="number" step="0.01" min="0" max="4" value="{{ $row->bobot ?? '' }}" />
            </div>
            <div class="col-md-4">
                <x-ui.form-input name="sks" label="SKS" type="number" min="1" max="20" value="{{ $row->sks ?? '' }}" required />
            </div>
            <div class="col-md-4">
                <x-ui.form-select name="status" label="Status" :options="['aktif'=>'Aktif','mengulang'=>'Mengulang','konversi'=>'Konversi','rpl'=>'RPL']" selected="{{ $row->status ?? 'aktif' }}" />
            </div>
            <div class="col-md-4">
                <x-ui.form-checkbox name="is_lulus" label="Lulus" checked="{{ ($row->is_lulus ?? false) ? 'checked' : '' }}" />
            </div>
        </div>
    </form>
@endsection
