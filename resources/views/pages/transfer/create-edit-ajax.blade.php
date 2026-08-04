@php
    $item = $row ?? new \stdClass();
    $method = isset($row) ? 'PUT' : 'POST';
    $route = isset($row) ? route('akd.transfer.update', encryptId($row->transfer_id)) : route('akd.transfer.store');
    $title = isset($row) ? 'Ubah Transfer' : 'Tambah Transfer';
    $submitText = isset($row) ? 'Update' : 'Simpan';
@endphp

<x-ui.form-modal
    :title="$title"
    :route="$route"
    :method="$method"
    :submitText="$submitText"
>
    <x-ui.form-select name="mahasiswa_id" label="Mahasiswa" url="{{ route('api.akd.mahasiswa.search') }}" type="select2" selected="{{ old('mahasiswa_id', $item->mahasiswa_id ?? '') }}" required="true" />
    <x-ui.form-select name="jenis" label="Jenis Transfer" :options="['masuk'=>'Masuk','keluar'=>'Keluar','pindah_prodi'=>'Pindah Prodi']" selected="{{ old('jenis', $item->jenis ?? '') }}" required="true" />
    <x-ui.form-input name="institusi_asal" label="Institusi Asal" value="{{ old('institusi_asal', $item->institusi_asal ?? '') }}" />
    <x-ui.form-input name="prodi_asal" label="Prodi Asal" value="{{ old('prodi_asal', $item->prodi_asal ?? '') }}" />
    <x-ui.form-input name="sks_diakui" label="SKS Diakui" type="number" min="0" value="{{ old('sks_diakui', $item->sks_diakui ?? '') }}" />
    <x-ui.form-select name="status" label="Status" :options="['pending'=>'Pending','disetujui'=>'Disetujui','ditolak'=>'Ditolak']" selected="{{ old('status', $item->status ?? 'pending') }}" />
</x-ui.form-modal>
