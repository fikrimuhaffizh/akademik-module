@extends('akademik::layouts.akademik-layout')

@section('content')
    <form id="form-biodata" action="{{ isset($row) ? route('akd.biodata.update', encryptId($row->biodata_id)) : route('akd.biodata.store') }}" method="POST">
        @csrf
        @if(isset($row)) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <x-ui.form-input name="mahasiswa_id" label="Mahasiswa ID" type="number" value="{{ $row->mahasiswa_id ?? '' }}" required />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="nik" label="NIK" value="{{ $row->nik ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="tempat_lahir" label="Tempat Lahir" value="{{ $row->tempat_lahir ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="tgl_lahir" label="Tanggal Lahir" type="date" value="{{ $row->tgl_lahir ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-select name="jenis_kelamin" label="Jenis Kelamin" :options="['L'=>'Laki-laki','P'=>'Perempuan']" selected="{{ $row->jenis_kelamin ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="agama" label="Agama" value="{{ $row->agama ?? '' }}" />
            </div>
            <div class="col-md-12">
                <x-ui.form-textarea name="alamat" label="Alamat" value="{{ $row->alamat ?? '' }}" />
            </div>

            {{-- Wilayah cascading Select2 dropdowns --}}
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="provinsi_kode" class="form-label">Provinsi <span class="text-danger">*</span></label>
                    <select name="provinsi_kode" id="provinsi_kode" class="form-select" data-placeholder="Pilih Provinsi...">
                        <option value="">Pilih Provinsi...</option>
                        @if(isset($row) && $row->provinsi_kode)
                            <option value="{{ $row->provinsi_kode }}" selected>{{ $row->provinsi }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="kabupaten_kode" class="form-label">Kabupaten / Kota</label>
                    <select name="kabupaten_kode" id="kabupaten_kode" class="form-select" data-placeholder="Pilih Kabupaten..."
                        {{ !(isset($row) && $row->kabupaten_kode) ? 'disabled' : '' }}>
                        <option value="">Pilih Kabupaten...</option>
                        @if(isset($row) && $row->kabupaten_kode)
                            <option value="{{ $row->kabupaten_kode }}" selected>{{ $row->kabupaten ?? $row->kota }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="kecamatan_kode" class="form-label">Kecamatan</label>
                    <select name="kecamatan_kode" id="kecamatan_kode" class="form-select" data-placeholder="Pilih Kecamatan..."
                        {{ !(isset($row) && $row->kecamatan_kode) ? 'disabled' : '' }}>
                        <option value="">Pilih Kecamatan...</option>
                        @if(isset($row) && $row->kecamatan_kode)
                            <option value="{{ $row->kecamatan_kode }}" selected>{{ $row->kecamatan }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="kelurahan_kode" class="form-label">Kelurahan</label>
                    <select name="kelurahan_kode" id="kelurahan_kode" class="form-select" data-placeholder="Pilih Kelurahan..."
                        {{ !(isset($row) && $row->kelurahan_kode) ? 'disabled' : '' }}>
                        <option value="">Pilih Kelurahan...</option>
                        @if(isset($row) && $row->kelurahan_kode)
                            <option value="{{ $row->kelurahan_kode }}" selected>{{ $row->kelurahan }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <x-ui.form-input name="kode_pos" label="Kode Pos" value="{{ $row->kode_pos ?? '' }}" />
            </div>

            {{-- Hidden inputs for text names (backward compat with display tab) --}}
            <input type="hidden" name="provinsi" id="provinsi_name" value="{{ $row->provinsi ?? '' }}">
            <input type="hidden" name="kabupaten" id="kabupaten_name" value="{{ $row->kabupaten ?? '' }}">
            <input type="hidden" name="kota" id="kota_name" value="{{ $row->kota ?? '' }}">
            <input type="hidden" name="kecamatan" id="kecamatan_name" value="{{ $row->kecamatan ?? '' }}">
            <input type="hidden" name="kelurahan" id="kelurahan_name" value="{{ $row->kelurahan ?? '' }}">

            <div class="col-md-6">
                <x-ui.form-input name="nama_ayah" label="Nama Ayah" value="{{ $row->nama_ayah ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="nama_ibu" label="Nama Ibu" value="{{ $row->nama_ibu ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="pekerjaan_ortu" label="Pekerjaan Orang Tua" value="{{ $row->pekerjaan_ortu ?? '' }}" />
            </div>
            <div class="col-md-6">
                <x-ui.form-input name="penghasilan_ortu" label="Penghasilan Orang Tua" value="{{ $row->penghasilan_ortu ?? '' }}" />
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    window.loadSelect2().then(function ($) {
        var API = '/ref/api/wilayah';

        function toResults(data) {
            return (data.data || []).map(function (item) {
                return { id: item.id, text: item.text };
            });
        }

        function select2Cfg(url, dataFn) {
            var cfg = {
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        var d = { search: params.term || '' };
                        if (dataFn) Object.assign(d, dataFn());
                        return d;
                    },
                    processResults: function (data) {
                        return { results: toResults(data) };
                    },
                    cache: true
                }
            };
            return cfg;
        }

        var $prov     = $('#provinsi_kode');
        var $kab      = $('#kabupaten_kode');
        var $kec      = $('#kecamatan_kode');
        var $kel      = $('#kelurahan_kode');
        var $provName = $('#provinsi_name');
        var $kabName  = $('#kabupaten_name');
        var $kotaName = $('#kota_name');
        var $kecName  = $('#kecamatan_name');
        var $kelName  = $('#kelurahan_name');

        // -- Init selects --
        $prov.select2(select2Cfg(API + '/provinces'));
        $kab.select2(select2Cfg(API + '/regencies', function () {
            return { province_code: $prov.val() || '' };
        }));
        $kec.select2(select2Cfg(API + '/districts', function () {
            return { regency_code: $kab.val() || '' };
        }));
        $kel.select2(select2Cfg(API + '/villages', function () {
            return { district_code: $kec.val() || '' };
        }));

        // -- Sync hidden text inputs on select --
        function syncName($select, $hidden) {
            $select.on('select2:select', function (e) {
                $hidden.val(e.params.data.text);
            });
            $select.on('select2:clear', function () {
                $hidden.val('');
            });
        }
        syncName($prov, $provName);
        syncName($kab, $kabName);
        syncName($kab, $kotaName);   // backward compat: kota = kabupaten name
        syncName($kec, $kecName);
        syncName($kel, $kelName);

        // -- Cascade: parent change resets children --
        $prov.on('select2:select', function () {
            $kab.val(null).trigger('change.select2').prop('disabled', false);
            $kec.val(null).trigger('change.select2').prop('disabled', true);
            $kel.val(null).trigger('change.select2').prop('disabled', true);
        });
        $prov.on('select2:clear', function () {
            $kab.val(null).trigger('change.select2').prop('disabled', true);
            $kec.val(null).trigger('change.select2').prop('disabled', true);
            $kel.val(null).trigger('change.select2').prop('disabled', true);
        });

        $kab.on('select2:select', function () {
            $kec.val(null).trigger('change.select2').prop('disabled', false);
            $kel.val(null).trigger('change.select2').prop('disabled', true);
        });
        $kab.on('select2:clear', function () {
            $kec.val(null).trigger('change.select2').prop('disabled', true);
            $kel.val(null).trigger('change.select2').prop('disabled', true);
        });

        $kec.on('select2:select', function () {
            $kel.val(null).trigger('change.select2').prop('disabled', false);
        });
        $kec.on('select2:clear', function () {
            $kel.val(null).trigger('change.select2').prop('disabled', true);
        });

        // -- Edit mode: enable children that have values --
        @if(isset($row) && $row->kabupaten_kode)
            $kab.prop('disabled', false);
        @endif
        @if(isset($row) && $row->kecamatan_kode)
            $kec.prop('disabled', false);
        @endif
        @if(isset($row) && $row->kelurahan_kode)
            $kel.prop('disabled', false);
        @endif
    });
})();
</script>
@endpush
