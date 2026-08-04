@php
    $item = $pembebanan ?? new \stdClass();
    $method = isset($pembebanan) ? 'PUT' : 'POST';
    $route = isset($pembebanan) ? route('akd.pembebanan.update', $pembebanan->encrypted_pembebanan_id) : route('akd.pembebanan.store');
    $title = isset($pembebanan) ? 'Ubah Pembebanan Dosen' : 'Tambah Pembebanan Dosen';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($pembebanan) ? 'Update' : 'Simpan'">
    <div class="mb-3">
        <x-ui.form-select name="kelas_id" label="Kelas Kuliah" required="true">
            <option value="">-- Pilih Kelas --</option>
            @foreach($kelasKuliahs as $k)
                <option value="{{ $k->kelas_id }}" {{ old('kelas_id', $item->kelas_id ?? '') == $k->kelas_id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="pegawai_id" label="Dosen" required="true">
            <option value="">-- Pilih Dosen --</option>
            @foreach($pegawais as $p)
                <option value="{{ $p->pegawai_id }}" {{ old('pegawai_id', $item->pegawai_id ?? '') == $p->pegawai_id ? 'selected' : '' }}>
                    {{ trim(($p->gelar_depan ? $p->gelar_depan . ' ' : '') . $p->nama . ($p->gelar_belakang ? ', ' . $p->gelar_belakang : '')) }}
                </option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="peran" label="Peran" required="true">
            <option value="">-- Pilih Peran --</option>
            @foreach($peranOptions as $value => $label)
                <option value="{{ $value }}" {{ old('peran', $item->peran ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
</x-ui.form-modal>
