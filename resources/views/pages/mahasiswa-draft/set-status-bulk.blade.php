<x-ui.form-modal
    title="Set Status Akhir (Massal)"
    :route="route('akd.mahasiswa-draft.set-status-bulk')"
    method="PUT"
    size="modal-sm"
    submitText="Terapkan"
>
    <input type="hidden" name="draft_ids" id="bulk-status-draft-ids" value="">
    <div class="text-secondary small mb-3">
        <i class="ti ti-info-circle me-1"></i> Draft terpilih: <strong id="bulk-status-count">0</strong>.
    </div>

    <x-ui.form-select name="status_draft" label="Status Akhir" required>
        <option value="draft">Draft</option>
        <option value="terima">Terima</option>
        <option value="batal">Batal</option>
    </x-ui.form-select>

    <div id="status-terima-warning" class="d-none alert alert-warning mb-0 mt-3">
        <i class="ti ti-alert-triangle me-1"></i>
        <strong>Peringatan:</strong> Draft yang dipilih <strong>Terima</strong> akan diproses menjadi mahasiswa aktif dan <strong>tidak akan tampil lagi di halaman ini</strong>.
    </div>

    <script>
        document.querySelector('[name="status_draft"]')?.addEventListener('change', function () {
            document.getElementById('status-terima-warning').classList.toggle('d-none', this.value !== 'terima');
        });
    </script>
</x-ui.form-modal>
