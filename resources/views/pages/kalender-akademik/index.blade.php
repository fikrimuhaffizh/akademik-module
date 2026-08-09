@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Kalender Akademik" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.kalender-akademik.create') }}" data-modal-title="Tambah Kalender Akademik" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-kalender" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-kalender" route="{{ route('akd.kalender-akademik.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'nama_kegiatan', 'title' => 'Nama Kegiatan'],
                    ['data' => 'periodeAkademik_nama', 'title' => 'Periode', 'orderable' => false, 'searchable' => false],
                    ['data' => 'jenis', 'title' => 'Jenis'],
                    ['data' => 'tgl_mulai', 'title' => 'Mulai', 'class' => 'text-center'],
                    ['data' => 'tgl_selesai', 'title' => 'Selesai', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" :options="['order' => [[4, 'desc']]]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
