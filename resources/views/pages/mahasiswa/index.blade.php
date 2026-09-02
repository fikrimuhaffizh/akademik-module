@extends('layouts.' . active_theme() . '.app')

@section('title')
    Daftar Mahasiswa
@endsection

@section('header')
<x-ui.page-header title="Daftar Mahasiswa" pretitle="Mahasiswa">
    <x-slot:actions>
        @can('akd.mahasiswa.create')
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.mahasiswa.create') }}" data-modal-title="Form Mahasiswa" />
            <x-ui.dropdown>
                <x-ui.dropdown-item
                    icon="ti ti-file-import"
                    label="Impor Mahasiswa"
                    href="{{ route('akd.mahasiswa.import.index') }}" />
            </x-ui.dropdown>
        @endcan
    </x-slot:actions>
</x-ui.page-header>
@endsection

@section('content')
<x-ui.card>
    <x-ui.card-header class="border-bottom">
        <x-ui.datatable-toolbar dataTableId="table-mahasiswa">
            <x-slot:actions>
                <x-ui.dropdown-item href="#" class="export-excel-btn"
                    data-export-url="{{ route('akd.mahasiswa.export') }}"
                    data-filter-form="table-mahasiswa-filter"
                    icon="ti ti-file-export" label="Export ke Excel" />
            </x-slot:actions>
        </x-ui.datatable-toolbar>
    </x-ui.card-header>
    <div class="collapse" id="table-mahasiswa-filter-area">
        <x-ui.datatable-filter dataTableId="table-mahasiswa" type="bare">
            <div class="row g-3">
                <div class="col-md-4">
                    <x-ui.form-select name="angkatan" label="Angkatan" placeholder="Semua Angkatan">
                        <option value="all">Semua Angkatan</option>
                        @foreach($angkatans ?? [] as $ang)
                            <option value="{{ $ang }}">{{ $ang }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-4">
                    <x-ui.form-select name="status" label="Status" placeholder="Semua Status">
                        <option value="all">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="cuti">Cuti</option>
                        <option value="lulus">Lulus</option>
                        <option value="dropout">Dropout</option>
                        <option value="keluar">Keluar</option>
                    </x-ui.form-select>
                </div>
                <div class="col-md-4">
                    <x-ui.form-select name="prodi_id" label="Prodi" placeholder="Semua Prodi">
                        <option value="all">Semua Prodi</option>
                        @foreach($prodis ?? [] as $prodi)
                            <option value="{{ $prodi->orgunit_id }}">{{ $prodi->nama }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
            </div>
        </x-ui.datatable-filter>
    </div>
    <x-ui.card-body class="p-0">
        <x-ui.datatable
            id="table-mahasiswa"
            route="{{ route('akd.mahasiswa.data') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '50px'],
                ['data' => 'mahasiswa_info', 'title' => 'Mahasiswa', 'orderable' => false],
                ['data' => 'status_badge', 'title' => 'Status', 'className' => 'text-center', 'orderable' => false],
                ['data' => 'kurikulum', 'title' => 'Kurikulum'],
                ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
            ]"
        />
    </x-ui.card-body>
</x-ui.card>
@endsection
