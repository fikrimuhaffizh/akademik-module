@extends('layouts.' . active_theme() . '.app')

@section('title', 'Periode Akademik')

@section('header')
    <x-ui.page-header title="Periode Akademik" pretitle="Pengaturan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.periode-akademik.create') }}" data-modal-title="Tambah Periode Akademik" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-pa" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-pa" route="{{ route('akd.periode-akademik.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'nama', 'title' => 'Nama'],
                    ['data' => 'semester', 'title' => 'Semester', 'class' => 'text-center'],
                    ['data' => 'tahun_mulai', 'title' => 'Thn Mulai', 'class' => 'text-center'],
                    ['data' => 'tahun_selesai', 'title' => 'Thn Selesai', 'class' => 'text-center'],
                    ['data' => 'is_aktif', 'title' => 'Status', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
