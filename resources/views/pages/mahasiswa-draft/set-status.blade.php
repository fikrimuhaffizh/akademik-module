<x-ui.form-modal
    title="Set Status Akhir — {{ $draft->nama }}"
    :route="route('akd.mahasiswa-draft.set-status', encryptId($draft->draft_id))"
    method="PUT"
    size="modal-sm"
    submitText="Simpan Status"
>
    <x-ui.form-select name="status_draft" label="Status Akhir Draft" :selected="$draft->status_draft" required>
        <option value="draft" @selected($draft->status_draft === 'draft')>Draft</option>
        <option value="submitted" @selected($draft->status_draft === 'submitted')>Submitted</option>
        <option value="batal" @selected($draft->status_draft === 'batal')>Batal</option>
    </x-ui.form-select>
    <div class="text-secondary small">
        <i class="ti ti-alert-circle me-1"></i> "Batal" menandai draft tidak akan diproses menjadi mahasiswa.
    </div>
</x-ui.form-modal>
