@php
    $item = $item ?? new \stdClass();
    $method = isset($item->kalender_id) ? 'PUT' : 'POST';
    $route = isset($item->kalender_id) ? route('akd.kalender-akademik.update', $item->encrypted_kalender_id) : route('akd.kalender-akademik.store');
    $title = isset($item->kalender_id) ? 'Ubah Kalender Akademik' : 'Tambah Kalender Akademik';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($item->kalender_id) ? 'Update' : 'Simpan'">
    <div class="mb-3">
        <x-ui.form-select name="periode_akademik_id" label="Periode Akademik" required="true">
            <option value="">-- Pilih Periode --</option>
            @foreach($periodes as $p)
                <option value="{{ $p->periode_akademik_id }}" @selected(old('periode_akademik_id', $item->periode_akademik_id ?? '') == $p->periode_akademik_id)>{{ $p->nama }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3"><x-ui.form-input name="nama_kegiatan" label="Nama Kegiatan" type="text" value="{{ old('nama_kegiatan', $item->nama_kegiatan ?? '') }}" placeholder="Contoh: UTS Ganjil 2025" required="true" /></div>
    <div class="row">
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="tgl_mulai" label="Tanggal Mulai" type="date" value="{{ old('tgl_mulai', isset($item->tgl_mulai) ? $item->tgl_mulai->format('Y-m-d') : '') }}" required="true" /></div></div>
        <div class="col-md-6"><div class="mb-3"><x-ui.form-input name="tgl_selesai" label="Tanggal Selesai" type="date" value="{{ old('tgl_selesai', isset($item->tgl_selesai) ? $item->tgl_selesai->format('Y-m-d') : '') }}" required="true" /></div></div>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="jenis" label="Jenis Kegiatan" required="true">
            <option value="">-- Pilih Jenis --</option>
            @foreach(['krs' => 'KRS', 'uts' => 'UTS', 'uas' => 'UAS', 'wisuda' => 'Wisuda', 'libur' => 'Libur', 'lainnya' => 'Lainnya'] as $val => $label)
                <option value="{{ $val }}" @selected(old('jenis', $item->jenis ?? '') == $val)>{{ $label }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3"><x-ui.form-textarea name="keterangan" label="Keterangan" value="{{ old('keterangan', $item->keterangan ?? '') }}" placeholder="Keterangan tambahan (opsional)" /></div>
</x-ui.form-modal>
