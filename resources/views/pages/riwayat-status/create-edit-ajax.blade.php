@php
    $item = $row ?? new \stdClass();
    $method = isset($row) ? 'PUT' : 'POST';
    $route = isset($row) ? route('akd.riwayat-status.update', encryptId($row->riwayat_status_id)) : route('akd.riwayat-status.store');
    $title = isset($row) ? 'Ubah Riwayat Status' : 'Tambah Riwayat Status';
@endphp
<x-ui.form-modal :title="$title" :route="$route" :method="$method">
    <x-ui.form-input name="tgl_efektif" label="Tanggal Efektif" type="date" value="{{ old('tgl_efektif', $item->tgl_efektif ?? '') }}" required="true" />
    <div class="row">
        <div class="col-md-6">
            <x-ui.form-input name="status_lama" label="Status Lama" value="{{ old('status_lama', $item->status_lama ?? '') }}" required="true" />
        </div>
        <div class="col-md-6">
            <x-ui.form-input name="status_baru" label="Status Baru" value="{{ old('status_baru', $item->status_baru ?? '') }}" required="true" />
        </div>
    </div>
    <x-ui.form-textarea name="alasan" label="Alasan" value="{{ old('alasan', $item->alasan ?? '') }}" />
</x-ui.form-modal>
