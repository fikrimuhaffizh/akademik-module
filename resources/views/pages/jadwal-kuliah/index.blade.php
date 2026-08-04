@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Jadwal Kuliah" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.jadwal-akd.create') }}" data-modal-title="Tambah Jadwal Kuliah" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-jadwal-kuliah" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-jadwal-kuliah" route="{{ route('akd.jadwal-akd.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'kelas', 'title' => 'Kelas'],
                    ['data' => 'hari', 'title' => 'Hari', 'class' => 'text-center'],
                    ['data' => 'waktu', 'title' => 'Waktu', 'class' => 'text-center'],
                    ['data' => 'ruang', 'title' => 'Ruang/Online', 'class' => 'text-center'],
                    ['data' => 'status', 'title' => 'Status', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
