<x-ui.form-modal
    title="Set Kurikulum — {{ $draft->nama }}"
    :route="route('akd.mahasiswa-draft.set-kurikulum', encryptId($draft->draft_id))"
    method="PUT"
    size="modal-lg"
    submitText="Simpan Kurikulum"
>
    <div class="mb-3">
        <div class="text-secondary small mb-2">
            NIM <strong>{{ $draft->nim ?? '-' }}</strong> • {{ $draft->prodi?->name ?? '-' }} • Angkatan {{ $draft->angkatan }}
        </div>
        <x-ui.form-select name="kurikulum_kode" label="Kurikulum yang Berlaku" :selected="$draft->kurikulum_kode">
            <option value="">-- Belum ditentukan --</option>
            @foreach(($kurikulumOptions ?? collect()) as $kur)
                <option value="{{ $kur->kode_kurikulum }}" @selected($draft->kurikulum_kode === $kur->kode_kurikulum)>{{ $kur->kode_kurikulum }} — {{ $kur->nama }}</option>
            @endforeach
        </x-ui.form-select>
        <div class="text-secondary small mt-2">
            <i class="ti ti-info-circle me-1"></i> Kosongkan untuk menghapus binding kurikulum; draft tanpa kurikulum tidak bisa di-submit.
        </div>
    </div>
</x-ui.form-modal>
