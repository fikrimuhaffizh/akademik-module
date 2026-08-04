@php
    $item = $kelas_kuliah ?? new \stdClass();
    $method = isset($kelas_kuliah) ? 'PUT' : 'POST';
    $route = isset($kelas_kuliah) ? route('akd.kelas-akd.update', $kelas_kuliah->encrypted_kelas_id) : route('akd.kelas-akd.store');
    $title = isset($kelas_kuliah) ? 'Ubah Kelas Kuliah' : 'Tambah Kelas Kuliah';

    $pembebanan = old('pembebanan', isset($kelas_kuliah) ? $kelas_kuliah->pembebananDosens->map(fn($p) => [
        'pegawai_id' => $p->pegawai_id,
        'nama_dosen' => $p->pegawai?->nama ?? '',
        'peran' => $p->peran,
    ])->toArray() : [['pegawai_id' => '', 'nama_dosen' => '', 'peran' => 'pengampu']]);

    $jadwals = old('jadwals', isset($kelas_kuliah) ? $kelas_kuliah->jadwalKuliahs->map(fn($j) => [
        'hari' => $j->hari, 'jam_mulai' => $j->jam_mulai, 'jam_selesai' => $j->jam_selesai,
        'ruang_id' => $j->ruang_id, 'jenis_pertemuan' => $j->jenis_pertemuan, 'link_online' => $j->link_online,
    ])->toArray() : []);
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="isset($kelas_kuliah) ? 'Update' : 'Simpan'" size="modal-xl">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-select name="penawaran_id" label="Penawaran MK" type="select2" required="true">
                    <option value="">-- Pilih Penawaran --</option>
                    @foreach($penawarans as $p)
                        <option value="{{ $p->penawaran_id }}" {{ old('penawaran_id', $item->penawaran_id ?? '') == $p->penawaran_id ? 'selected' : '' }}>
                            {{ $p->kurikulumMataKuliah?->mataKuliah?->kode }} - {{ $p->kurikulumMataKuliah?->mataKuliah?->nama }}
                        </option>
                    @endforeach
                </x-ui.form-select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-select name="ref_kelas_id" label="Kelas" required="true">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($refKelases as $r)
                        <option value="{{ $r->ref_id }}" {{ old('ref_kelas_id', $item->ref_kelas_id ?? '') == $r->ref_id ? 'selected' : '' }}>{{ $r->label }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-input name="kapasitas" label="Kapasitas" type="number" min="1" value="{{ old('kapasitas', $item->kapasitas ?? '') }}" required="true" />
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <x-ui.form-select name="sistem_kuliah" label="Sistem Kuliah" required="true">
                    <option value="">-- Pilih --</option>
                    @foreach(['reguler' => 'Reguler', 'online' => 'Online', 'hybrid' => 'Hybrid'] as $v => $l)
                        <option value="{{ $v }}" {{ old('sistem_kuliah', $item->sistem_kuliah ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
        </div>
    </div>

    <hr>
    <h3 class="card-title">Dosen Pengampu</h3>
    <div id="pembebanan-list">
        @foreach($pembebanan as $i => $p)
            <div class="row pembebanan-row align-items-end mb-2">
                <div class="col-md-7">
                    <select name="pembebanan[{{ $i }}][pegawai_id]" class="form-select js-select2-ajax" data-placeholder="Cari Dosen (NIP/Nama)..." data-ajax-url="{{ route('hrc.pegawai.search') }}" data-min-length="2">
                        @if($p['pegawai_id'])
                            <option value="{{ $p['pegawai_id'] }}" selected>{{ $p['nama_dosen'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <x-ui.form-select name="pembebanan[{{ $i }}][peran]" label="Peran">
                        @foreach($peranOptions as $v => $l)
                            <option value="{{ $v }}" {{ ($p['peran'] ?? 'pengampu') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-icon mb-3 remove-row" title="Hapus"><i class="ti ti-trash"></i></button>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-pembebanan" data-add-row data-list="#pembebanan-list" data-tpl="#tpl-pembebanan"><i class="ti ti-plus"></i> Tambah Dosen</button>

    <hr>
    <h3 class="card-title">Jadwal Mingguan</h3>
    <div id="jadwal-list">
        @foreach($jadwals as $i => $j)
            <div class="row jadwal-row align-items-end mb-2">
                <div class="col-md-2">
                    <x-ui.form-select name="jadwals[{{ $i }}][hari]" label="Hari">
                        <option value="">--</option>
                        @foreach($hariOptions as $v => $l)
                            <option value="{{ $v }}" {{ ($j['hari'] ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-2">
                    <x-ui.form-input name="jadwals[{{ $i }}][jam_mulai]" label="Jam Mulai" type="time" value="{{ $j['jam_mulai'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <x-ui.form-input name="jadwals[{{ $i }}][jam_selesai]" label="Jam Selesai" type="time" value="{{ $j['jam_selesai'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <x-ui.form-select name="jadwals[{{ $i }}][ruang_id]" label="Ruang">
                        <option value="">-- Pilih --</option>
                        @foreach($ruangKuliahs as $rg)
                            <option value="{{ $rg->ruang_id }}" {{ ($j['ruang_id'] ?? '') == $rg->ruang_id ? 'selected' : '' }}>{{ $rg->kode }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-2">
                    <x-ui.form-select name="jadwals[{{ $i }}][jenis_pertemuan]" label="Jenis">
                        @foreach($jenisPertemuanOptions as $v => $l)
                            <option value="{{ $v }}" {{ ($j['jenis_pertemuan'] ?? 'teori') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-icon mb-3 remove-row" title="Hapus"><i class="ti ti-trash"></i></button>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-jadwal" data-add-row data-list="#jadwal-list" data-tpl="#tpl-jadwal"><i class="ti ti-plus"></i> Tambah Jadwal</button>

    {{-- Template rows (cloned by delegated handler; select2 re-init via MutationObserver) --}}
    <template id="tpl-pembebanan">
        <div class="row pembebanan-row align-items-end mb-2">
            <div class="col-md-7">
                <select name="pembebanan[__IDX__][pegawai_id]" class="form-select js-select2-ajax" data-placeholder="Cari Dosen (NIP/Nama)..." data-ajax-url="{{ route('hrc.pegawai.search') }}" data-min-length="2"></select>
            </div>
            <div class="col-md-4">
                <x-ui.form-select name="pembebanan[__IDX__][peran]" label="Peran">
                    @foreach($peranOptions as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-icon mb-3 remove-row" title="Hapus"><i class="ti ti-trash"></i></button>
            </div>
        </div>
    </template>
    <template id="tpl-jadwal">
        <div class="row jadwal-row align-items-end mb-2">
            <div class="col-md-2">
                <x-ui.form-select name="jadwals[__IDX__][hari]" label="Hari">
                    <option value="">--</option>
                    @foreach($hariOptions as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            <div class="col-md-2">
                <x-ui.form-input name="jadwals[__IDX__][jam_mulai]" label="Jam Mulai" type="time" />
            </div>
            <div class="col-md-2">
                <x-ui.form-input name="jadwals[__IDX__][jam_selesai]" label="Jam Selesai" type="time" />
            </div>
            <div class="col-md-3">
                <x-ui.form-select name="jadwals[__IDX__][ruang_id]" label="Ruang">
                    <option value="">-- Pilih --</option>
                    @foreach($ruangKuliahs as $rg)
                        <option value="{{ $rg->ruang_id }}">{{ $rg->kode }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            <div class="col-md-2">
                <x-ui.form-select name="jadwals[__IDX__][jenis_pertemuan]" label="Jenis">
                    @foreach($jenisPertemuanOptions as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-icon mb-3 remove-row" title="Hapus"><i class="ti ti-trash"></i></button>
            </div>
        </div>
    </template>
</x-ui.form-modal>
