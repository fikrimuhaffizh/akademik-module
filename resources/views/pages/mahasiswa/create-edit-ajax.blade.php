@extends('akademik::layouts.akademik-layout')

@section('content')
    <form id="form-mahasiswa" action="{{ isset($row) ? route('akd.mahasiswa.update', encryptId($row->mahasiswa_id)) : route('akd.mahasiswa.store') }}" method="POST">
        @csrf
        @if(isset($row)) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <x-ui.form-input name="nim" label="NIM" value="{{ $row->nim ?? '' }}" placeholder="NIM" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="nama" label="Nama" value="{{ $row->nama ?? '' }}" placeholder="Nama Lengkap" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="email" label="Email" type="email" value="{{ $row->email ?? '' }}" placeholder="Email" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="no_hp" label="No. HP" value="{{ $row->no_hp ?? '' }}" placeholder="No. HP" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="prodi_id" label="Prodi ID" type="number" value="{{ $row->prodi_id ?? '' }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="angkatan" label="Angkatan" type="number" value="{{ $row->angkatan ?? date('Y') }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-select name="status" label="Status" :options="['calon'=>'Calon','aktif'=>'Aktif','cuti'=>'Cuti','non_aktif'=>'Non Aktif','do'=>'DO','undur_diri'=>'Undur Diri','lulus'=>'Lulus']" selected="{{ $row->status ?? 'calon' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-select name="jenis_masuk" label="Jenis Masuk" :options="['reguler'=>'Reguler','transfer'=>'Transfer','rpl'=>'RPL','pindahan'=>'Pindahan']" selected="{{ $row->jenis_masuk ?? 'reguler' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="semester_masuk" label="Semester Masuk" type="number" value="{{ $row->semester_masuk ?? 1 }}" min="1" max="14" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="sks_diakui_awal" label="SKS Diakui Awal" type="number" value="{{ $row->sks_diakui_awal ?? 0 }}" min="0" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="institusi_asal" label="Institusi Asal" value="{{ $row->institusi_asal ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="prodi_asal" label="Prodi Asal" value="{{ $row->prodi_asal ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="kurikulum_kode" label="Kode Kurikulum" value="{{ $row->kurikulum_kode ?? '' }}" placeholder="Contoh: TI-2026-V1" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="user_id" label="User ID" type="number" value="{{ $row->user_id ?? '' }}" />
            </div>
        </div>
    </form>
@endsection
