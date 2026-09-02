@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header title="Data Nilai" pretitle="Akademik">
        <x-slot:actions>
            @can('akd.nilai.create')
                <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.nilai.create') }}" data-modal-title="Form Nilai" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-nilai" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable
                id="table-nilai"
                route="{{ route('akd.nilai.data') }}"
                :columns="[
                    ['data' => 'mahasiswa_id', 'title' => 'Mahasiswa'],
                    ['data' => 'mata_kuliah_id', 'title' => 'Mata Kuliah'],
                    ['data' => 'nilai_angka', 'title' => 'Nilai Angka'],
                    ['data' => 'nilai_huruf', 'title' => 'Nilai Huruf'],
                    ['data' => 'sks', 'title' => 'SKS'],
                    ['data' => 'status', 'title' => 'Status'],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false],
                ]"
            />
        </x-ui.card-body>
    </x-ui.card>
@endsection
