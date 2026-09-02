@extends('layouts.' . active_theme() . '.app')

@section('title', 'Rekap EDOM - ' . $kelas->nama_kelas)

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Perkuliahan</div>
            <h2 class="page-title">Rekap EDOM — {{ $kelas->nama_kelas }}</h2>
            <p class="text-muted">{{ $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? '-' }} | {{ $periodeAktif->nama }}</p>
        </div>
        <div class="col-auto">
            <x-ui.button href="{{ route('akd.edom.index') }}" class="btn-ghost-primary" icon="ti ti-arrow-left" text="Kembali" />
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Mahasiswa ID</th>
                        <th>Status</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statuses as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->mahasiswa_id }}</td>
                        <td>
                            @if($item->status === 'selesai')
                                <span class="badge bg-green-lt text-green">Selesai</span>
                            @elseif($item->status === 'sedang_diisi')
                                <span class="badge bg-yellow-lt text-yellow">Sedang Diisi</span>
                            @else
                                <span class="badge bg-red-lt text-red">Belum Mulai</span>
                            @endif
                        </td>
                        <td>{{ formatTanggalIndo($item->waktu_mulai) }}</td>
                        <td>{{ formatTanggalIndo($item->waktu_selesai) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada data EDOM untuk kelas ini</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
