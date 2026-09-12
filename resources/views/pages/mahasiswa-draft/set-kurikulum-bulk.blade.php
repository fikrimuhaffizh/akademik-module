<x-ui.form-modal
    title="Set Kurikulum (Massal)"
    :route="route('akd.mahasiswa-draft.set-kurikulum-bulk')"
    method="PUT"
    size="modal-lg"
    submitText="Terapkan ke Terpilih"
>
    <input type="hidden" name="draft_ids" id="bulk-kurikulum-draft-ids" value="">
    <div class="text-secondary small mb-3">
        <i class="ti ti-info-circle me-1"></i> Draft terpilih: <strong id="bulk-kurikulum-count">0</strong>. Ubah pilihan di atas datatable untuk mengubah jumlah.
    </div>

    <div class="mb-3">
        <label class="form-label">Cara Penentuan</label>
        <div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="mode" value="auto" id="kur-mode-auto" checked
                       onchange="document.getElementById('kur-manual-wrap').classList.add('d-none')">
                <label class="form-check-label" for="kur-mode-auto">
                    <strong>Otomatis (rekomendasi)</strong> — resolve per draft via Setting Prodi: kurikulum dicari berdasarkan prodi + angkatan tiap draft (kurikulum dan prodi berbagi kode yang sama).
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="mode" value="manual" id="kur-mode-manual"
                       onchange="document.getElementById('kur-manual-wrap').classList.remove('d-none')">
                <label class="form-check-label" for="kur-mode-manual">
                    <strong>Manual</strong> — paksa semua draft terpilih ke satu kurikulum di bawah.
                </label>
            </div>
        </div>
    </div>

    <div id="kur-manual-wrap" class="d-none">
        <x-ui.form-select name="kurikulum_kode" label="Kurikulum" type="select2">
            <option value="">-- Pilih Kurikulum --</option>
            @foreach(($kurikulumOptions ?? collect()) as $kur)
                <option value="{{ $kur->kode_kurikulum }}">{{ $kur->kode_kurikulum }} — {{ $kur->nama }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
</x-ui.form-modal>
