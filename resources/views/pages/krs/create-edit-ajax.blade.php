@php
    $item = $item ?? new \stdClass();
    $method = isset($item->krs_id) ? 'PUT' : 'POST';
    $route = isset($item->krs_id) ? route('akd.krs.update', $item->encrypted_krs_id) : route('akd.krs.store');
    $title = isset($item->krs_id) ? 'Ubah KRS' : 'Tambah KRS';
    $selectedKelasIds = collect(old('kelas_ids', $selectedKelasIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($item->krs_id) ? 'Update' : 'Simpan'">
    <div class="mb-3"><x-ui.form-input name="mahasiswa_id" label="Mahasiswa ID" type="number" value="{{ old('mahasiswa_id', $item->mahasiswa_id ?? '') }}" required="true" /></div>
    <div class="mb-3">
        <x-ui.form-select name="periode_akademik_id" label="Periode Akademik" required="true">
            <option value="">-- Pilih Periode --</option>
            @foreach($periodes as $p)
                <option value="{{ $p->periode_akademik_id }}" {{ old('periode_akademik_id', $item->periode_akademik_id ?? '') == $p->periode_akademik_id ? 'selected' : '' }}>{{ $p->nama }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="status" label="Status">
            <option value="draft" {{ old('status', $item->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="diajukan" {{ old('status', $item->status ?? '') === 'diajukan' ? 'selected' : '' }}>Diajukan</option>
            <option value="disetujui" {{ old('status', $item->status ?? '') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
            <option value="ditolak" {{ old('status', $item->status ?? '') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
        </x-ui.form-select>
    </div>
    <div class="mb-3">
        <label class="form-label required">Kelas Kuliah</label>
        <select name="kelas_ids[]" class="form-select" multiple required>
            @foreach($kelasKuliahs as $kelas)
                @php
                    $mk = $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah;
                    $periode = $kelas->penawaran?->periodeAkademik;
                @endphp
                <option value="{{ $kelas->kelas_id }}" @selected(in_array($kelas->kelas_id, $selectedKelasIds, true))>
                    {{ $kelas->nama_kelas }} - {{ $mk?->kode }} {{ $mk?->nama }} ({{ $periode?->nama ?? '-' }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-3"><x-ui.form-textarea name="catatan" label="Catatan" rows="3">{{ old('catatan', $item->catatan ?? '') }}</x-ui.form-textarea></div>
</x-ui.form-modal>
