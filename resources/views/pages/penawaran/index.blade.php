@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Penawaran Mata Kuliah" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.penawaran.generate.form') }}" data-modal-title="Generate Penawaran dari Kurikulum">Generate dari Kurikulum</x-ui.button>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.penawaran.create') }}" data-modal-title="Tambah Penawaran MK" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-penawaran" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-penawaran" route="{{ route('akd.penawaran.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'periodeAkademik', 'title' => 'Periode', 'orderable' => false, 'searchable' => false],
                    ['data' => 'mata_kuliah_id', 'title' => 'Mata Kuliah'],
                    ['data' => 'semester', 'title' => 'Smstr', 'class' => 'text-center', 'width' => '70px', 'orderable' => false, 'searchable' => false],
                    ['data' => 'jenis', 'title' => 'Jenis (Wajib/Pilihan)', 'class' => 'text-center', 'orderable' => false, 'searchable' => false],
                    ['data' => 'prodi_id', 'title' => 'Prodi'],
                    ['data' => 'is_aktif', 'title' => 'Status', 'class' => 'text-center'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>
@endsection
