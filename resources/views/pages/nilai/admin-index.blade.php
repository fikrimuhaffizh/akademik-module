@extends('akademik::layouts.akademik-layout')

@section('title', 'Kelola Nilai — Admin')

@section('content')
<x-ui.page-header title="Nilai" pretitle="Akademik">
    <x-slot:actions>
        <a href="{{ route('akd.nilai.template') }}" class="btn btn-outline-secondary">
            <i class="ti ti-download me-1"></i> Template
        </a>
        <button type="button" class="btn btn-primary ajax-modal-btn"
                data-url="{{ route('akd.nilai.import') }}">
            <i class="ti ti-upload me-1"></i> Import Nilai
        </button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <x-ui.card-body class="p-0">
        <table id="table-nilai" class="table table-vcenter table-striped card-table" style="width:100%">
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Mata Kuliah</th>
                    <th class="text-center">SKS</th>
                    <th class="text-center">Nilai</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width:80px">Aksi</th>
                </tr>
            </thead>
        </table>
    </x-ui.card-body>
</x-ui.card>
@endsection

@push('scripts')
<script>
$(function() {
    $('#table-nilai').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("akd.nilai.data") }}',
        columns: [
            { data: 'mahasiswa_id', name: 'mahasiswa_id' },
            { data: 'mata_kuliah_id', name: 'mata_kuliah_id' },
            { data: 'sks', name: 'sks', className: 'text-center' },
            { data: 'nilai_huruf', name: 'nilai_huruf', className: 'text-center' },
            { data: 'is_lulus', name: 'is_lulus', className: 'text-center', orderable: false },
            { data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false },
        ],
        order: [[0, 'asc']],
    });
});
</script>
@endpush
