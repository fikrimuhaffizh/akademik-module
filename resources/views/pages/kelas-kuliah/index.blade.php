@extends('layouts.' . active_theme() . '.app')

@section('title', 'Kelas Kuliah')

@section('header')
    <x-ui.page-header title="Kelas Kuliah" pretitle="Perkuliahan">
        <x-slot:actions>
            <x-ui.button type="create" class="ajax-modal-btn" data-url="{{ route('akd.kelas-akd.create') }}" data-modal-title="Tambah Kelas Kuliah" data-modal-size="modal-xl" />
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <x-ui.datatable-toolbar dataTableId="table-kelas" />
        </x-ui.card-header>
        <x-ui.card-body class="p-0">
            <x-ui.datatable id="table-kelas" route="{{ route('akd.kelas-akd.data') }}"
                :columns="[
                    ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '50px'],
                    ['data' => 'penawaran', 'title' => 'Penawaran MK', 'orderable' => false, 'searchable' => false],
                    ['data' => 'nama_kelas', 'title' => 'Nama Kelas', 'searchable' => false],
                    ['data' => 'kapasitas', 'title' => 'Kapasitas', 'class' => 'text-center', 'searchable' => false],
                    ['data' => 'sistem_kuliah', 'title' => 'Sistem', 'searchable' => false],
                    ['data' => 'is_aktif', 'title' => 'Status', 'class' => 'text-center', 'searchable' => false],
                    ['data' => 'action', 'title' => 'Aksi', 'orderable' => false, 'searchable' => false, 'class' => 'text-center', 'width' => '100px'],
                ]" />
        </x-ui.card-body>
    </x-ui.card>


{{-- Delegated handlers clone-row (kelas-kuliah modal) — inline script di
     modal AJAX tidak dieksekusi core-ajax, makanya hidup di halaman host. --}}

@push('scripts')
<script>
(function () {
    // Delegated handlers for clone-row forms (kelas-kuliah modal).
    // Inline <script> in AJAX-loaded modal content is NOT executed by core-ajax
    // (.html()), so these live here (rendered in the main layout) and use
    // event delegation — they keep working for any modal opened later.

    function reindex(listSel) {
        var list = document.querySelector(listSel);
        if (!list) return;
        list.querySelectorAll(':scope > .row').forEach(function (row, idx) {
            row.querySelectorAll('[name*="[__IDX__]"]').forEach(function (el) {
                el.name = el.name.replace('__IDX__', idx);
            });
        });
    }

    document.addEventListener('click', function (e) {
        // Add row
        var add = e.target.closest('[data-add-row]');
        if (add) {
            var listSel = add.getAttribute('data-list');
            var tplSel = add.getAttribute('data-tpl');
            var tpl = document.querySelector(tplSel);
            var list = document.querySelector(listSel);
            if (!tpl || !list) return;
            var wrap = document.createElement('div');
            wrap.innerHTML = tpl.innerHTML.trim();
            var row = wrap.firstElementChild;
            list.appendChild(row);
            reindex(listSel);
            // select2 (.js-select2-ajax) inside the clone auto-inits via
            // the MutationObserver in tabler.js — no manual init needed.
            return;
        }

        // Remove row
        var rm = e.target.closest('.remove-row');
        if (rm) {
            var rowEl = rm.closest('.row');
            if (!rowEl) return;
            var parentList = rowEl.parentElement;
            rowEl.remove();
            reindex('#' + parentList.id);
        }
    });
})();
</script>
@endpush
@endsection
