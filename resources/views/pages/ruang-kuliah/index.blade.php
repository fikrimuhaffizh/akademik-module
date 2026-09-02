@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Ruang Kuliah" pretitle="Pengaturan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.ruang-akd.create') }}" data-modal-title="Tambah Ruang Kuliah" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-ruang" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-ruang" route="{{ route('akd.ruang-akd.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'kode', 'title' => 'Kode'],
                    ['data' => 'nama', 'title' => 'Nama'],
                    ['data' => 'gedung', 'title' => 'Gedung'],
                    ['data' => 'kapasitas', 'title' => 'Kapasitas', 'class' => 'text-center'],
                    ['data' => 'jenis', 'title' => 'Jenis', 'class' => 'text-center'],
                    ['data' => 'is_aktif', 'title' => 'Status', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
