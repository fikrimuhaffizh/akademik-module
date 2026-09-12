@extends('layouts.' . active_theme() . '.app')

@section('title', 'Monitoring KRS')

@section('content')
    <x-ui.page-header title="Monitoring KRS" pretitle="Akademik" />

    <x-ui.card>
        <x-ui.card-header class="border-bottom">
            <div class="row g-3 align-items-end w-100">
                <div class="col-md-3">
                    <x-ui.form-select
                        name="angkatan"
                        label="Angkatan"
                        placeholder="Semua Angkatan"
                        selected="{{ request('angkatan') }}"
                        onchange="window.location = '{{ route('akd.krs.monitoring') }}' + (this.value ? '?angkatan=' + this.value : '')"
                    >
                        <option value="">Semua Angkatan</option>
                        @foreach($angkatans ?? [] as $ang)
                            <option value="{{ $ang }}">{{ $ang }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                @if($periode)
                    <div class="col-md-9 text-md-end text-secondary">
                        Periode Aktif: <strong>{{ $periode->nama }}</strong>
                    </div>
                @endif
            </div>
        </x-ui.card-header>

        <x-ui.card-body class="p-0">
            @if(!$periode)
                <x-ui.empty-state
                    title="Tidak ada periode akademik aktif"
                    text="Aktifkan periode akademik terlebih dahulu di modul Perkuliahan."
                    icon="ti ti-calendar-off"
                />
            @elseif($data->isEmpty())
                <x-ui.empty-state
                    title="Belum ada data monitoring"
                    text="Belum ada mahasiswa aktif untuk periode & angkatan yang dipilih."
                    icon="ti ti-chart-dots"
                />
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter table-striped card-table">
                        <thead>
                            <tr>
                                <th>Angkatan</th>
                                <th>Prodi</th>
                                <th class="text-center">Total Mhs</th>
                                <th class="text-center">Belum KRS</th>
                                <th class="text-center">Terisi</th>
                                <th class="text-center">Diajukan</th>
                                <th class="text-center">Disetujui</th>
                                <th class="text-center">Total SKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr>
                                    <td>{{ $row['angkatan'] }}</td>
                                    <td>{{ $row['prodi_nama'] }}</td>
                                    <td class="text-center">{{ $row['total'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row['belum'] > 0 ? 'warning' : 'secondary' }}">{{ $row['belum'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $row['terisi'] }}</td>
                                    <td class="text-center">{{ $row['diajukan'] }}</td>
                                    <td class="text-center">{{ $row['disetujui'] }}</td>
                                    <td class="text-center">{{ $row['total_sks'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card-body>
    </x-ui.card>
@endsection
