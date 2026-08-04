@php
    $item = $row ?? new \stdClass();
    $method = isset($row) ? 'PUT' : 'POST';
    $route = isset($row) ? route('akd.cuti.update', encryptId($row->cuti_id)) : route('akd.cuti.store');
    $title = isset($row) ? 'Ubah Cuti' : 'Ajukan Cuti';
    $submitText = isset($row) ? 'Update' : 'Simpan';
@endphp

<x-ui.form-modal
    :title="$title"
    :route="$route"
    :method="$method"
    :submitText="$submitText"
>
    <x-ui.form-select name="mahasiswa_id" label="Mahasiswa" url="{{ route('api.akd.mahasiswa.search') }}" type="select2" selected="{{ old('mahasiswa_id', $item->mahasiswa_id ?? '') }}" required="true" />
    <x-ui.form-select name="periode_akademik_id" label="Periode Akademik" url="/sys/referensi/periode-akademik" selected="{{ old('periode_akademik_id', $item->periode_akademik_id ?? '') }}" required="true" />
    <x-ui.form-textarea name="alasan" label="Alasan Cuti" value="{{ old('alasan', $item->alasan ?? '') }}" required="true" />
    <x-ui.form-select name="status" label="Status" :options="['pending'=>'Pending','disetujui'=>'Disetujui','ditolak'=>'Ditolak']" selected="{{ old('status', $item->status ?? 'pending') }}" />
</x-ui.form-modal>
