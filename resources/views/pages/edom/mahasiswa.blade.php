@extends('layouts.' . active_theme() . '.app')

@section('title', 'EDOM Saya')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Perkuliahan</div>
            <h2 class="page-title">EDOM Saya — Evaluasi Dosen Mengajar</h2>
        </div>
    </div>
</div>

@if(!$periodeAktif)
<div class="card shadow-sm border-0">
    <div class="card-body text-center py-5">
        <div class="text-muted mb-3"><i class="ti ti-calendar-off ti-lg"></i></div>
        <p class="text-muted">Tidak ada periode EDOM aktif saat ini.</p>
    </div>
</div>
@elseif($list->isEmpty())
<div class="card shadow-sm border-0">
    <div class="card-body text-center py-5">
        <div class="text-muted mb-3"><i class="ti ti-school ti-lg"></i></div>
        <p class="text-muted">Anda belum terdaftar di matakuliah manapun untuk periode ini.</p>
    </div>
</div>
@else
<div class="card shadow-sm border-0">
    <div class="card-header">
        <h3 class="card-title">Daftar Matakuliah — {{ $periodeAktif->nama }}</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><span class="text-muted">{{ $item->kelas?->penawaran?->kurikulumMataKuliah?->mataKuliah?->kode ?? '-' }}</span></td>
                        <td>{{ $item->kelas?->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? '-' }}</td>
                        <td><span class="badge bg-blue-lt text-blue">{{ $item->kelas?->nama_kelas ?? '-' }}</span></td>
                        <td>
                            @if($item->status === 'selesai')
                                <span class="badge bg-green-lt text-green"><i class="ti ti-check"></i> Selesai</span>
                            @elseif($item->status === 'sedang_diisi')
                                <span class="badge bg-yellow-lt text-yellow"><i class="ti ti-pencil"></i> Sedang Diisi</span>
                            @else
                                <span class="badge bg-red-lt text-red"><i class="ti ti-clock"></i> Belum Mulai</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status === 'selesai')
                                <span class="text-muted small">Selesai</span>
                            @else
                                <a href="{{ route('akd.edom.mulai', $item->edom_status_id) }}" class="btn btn-sm btn-success">
                                    <i class="ti ti-player-play"></i> Isi EDOM
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
