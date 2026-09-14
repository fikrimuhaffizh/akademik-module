<x-ui.form-modal
    title="Set Status Akhir — {{ $draft->nama }}"
    :route="route('akd.mahasiswa-draft.set-status', encryptId($draft->draft_id))"
    method="PUT"
    size="modal-sm"
    submitText="Simpan Status"
>
    <x-ui.form-select name="status_draft" label="Status Akhir Draft" :selected="$draft->status_draft" required>
        <option value="draft" @selected($draft->status_draft === 'draft')>Draft</option>
        <option value="terima" @selected($draft->status_draft === 'terima')>Terima</option>
        <option value="batal" @selected($draft->status_draft === 'batal')>Batal</option>
    </x-ui.form-select>

    <div id="status-terima-warning" class="d-none alert alert-warning mb-0 mt-2">
        <i class="ti ti-alert-triangle me-1"></i>
        <strong>Peringatan:</strong> Draft yang dipilih <strong>Terima</strong> akan diproses menjadi mahasiswa aktif dan <strong>tidak akan tampil lagi di halaman ini</strong>.
    </div>

    <div class="text-secondary small mt-2">
        <i class="ti ti-alert-circle me-1"></i> "Batal" menandai draft tidak akan diproses menjadi mahasiswa.
    </div>

    <script>
        document.querySelector('[name="status_draft"]')?.addEventListener('change', function () {
            document.getElementById('status-terima-warning').classList.toggle('d-none', this.value !== 'terima');
        });
    </script>
</x-ui.form-modal>
