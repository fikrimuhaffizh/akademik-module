@php
    $item = $item ?? new \stdClass();
    $method = isset($item->periode_akademik_id) ? 'PUT' : 'POST';
    $route = isset($item->periode_akademik_id) ? route('akd.periode-akademik.update', $item->encrypted_periode_akademik_id) : route('akd.periode-akademik.store');
    $title = isset($item->periode_akademik_id) ? 'Ubah Periode Akademik' : 'Tambah Periode Akademik';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($item->periode_akademik_id) ? 'Update' : 'Simpan'" :modalSize="'modal-lg'">
    <div class="mb-3">
        <x-ui.form-input name="nama" label="Nama Periode" type="text" value="{{ old('nama', $item->nama ?? '') }}" placeholder="Contoh: Semester Ganjil 2024/2025" required="true" />
    </div>
    <div class="row">
        <div class="col-md-4"><div class="mb-3"><x-ui.form-input name="tahun_mulai" label="Tahun Mulai" type="number" min="2000" max="2100" step="1" value="{{ old('tahun_mulai', $item->tahun_mulai ?? '') }}" placeholder="2024" required="true" /></div></div>
        <div class="col-md-4"><div class="mb-3"><x-ui.form-input name="tahun_selesai" label="Tahun Selesai" type="number" min="2000" max="2100" step="1" value="{{ old('tahun_selesai', $item->tahun_selesai ?? '') }}" placeholder="2025" required="true" /></div></div>
        <div class="col-md-4"><div class="mb-3">
            <x-ui.form-select name="semester" label="Semester" required="true">
                <option value="">-- Pilih --</option>
                <option value="ganjil" {{ old('semester', $item->semester ?? '') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                <option value="genap" {{ old('semester', $item->semester ?? '') === 'genap' ? 'selected' : '' }}>Genap</option>
                <option value="pendek" {{ old('semester', $item->semester ?? '') === 'pendek' ? 'selected' : '' }}>Pendek</option>
            </x-ui.form-select>
        </div></div>
    </div>
    <div class="row">
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="tgl_mulai" label="Tanggal Mulai" type="date" value="{{ old('tgl_mulai', isset($item->tgl_mulai) ? $item->tgl_mulai->format('Y-m-d') : '') }}" required="true" /></div></div>
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="tgl_selesai" label="Tanggal Selesai" type="date" value="{{ old('tgl_selesai', isset($item->tgl_selesai) ? $item->tgl_selesai->format('Y-m-d') : '') }}" required="true" /></div></div>
    </div>
    <div class="mb-3"><x-ui.form-checkbox name="is_aktif" label="Status Aktif" :checked="old('is_aktif', $item->is_aktif ?? true)" /></div>
</x-ui.form-modal>
