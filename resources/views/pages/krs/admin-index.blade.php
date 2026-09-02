@extends('akademik::layouts.akademik-layout')

@section('title', 'KRS Mahasiswa — Admin')

@section('content')
<x-ui.page-header title="KRS Mahasiswa" pretitle="Akademik">
    <x-slot:actions>
        <a href="{{ route('akd.krs.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Tambah KRS
        </a>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <x-ui.card-body class="p-0">
        <table id="table-krs" class="table table-vcenter table-striped card-table" style="width:100%">
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Periode</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Total SKS</th>
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
    $('#table-krs').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("akd.krs.data") }}',
        columns: [
            { data: 'mahasiswa_id', name: 'mahasiswa_id' },
            { data: 'periode_akademik_id', name: 'periode_akademik_id' },
            { data: 'status', name: 'status', className: 'text-center', orderable: false },
            { data: 'total_sks', name: 'total_sks', className: 'text-center' },
            { data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false },
        ],
        order: [[1, 'desc']],
    });
});
</script>
@endpush
