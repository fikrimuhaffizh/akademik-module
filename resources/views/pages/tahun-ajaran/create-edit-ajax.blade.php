@php
    $item = $item ?? new \stdClass();
    $method = isset($item->tahun_ajaran_id) ? 'PUT' : 'POST';
    $route = isset($item->tahun_ajaran_id) ? route('akd.tahun-ajaran.update', $item->encrypted_tahun_ajaran_id) : route('akd.tahun-ajaran.store');
    $title = isset($item->tahun_ajaran_id) ? 'Ubah Tahun Ajaran' : 'Tambah Tahun Ajaran';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($item->tahun_ajaran_id) ? 'Update' : 'Simpan'">
    <div class="mb-3"><x-ui.form-input name="nama" label="Nama Tahun Ajaran" type="text" value="{{ old('nama', $item->nama ?? '') }}" placeholder="Contoh: 2024/2025" required="true" /></div>
    <div class="row">
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="tahun_mulai" label="Tahun Mulai" type="number" value="{{ old('tahun_mulai', $item->tahun_mulai ?? '') }}" required="true" /></div></div>
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="tahun_selesai" label="Tahun Selesai" type="number" value="{{ old('tahun_selesai', $item->tahun_selesai ?? '') }}" required="true" /></div></div>
    </div>
    <div class="mb-3"><x-ui.form-checkbox name="is_aktif" label="Status Aktif" :checked="old('is_aktif', $item->is_aktif ?? true)" /></div>
</x-ui.form-modal>
