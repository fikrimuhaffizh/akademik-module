@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Data Cekal" pretitle="Mahasiswa">
        <x-slot:actions>
            @can('akd.cekal.create')
                <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.cekal.create') }}" data-modal-title="Form Cekal" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-cekal" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable
                id="table-cekal"
                route="{{ route('akd.cekal.data') }}"
                :columns="[
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'jenis', 'title' => 'Jenis'],
                    ['data' => 'alasan', 'title' => 'Alasan'],
                    ['data' => 'tgl_mulai', 'title' => 'Tanggal Mulai'],
                    ['data' => 'is_aktif', 'title' => 'Aktif'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false],
                ]"
            />
        </x-ui.card-body>
    </x-ui.card>
@endsection
