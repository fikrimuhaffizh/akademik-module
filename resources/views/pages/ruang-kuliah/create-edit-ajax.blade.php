@php
    $item = $item ?? new \stdClass();
    $method = isset($item->ruang_id) ? 'PUT' : 'POST';
    $route = isset($item->ruang_id) ? route('akd.ruang-akd.update', $item->encrypted_ruang_id) : route('akd.ruang-akd.store');
    $title = isset($item->ruang_id) ? 'Ubah Ruang Kuliah' : 'Tambah Ruang Kuliah';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($item->ruang_id) ? 'Update' : 'Simpan'">
    <div class="row">
        <div class="col-md-4"><div class="mb-3"><x-ui.form-input name="kode" label="Kode Ruang" type="text" value="{{ old('kode', $item->kode ?? '') }}" required="true" /></div></div>
        <div class="col-md-8"><div class="mb-3"><x-ui.form-input name="nama" label="Nama Ruang" type="text" value="{{ old('nama', $item->nama ?? '') }}" required="true" /></div></div>
    </div>
    <div class="row">
        <div class="col-md-4"><div class="mb-3"><x-ui.form-input name="gedung" label="Gedung" type="text" value="{{ old('gedung', $item->gedung ?? '') }}" /></div></div>
        <div class="col-md-4"><div class="mb-3"><x-ui.form-input name="lantai" label="Lantai" type="number" value="{{ old('lantai', $item->lantai ?? '') }}" /></div></div>
        <div class="col-md-4"><div class="mb-3"><x-ui.form-input name="kapasitas" label="Kapasitas" type="number" value="{{ old('kapasitas', $item->kapasitas ?? '') }}" required="true" /></div></div>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="jenis" label="Jenis Ruang" required="true">
            <option value="kelas" {{ old('jenis', $item->jenis ?? '') === 'kelas' ? 'selected' : '' }}>Kelas</option>
            <option value="lab" {{ old('jenis', $item->jenis ?? '') === 'lab' ? 'selected' : '' }}>Lab</option>
            <option value="aula" {{ old('jenis', $item->jenis ?? '') === 'aula' ? 'selected' : '' }}>Aula</option>
            <option value="online" {{ old('jenis', $item->jenis ?? '') === 'online' ? 'selected' : '' }}>Online</option>
        </x-ui.form-select>
    </div>
    <div class="mb-3"><x-ui.form-checkbox name="is_aktif" label="Status Aktif" :checked="old('is_aktif', $item->is_aktif ?? true)" /></div>
</x-ui.form-modal>
