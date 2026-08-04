@php
    $item = $jadwal_kuliah ?? new \stdClass();
    $method = isset($jadwal_kuliah) ? 'PUT' : 'POST';
    $route = isset($jadwal_kuliah) ? route('akd.jadwal-akd.update', $jadwal_kuliah->encrypted_jadwal_id) : route('akd.jadwal-akd.store');
    $title = isset($jadwal_kuliah) ? 'Ubah Jadwal Kuliah' : 'Tambah Jadwal Kuliah';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($jadwal_kuliah) ? 'Update' : 'Simpan'">
    <div class="mb-3">
        <x-ui.form-select name="kelas_id" label="Kelas Kuliah" required="true">
            <option value="">-- Pilih Kelas --</option>
            @foreach($kelasKuliahs as $k)
                <option value="{{ $k->kelas_id }}" {{ old('kelas_id', $item->kelas_id ?? '') == $k->kelas_id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <x-ui.form-select name="hari" label="Hari" required="true">
                    <option value="">-- Pilih Hari --</option>
                    @foreach($hariOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('hari', $item->hari ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <x-ui.form-input name="jam_mulai" label="Jam Mulai" type="time" value="{{ old('jam_mulai', $item->jam_mulai ?? '') }}" required="true" />
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <x-ui.form-input name="jam_selesai" label="Jam Selesai" type="time" value="{{ old('jam_selesai', $item->jam_selesai ?? '') }}" required="true" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-select name="metode_pembelajaran" label="Metode Pembelajaran" required="true">
                    <option value="">-- Pilih --</option>
                    <option value="offline" {{ old('metode_pembelajaran', $item->metode_pembelajaran ?? 'offline') == 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="online" {{ old('metode_pembelajaran', $item->metode_pembelajaran ?? '') == 'online' ? 'selected' : '' }}>Online</option>
                    <option value="hybrid" {{ old('metode_pembelajaran', $item->metode_pembelajaran ?? '') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                </x-ui.form-select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-select name="jenis_pertemuan" label="Jenis Pertemuan" required="true">
                    <option value="">-- Pilih --</option>
                    <option value="teori" {{ old('jenis_pertemuan', $item->jenis_pertemuan ?? 'teori') == 'teori' ? 'selected' : '' }}>Teori</option>
                    <option value="praktikum" {{ old('jenis_pertemuan', $item->jenis_pertemuan ?? '') == 'praktikum' ? 'selected' : '' }}>Praktikum</option>
                </x-ui.form-select>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="ruang_id" label="Ruang Kuliah (kosongkan jika online/hybrid)">
            <option value="">-- Pilih Ruang --</option>
            @foreach($ruangKuliahs as $r)
                <option value="{{ $r->ruang_id }}" {{ old('ruang_id', $item->ruang_id ?? '') == $r->ruang_id ? 'selected' : '' }}>{{ $r->kode }} - {{ $r->nama }} ({{ $r->kapasitas }})</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3">
        <x-ui.form-input name="link_online" label="Link Online (opsional)" type="url" value="{{ old('link_online', $item->link_online ?? '') }}" placeholder="https://meet.google.com/..." />
    </div>
</x-ui.form-modal>
