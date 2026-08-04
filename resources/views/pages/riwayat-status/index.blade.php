@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Riwayat Status" pretitle="Mahasiswa">
        <x-slot:actions>
            @can('akd.riwayat_status.create')
                <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.riwayat-status.create') }}" data-modal-title="Form Riwayat Status" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-riwayat-status" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable
                id="table-riwayat-status"
                route="{{ route('akd.riwayat-status.data') }}"
                :columns="[
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'status_lama', 'title' => 'Status Lama'],
                    ['data' => 'status_baru', 'title' => 'Status Baru'],
                    ['data' => 'tgl_efektif', 'title' => 'Tanggal Efektif'],
                    ['data' => 'diproses_oleh', 'title' => 'Diproses Oleh'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false],
                ]"
            />
        </x-ui.card-body>
    </x-ui.card>
@endsection
