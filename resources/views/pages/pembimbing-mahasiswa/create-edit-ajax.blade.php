@php
    $item = $item ?? new \stdClass();
    $isEdit = isset($item->pma_id);
    $method = $isEdit ? 'PUT' : 'POST';
    $route = $isEdit ? route('akd.pembimbing-mahasiswa.update', $item->encrypted_pma_id) : route('akd.pembimbing-mahasiswa.store');
    $title = $isEdit ? 'Ubah Pembimbing Mahasiswa' : 'Tambah Pembimbing Mahasiswa';

    $mahasiswaOptions = $isEdit && $item->mahasiswa
        ? [encryptId($item->mahasiswa_id) => $item->mahasiswa->nim . ' - ' . $item->mahasiswa->nama]
        : [];
    $pegawaiOptions = $isEdit && $item->pegawai
        ? [encryptId($item->pegawai_id) => $item->pegawai->nama]
        : [];
@endphp

<x-ui.form-modal :title="$title" :route="$route" :method="$method" :submitText="$isEdit ? 'Update' : 'Simpan'">
    <div class="mb-3">
        <label class="form-label required">Mahasiswa</label>
        <x-ui.select2-ajax name="mahasiswa_id" id="mahasiswa_id"
            placeholder="Ketik NIM atau nama mahasiswa..."
            :ajax-url="route('akd.mahasiswa.search')"
            :minimum-input-length="2"
            :options="$mahasiswaOptions" />
        <div class="form-hint">Pilih mahasiswa yang akan diatur pembimbingnya.</div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label required">Jenis Pembimbing</label>
            <select name="jenis_pembimbing" class="form-select" required>
                <option value="">-- Pilih Jenis --</option>
                @foreach($jenisPembimbing as $jp)
                    <option value="{{ $jp->ref_id }}" {{ $isEdit && $item->jenis_pembimbing == $jp->ref_id ? 'selected' : '' }}>{{ $jp->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label required">Periode Akademik</label>
            <select name="periode_akademik_id" class="form-select" required>
                <option value="">-- Pilih Periode --</option>
                @foreach($periodes as $p)
                    <option value="{{ $p->periode_akademik_id }}" {{ $isEdit && $item->periode_akademik_id == $p->periode_akademik_id ? 'selected' : '' }}>{{ $p->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label required">Dosen Pembimbing</label>
        <x-ui.select2-ajax name="pegawai_id" id="pegawai_id"
            placeholder="Cari dosen (NIP/Nama)..."
            :ajax-url="route('hrc.pegawai.search')"
            :minimum-input-length="2"
            :options="$pegawaiOptions" />
    </div>
</x-ui.form-modal>
