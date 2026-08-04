@php
    $item = $item ?? new \stdClass();
    $method = isset($item->penawaran_id) ? 'PUT' : 'POST';
    $route = isset($item->penawaran_id) ? route('akd.penawaran.update', $item->encrypted_penawaran_id) : route('akd.penawaran.store');
    $title = isset($item->penawaran_id) ? 'Ubah Penawaran MK' : 'Tambah Penawaran MK';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($item->penawaran_id) ? 'Update' : 'Simpan'">
    <div class="mb-3">
        <x-ui.form-select name="periode_akademik_id" label="Periode Akademik" required="true">
            <option value="">-- Pilih Periode --</option>
            @foreach($periodes as $p)
                <option value="{{ $p->periode_akademik_id }}" {{ old('periode_akademik_id', $item->periode_akademik_id ?? '') == $p->periode_akademik_id ? 'selected' : '' }}>{{ $p->nama }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-select name="kurikulum_mata_kuliah_id" label="Mata Kuliah (Kurikulum)" required="true" type="select2">
                    <option value="">-- Pilih Mata Kuliah --</option>
                    @foreach($kurMkOptions as $kurMk)
                        <option value="{{ $kurMk->kur_mk_id }}" {{ old('kurikulum_mata_kuliah_id', $item->kurikulum_mata_kuliah_id ?? '') == $kurMk->kur_mk_id ? 'selected' : '' }}>
                            {{ $kurMk->mataKuliah?->kode }} - {{ $kurMk->mataKuliah?->nama }} (Smstr {{ $kurMk->semester ?? '-' }})
                        </option>
                    @endforeach
                </x-ui.form-select>
            </div>
        </div>
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="prodi_id" label="Prodi ID" type="number" value="{{ old('prodi_id', $item->prodi_id ?? '') }}" required="true" /></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3"><x-ui.form-checkbox name="is_wajib" label="Mata Kuliah Wajib" :checked="old('is_wajib', $item->is_wajib ?? true)" /></div>
        </div>
        <div class="col-md-6">
            <div class="mb-3"><x-ui.form-input name="grup_pilihan" label="Grup Pilihan" value="{{ old('grup_pilihan', $item->grup_pilihan ?? '') }}" placeholder="Kosongkan bila wajib" /></div>
        </div>
    </div>
    <div class="mb-3"><x-ui.form-checkbox name="is_aktif" label="Status Aktif" :checked="old('is_aktif', $item->is_aktif ?? true)" /></div>
</x-ui.form-modal>
