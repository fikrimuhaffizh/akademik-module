@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Tahun Ajaran" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.tahun-ajaran.create') }}" data-modal-title="Tambah Tahun Ajaran" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-ta" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-ta" route="{{ route('akd.tahun-ajaran.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'nama', 'title' => 'Nama'],
                    ['data' => 'tahun_mulai', 'title' => 'Mulai', 'class' => 'text-center'],
                    ['data' => 'tahun_selesai', 'title' => 'Selesai', 'class' => 'text-center'],
                    ['data' => 'is_aktif', 'title' => 'Status', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" :options="['order' => [[2, 'desc']]]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
