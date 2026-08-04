@php
    $action = $action ?? route('akd.penawaran.generate');
    $title = $title ?? 'Generate Penawaran dari Kurikulum';
    $kelas = $kelas ?? false;
@endphp
<x-ui.form-modal :title="$title" :route="$action" method="POST" submitText="Generate">
    <div class="mb-3">
        <x-ui.form-select name="periode_akademik_id" label="Periode Akademik" required="true">
            <option value="">-- Pilih Periode --</option>
            @foreach($periodes as $p)
                <option value="{{ $p->periode_akademik_id }}">{{ $p->nama }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <div class="mb-3">
        <x-ui.form-select name="prodi_id" label="Program Studi" required="true">
            <option value="">-- Pilih Prodi --</option>
            @foreach($prodis as $pr)
                <option value="{{ $pr->orgunit_id }}">{{ $pr->name }}</option>
            @endforeach
        </x-ui.form-select>
    </div>
    <p class="text-muted small">Sistem mengambil seluruh mata kuliah dari kurikulum yang di-apply ke prodi ini (sesuai semester ganjil/genap periode), lalu membuat Penawaran. Penawaran yang sudah ada tidak dibuat ulang.</p>
</x-ui.form-modal>
