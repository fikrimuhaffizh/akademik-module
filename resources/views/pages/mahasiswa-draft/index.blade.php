@extends('layouts.' . active_theme() . '.app')

@section('title', 'Mahasiswa Draft')

@section('header')
<x-ui.page-header title="Mahasiswa Draft (Sync dari PMB)" pretitle="Akademik">
    <x-slot:actions>
        <form action="{{ route('akd.mahasiswa-draft.sync') }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button type="submit" color="btn-success" icon="ti ti-refresh" text="Sync dari PMB" />
        </form>
    </x-slot:actions>
</x-ui.page-header>
@endsection

@section('content')
<x-ui.card>
    <x-ui.card-header class="border-bottom">
        <x-ui.datatable-toolbar dataTableId="table-mahasiswa-draft">
            <x-slot:actions>
                <form id="submit-selected-form" action="{{ route('akd.mahasiswa-draft.submit') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="draft_ids[]" id="selected-draft-ids" value="">
                    <x-ui.button type="submit" id="btn-submit-selected" color="btn-primary" icon="ti ti-send" text="Submit Selected" disabled />
                </form>
            </x-slot:actions>
        </x-ui.datatable-toolbar>
    </x-ui.card-header>

    <div class="collapse" id="table-mahasiswa-draft-filter-area">
        <x-ui.datatable-filter dataTableId="table-mahasiswa-draft" type="bare">
            <div class="row g-3">
                <div class="col-md-3">
                    <x-ui.form-select name="status_draft" label="Status" placeholder="Semua Status">
                        <option value="all">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                    </x-ui.form-select>
                </div>
                <div class="col-md-3">
                    <x-ui.form-select name="prodi_id" label="Prodi" placeholder="Semua Prodi">
                        <option value="all">Semua Prodi</option>
                        @foreach($prodis ?? [] as $prodi)
                            <option value="{{ $prodi->orgunit_id }}">{{ $prodi->name }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-3">
                    <x-ui.form-select name="angkatan" label="Angkatan" placeholder="Semua Angkatan">
                        <option value="all">Semua Angkatan</option>
                        @for($year = date('Y'); $year >= date('Y') - 10; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </x-ui.form-select>
                </div>
            </div>
        </x-ui.datatable-filter>
    </div>

    <x-ui.card-body class="p-0">
        <x-ui.datatable
            id="table-mahasiswa-draft"
            route="{{ route('akd.mahasiswa-draft.data') }}"
            checkbox
            checkboxKey="draft_id"
            :columns="[
                ['data' => 'nim', 'title' => 'NIM', 'width' => '120px'],
                ['data' => 'nama', 'title' => 'Nama'],
                ['data' => 'prodi_nama', 'title' => 'Prodi'],
                ['data' => 'angkatan', 'title' => 'Angkatan', 'width' => '100px'],
                ['data' => 'kurikulum', 'title' => 'Kurikulum', 'width' => '100px'],
                ['data' => 'status_badge', 'title' => 'Status', 'className' => 'text-center', 'width' => '120px'],
                ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
            ]"
        />
    </x-ui.card-body>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('table-mahasiswa-draft');
        const selectedInput = document.getElementById('selected-draft-ids');
        const submitBtn = document.getElementById('btn-submit-selected');
        const selectAll = document.getElementById('selectAll-table-mahasiswa-draft');

        function updateSubmitButton() {
            const checked = table.querySelectorAll('tbody input[type="checkbox"]:checked');
            const ids = Array.from(checked).map(cb => cb.value);
            selectedInput.value = ids.join(',');
            submitBtn.disabled = ids.length === 0;
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateSubmitButton();
            });
        }

        table.addEventListener('change', function (e) {
            if (e.target.type === 'checkbox' && !e.target.matches('thead input')) {
                updateSubmitButton();
            }
        });
    });
</script>
@endpush
@endsection
