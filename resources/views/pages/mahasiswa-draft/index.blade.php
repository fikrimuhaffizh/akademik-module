@extends('layouts.' . active_theme() . '.app')

@section('title', 'Mahasiswa Draft')

@section('header')
<x-ui.page-header title="Mahasiswa Draft (Sync dari PMB)" pretitle="Akademik">
    <x-slot:actions>
        <button type="button" id="btn-sync-pmb" class="btn btn-success" data-sync-url="{{ route('akd.mahasiswa-draft.sync') }}" data-csrf="{{ csrf_token() }}">
            <i class="ti ti-refresh me-1"></i> Sync dari PMB
        </button>
    </x-slot:actions>
</x-ui.page-header>
@endsection

@section('content')
{{-- Hasil sync (dipopulasi via AJAX) --}}
<div id="sync-result-panel" class="alert d-none mb-3" role="alert">
    <div class="d-flex align-items-center mb-2">
        <i class="ti ti-chart-bar me-2" style="font-size:1.2rem"></i>
        <strong id="sync-result-title"></strong>
    </div>
    <ul class="mb-0 ps-3" id="sync-result-list"></ul>
</div>

<x-ui.card>
    <x-ui.card-header class="d-flex flex-wrap align-items-center justify-content-between">
        <x-ui.datatable-toolbar dataTableId="table-mahasiswa-draft" class="flex-grow-1" :filter="false">
            <x-slot:actions>
                <button type="button" id="btn-bulk-delete-confirm" class="dropdown-item text-danger d-none">
                    <i class="ti ti-trash me-1"></i> Hapus Dipilih (<span class="bulk-count">0</span>)
                </button>
            </x-slot:actions>
        </x-ui.datatable-toolbar>
        <div class="d-flex flex-wrap gap-2 ms-auto">
            <x-ui.button type="button" text="Set Kurikulum" icon="ti ti-book-2" size="sm" color="btn-outline-primary" class="ajax-modal-btn"
                id="btn-bulk-kurikulum"
                data-url="{{ route('akd.mahasiswa-draft.set-kurikulum-bulk.form') }}"
                data-modal-title="Set Kurikulum" data-modal-size="modal-lg" />
            <x-ui.button type="button" text="Set Status Akhir" icon="ti ti-flag-check" size="sm" color="btn-outline-secondary" class="ajax-modal-btn"
                id="btn-bulk-status"
                data-url="{{ route('akd.mahasiswa-draft.set-status-bulk.form') }}"
                data-modal-title="Set Status Akhir" data-modal-size="modal-sm" />
        </div>
    </x-ui.card-header>

    <div class="collapse" id="table-mahasiswa-draft-filter-area">
        <x-ui.datatable-filter dataTableId="table-mahasiswa-draft" type="bare">
            <div class="row g-3">
                <div class="col-md-3">
                    <x-ui.form-select name="status_draft" label="Status" placeholder="Semua Status">
                        <option value="all">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="terima">Terima</option>
                        <option value="batal">Batal</option>
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
                        @foreach($angkatans ?? [] as $ang)
                            <option value="{{ $ang }}">{{ $ang }}</option>
                        @endforeach
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
                ['data' => 'no_pendaftaran', 'title' => 'No. Pendaftaran PMB', 'width' => '150px'],
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
        const csrf = '{{ csrf_token() }}';
        const getDT = () => window['DT_table-mahasiswa-draft'];
        const getSelectedIds = () => getDT()?.getSelectedIds?.() ?? [];

        const updateBulkButtons = () => {
            const count = getSelectedIds().length;
            const deleteItem = document.querySelector('.dropdown-item.text-danger');
            const countSpans = document.querySelectorAll('.bulk-count');
            if (deleteItem) deleteItem.classList.toggle('d-none', count === 0);
            countSpans.forEach(span => span.textContent = count);
        };

        document.addEventListener('change', (e) => {
            if (e.target.matches('.dt-checkboxes, #selectAll-table-mahasiswa-draft')) {
                setTimeout(updateBulkButtons, 50);
            }
        });

        // ── Bulk delete (AJAX) ──
        document.getElementById('btn-bulk-delete-confirm')?.addEventListener('click', function () {
            const ids = getSelectedIds();
            if (! ids.length) return;

            Swal.fire({
                title: 'Hapus Massal?',
                text: ids.length + ' draft akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d63939',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            }).then(function (result) {
                if (! result.isConfirmed) return;

                axios.post('{{ route("akd.mahasiswa-draft.bulk-destroy") }}', { ids: ids }, {
                    headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                }).then(function (res) {
                    showSuccessMessage(res.data?.message || 'Berhasil dihapus');
                    if (typeof reloadDataTable === 'function') reloadDataTable('table-mahasiswa-draft');
                    updateBulkButtons();
                }).catch(function (err) {
                    showErrorMessage(err.response?.data?.message || err.message);
                });
            });
        });

        // ── Pass selected ids into bulk modals ──
        window._bulkDraftIds = [];
        ['btn-bulk-kurikulum', 'btn-bulk-status'].forEach(btnId => {
            const b = document.getElementById(btnId);
            if (! b) return;
            b.addEventListener('click', function () {
                window._bulkDraftIds = getSelectedIds();
                const isKur = btnId === 'btn-bulk-kurikulum';
                const countId = isKur ? 'bulk-kurikulum-count' : 'bulk-status-count';
                const idsId = isKur ? 'bulk-kurikulum-draft-ids' : 'bulk-status-draft-ids';
                function tryFill() {
                    const countEl = document.getElementById(countId);
                    const idsEl = document.getElementById(idsId);
                    if (countEl) countEl.textContent = window._bulkDraftIds.length;
                    if (idsEl) idsEl.value = JSON.stringify(window._bulkDraftIds);
                    return !!(countEl && idsEl);
                }
                if (tryFill()) return;
                const obs = new MutationObserver(() => { if (tryFill()) obs.disconnect(); });
                obs.observe(document.body, { childList: true, subtree: true });
                setTimeout(() => obs.disconnect(), 5000);
            });
        });

        // Bulk modal forms: hidden draft_ids (JSON) => per-id array inputs before ajax-form reads FormData
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (! form.matches?.('.ajax-form')) return;
            const hidden = form.querySelector('input[type="hidden"][name="draft_ids"]');
            if (! hidden) return;
            try {
                const ids = JSON.parse(hidden.value || '[]');
                hidden.remove();
                ids.forEach(id => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = 'draft_ids[]'; inp.value = id;
                    form.appendChild(inp);
                });
            } catch (err) { /* ignore */ }
        }, true);

        // ── Sync PMB via AJAX: spinner di tombol, hasil statistik dalam list ──
        const btn = document.getElementById('btn-sync-pmb');
        const panel = document.getElementById('sync-result-panel');
        const title = document.getElementById('sync-result-title');
        const list = document.getElementById('sync-result-list');

        btn.addEventListener('click', function () {
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyinkronkan...';
            panel.classList.add('d-none');

            axios.post(btn.dataset.syncUrl, {}, {
                headers: {
                    'X-CSRF-TOKEN': btn.dataset.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(function (res) {
                const d = res.data || {};
                const items = [
                    `<span class="badge bg-success me-2">${d.synced ?? 0}</span> mahasiswa baru ditambahkan ke draft`,
                    `<span class="badge bg-secondary me-2">${d.skipped ?? 0}</span> dilewati (sudah ada di draft)`,
                    `<span class="badge bg-danger me-2">${(d.errors ?? []).length}</span> gagal disinkronkan`,
                    ...(d.errors ?? []).map(e => `<li class="text-danger">${e}</li>`),
                ];
                title.textContent = d.message || 'Sync selesai';
                list.innerHTML = items.map(i => `<li>${i}</li>`).join('');
                panel.classList.remove('d-none');
                panel.classList.remove('alert-info', 'alert-warning');
                panel.classList.add((d.errors ?? []).length ? 'alert-warning' : 'alert-info');

                if (typeof reloadDataTable === 'function') reloadDataTable('table-mahasiswa-draft');
                else if (getDT()?.table?.ajax) getDT().table.ajax.reload(null, false);
            }).catch(function (err) {
                title.textContent = 'Sync gagal';
                list.innerHTML = `<li class="text-danger">${err.response?.data?.message || err.message}</li>`;
                panel.classList.remove('d-none', 'alert-info', 'alert-warning');
                panel.classList.add('alert-warning');
            }).finally(function () {
                btn.disabled = false;
                btn.innerHTML = original;
            });
        });
    });
</script>
@endpush
@endsection
