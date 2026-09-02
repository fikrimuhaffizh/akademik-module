@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Pembimbing Mahasiswa" pretitle="Akademik">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.pembimbing-mahasiswa.create') }}" data-modal-title="Tambah Pembimbing Mahasiswa" />
            <a href="{{ route('akd.pembimbing-mahasiswa.import.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-file-import me-1"></i> Import
            </a>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-pma" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-pma" route="{{ route('akd.pembimbing-mahasiswa.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'periode_akademik_id', 'title' => 'Periode Akademik'],
                    ['data' => 'pegawai_id', 'title' => 'Dosen Pembimbing'],
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'jenis_pembimbing', 'title' => 'Jenis Pembimbing'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" :options="['order' => [[0, 'desc']]]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
