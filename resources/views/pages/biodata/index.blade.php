@extends('layouts.' . active_theme() . '.app')

@section('title', 'Data Biodata')

@section('header')
    <x-ui.page-header title="Data Biodata" pretitle="Mahasiswa">
        <x-slot:actions>
            @can('akd.biodata.create')
                <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.biodata.create') }}" data-modal-title="Form Biodata" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-biodata" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable
                id="table-biodata"
                route="{{ route('akd.biodata.data') }}"
                :columns="[
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'nik', 'title' => 'NIK'],
                    ['data' => 'tempat_lahir', 'title' => 'Tempat Lahir'],
                    ['data' => 'tgl_lahir', 'title' => 'Tanggal Lahir'],
                    ['data' => 'jenis_kelamin', 'title' => 'Jenis Kelamin'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false],
                ]"
            />
        </x-ui.card-body>
    </x-ui.card>
@endsection
