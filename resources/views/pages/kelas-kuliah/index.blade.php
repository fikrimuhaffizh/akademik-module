@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Kelas Kuliah" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.kelas-akd.create') }}" data-modal-title="Tambah Kelas Kuliah" data-modal-size="modal-xl" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-kelas" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-kelas" route="{{ route('akd.kelas-akd.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'penawaran', 'title' => 'Penawaran MK', 'orderable' => false, 'searchable' => false],
                    ['data' => 'nama_kelas', 'title' => 'Nama Kelas', 'searchable' => false],
                    ['data' => 'kapasitas', 'title' => 'Kapasitas', 'class' => 'text-center', 'searchable' => false],
                    ['data' => 'sistem_kuliah', 'title' => 'Sistem', 'searchable' => false],
                    ['data' => 'is_aktif', 'title' => 'Status', 'class' => 'text-center', 'searchable' => false],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
