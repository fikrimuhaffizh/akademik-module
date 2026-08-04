@extends('akademik::layouts.akademik-layout')

@section('title', 'Dashboard Akademik')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">Dashboard Akademik</h2>
            <div class="page-pretitle">Overview</div>
        </div>
        @if($stats['periode_aktif'] ?? null)
        <div class="col-auto">
            <span class="badge bg-green-lt text-green">
                <i class="ti ti-calendar-event me-1"></i>
                {{ $stats['periode_aktif']->nama }} — {{ ucfirst($stats['periode_aktif']->semester) }}
            </span>
        </div>
        @endif
    </div>
</div>

<div class="row row-deck row-cards">
    {{-- Total Mahasiswa --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-blue text-white rounded me-3">
                        <i class="ti ti-users"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">Total Mahasiswa</div>
                        <h3 class="mb-0">{{ number_format($stats['total_mahasiswa']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aktif --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-green text-white rounded me-3">
                        <i class="ti ti-user-check"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">Aktif</div>
                        <h3 class="mb-0">{{ number_format($stats['aktif']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lulus --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-teal text-white rounded me-3">
                        <i class="ti ti-medal"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">Lulus</div>
                        <h3 class="mb-0">{{ number_format($stats['lulus']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Calon --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-cyan text-white rounded me-3">
                        <i class="ti ti-user-plus"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">Calon</div>
                        <h3 class="mb-0">{{ number_format($stats['calon']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Actions --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-alert-triangle me-2"></i> Menunggu Proses</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted">KRS Menunggu Persetujuan</td>
                            <td class="text-end fw-bold">{{ $stats['krs_pending'] }}</td>
                            <td class="text-end"><a href="{{ route('akd.krs.index') }}" class="btn btn-sm btn-ghost-primary">Lihat</a></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cuti Menunggu Persetujuan</td>
                            <td class="text-end fw-bold">{{ $stats['cuti_pending'] }}</td>
                            <td class="text-end"><a href="{{ route('akd.cuti.index') }}" class="btn btn-sm btn-ghost-primary">Lihat</a></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Transfer Menunggu Persetujuan</td>
                            <td class="text-end fw-bold">{{ $stats['transfer_pending'] }}</td>
                            <td class="text-end"><a href="{{ route('akd.transfer.index') }}" class="btn btn-sm btn-ghost-primary">Lihat</a></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mahasiswa Tercekal</td>
                            <td class="text-end fw-bold">{{ $stats['tercekal'] }}</td>
                            <td class="text-end"><a href="{{ route('akd.cekal.index') }}" class="btn btn-sm btn-ghost-primary">Lihat</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
