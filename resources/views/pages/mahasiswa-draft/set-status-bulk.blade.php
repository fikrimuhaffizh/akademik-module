<x-ui.form-modal
    title="Set Status Akhir (Massal)"
    :route="route('akd.mahasiswa-draft.set-status-bulk')"
    method="PUT"
    size="modal-sm"
    submitText="Terapkan ke Terpilih"
>
    <input type="hidden" name="draft_ids" id="bulk-status-draft-ids" value="">
    <div class="text-secondary small mb-3">
        <i class="ti ti-info-circle me-1"></i> Draft terpilih: <strong id="bulk-status-count">0</strong>.
    </div>

    <x-ui.form-select name="status_draft" label="Status Akhir" required>
        <option value="draft">Draft</option>
        <option value="submitted">Submitted</option>
        <option value="batal">Batal</option>
    </x-ui.form-select>
    <div class="text-secondary small">
        <i class="ti ti-alert-circle me-1"></i> "Batal" menandai draft tidak akan diproses menjadi mahasiswa.
    </div>
</x-ui.form-modal>
