@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Pembebanan Dosen" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.pembebanan.create') }}" data-modal-title="Tambah Pembebanan Dosen" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-pembebanan" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-pembebanan" route="{{ route('akd.pembebanan.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'kelas', 'title' => 'Kelas', 'orderable' => false, 'searchable' => false],
                    ['data' => 'pegawai', 'title' => 'Dosen', 'orderable' => false, 'searchable' => false],
                    ['data' => 'peran', 'title' => 'Peran', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
