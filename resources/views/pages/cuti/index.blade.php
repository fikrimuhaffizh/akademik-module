@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Data Cuti" pretitle="Mahasiswa">
        <x-slot:actions>
            @can('akd.cuti.create')
                <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.cuti.create') }}" data-modal-title="Form Cuti" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-cuti" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable
                id="table-cuti"
                route="{{ route('akd.cuti.data') }}"
                :columns="[
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'periode_akademik_id', 'title' => 'Periode Akademik'],
                    ['data' => 'alasan', 'title' => 'Alasan'],
                    ['data' => 'status', 'title' => 'Status'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false],
                ]"
            />
        </x-ui.card-body>
    </x-ui.card>
@endsection
