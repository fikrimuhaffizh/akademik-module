@php
    $item = $row ?? new \stdClass();
    $method = isset($row) ? 'PUT' : 'POST';
    $route = isset($row) ? route('akd.cekal.update', encryptId($row->cekal_id)) : route('akd.cekal.store');
    $title = isset($row) ? 'Ubah Cekal' : 'Tambah Cekal';
    $submitText = isset($row) ? 'Update' : 'Simpan';
@endphp

<x-ui.form-modal
    :title="$title"
    :route="$route"
    :method="$method"
    :submitText="$submitText"
>
    <x-ui.form-select name="mahasiswa_id" label="Mahasiswa" url="{{ route('api.akd.mahasiswa.search') }}" type="select2" selected="{{ old('mahasiswa_id', $item->mahasiswa_id ?? '') }}" required="true" />
    <x-ui.form-select name="jenis" label="Jenis Cekal" :options="['keuangan'=>'Keuangan','akademik'=>'Akademik','administrasi'=>'Administrasi']" selected="{{ old('jenis', $item->jenis ?? '') }}" required="true" />
    <x-ui.form-textarea name="alasan" label="Alasan" value="{{ old('alasan', $item->alasan ?? '') }}" required="true" />
    <x-ui.form-input name="tgl_mulai" label="Tanggal Mulai" type="date" value="{{ old('tgl_mulai', $item->tgl_mulai ?? '') }}" required="true" />
    <x-ui.form-input name="tgl_selesai" label="Tanggal Selesai" type="date" value="{{ old('tgl_selesai', $item->tgl_selesai ?? '') }}" />
</x-ui.form-modal>
