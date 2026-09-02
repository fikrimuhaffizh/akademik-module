@extends('layouts.' . active_theme() . '.app')

@section('title', 'Data Transfer')

@section('header')
    <x-ui.page-header title="Data Transfer" pretitle="Mahasiswa">
        <x-slot:actions>
            @can('akd.transfer.create')
                <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.transfer.create') }}" data-modal-title="Form Transfer" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-transfer" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable
                id="table-transfer"
                route="{{ route('akd.transfer.data') }}"
                :columns="[
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'jenis', 'title' => 'Jenis'],
                    ['data' => 'institusi_asal', 'title' => 'Institusi Asal'],
                    ['data' => 'sks_diakui', 'title' => 'SKS Diakui'],
                    ['data' => 'status', 'title' => 'Status'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false],
                ]"
            />
        </x-ui.card-body>
    </x-ui.card>
@endsection
