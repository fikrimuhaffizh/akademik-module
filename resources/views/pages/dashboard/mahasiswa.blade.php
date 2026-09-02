@extends('layouts.' . active_theme() . '.app')

@section('title', 'Dashboard Saya')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col-auto">
            <span class="avatar avatar-lg rounded bg-blue text-white">
                <i class="ti ti-user fs-1"></i>
            </span>
        </div>
        <div class="col">
            <h2 class="page-title">{{ $mahasiswa->nama }}</h2>
            <div class="page-pretitle">
                {{ $mahasiswa->nim ?? '-' }} • {{ $prodiNama }} • Angkatan {{ $mahasiswa->angkatan }}
            </div>
        </div>
        <div class="col-auto">
            <span class="badge {{ $mahasiswa->status === 'aktif' ? 'bg-green-lt text-green' : 'bg-amber-lt text-amber' }}">
                {{ ucfirst($mahasiswa->status) }}
            </span>
        </div>
    </div>
</div>

{{-- Cekal Alert --}}
@if($cekalAktif)
<div class="alert alert-danger d-flex align-items-center mb-3">
    <i class="ti ti-lock-open me-2 fs-3"></i>
    <div>
        <div class="fw-bold">Anda Tercekal</div>
        <div>{{ $cekalAktif->alasan }} — Jenis: {{ ucfirst($cekalAktif->jenis) }}</div>
    </div>
</div>
@endif

{{-- Cuti Alert --}}
@if($cutiAktif)
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="ti ti-calendar-off me-2 fs-3"></i>
    <div>
        <div class="fw-bold">Pengajuan Cuti Pending</div>
        <div>Pengajuan cuti Anda sedang dalam proses persetujuan.</div>
    </div>
</div>
@endif

<div class="row row-deck row-cards">
    {{-- Ringkasan Akademik --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-blue text-white rounded me-3">
                        <i class="ti ti-school"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">IPK</div>
                        <h3 class="mb-0">{{ number_format($ipk, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-green text-white rounded me-3">
                        <i class="ti ti-chart-line"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">IPS Semester Ini</div>
                        <h3 class="mb-0">{{ number_format($ips, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-cyan text-white rounded me-3">
                        <i class="ti ti-book"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">SKS Lulus</div>
                        <h3 class="mb-0">{{ $sksLulus }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader-icon bg-amber text-white rounded me-3">
                        <i class="ti ti-file-text"></i>
                    </div>
                    <div>
                        <div class="subheader text-muted">KRS Semester Ini</div>
                        <h3 class="mb-0">{{ $krsAktif ? $krsAktif->total_sks . ' SKS' : '-' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Kelulusan --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-bold">Progress Kelulusan</span>
                        <span class="text-muted ms-2">{{ $sksLulus }} / {{ $totalSksKurikulum }} SKS</span>
                    </div>
                    <span class="fw-bold {{ $persentaseKelulusan >= 75 ? 'text-green' : ($persentaseKelulusan >= 50 ? 'text-blue' : 'text-amber') }}">
                        {{ $persentaseKelulusan }}%
                    </span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 6px;">
                    <div class="progress-bar {{ $persentaseKelulusan >= 75 ? 'bg-green' : ($persentaseKelulusan >= 50 ? 'bg-blue' : 'bg-amber') }}"
                         style="width: {{ $persentaseKelulusan }}%; border-radius: 6px;"
                         role="progressbar" aria-valuenow="{{ $persentaseKelulusan }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                <div class="text-muted small mt-1">
                    @if($persentaseKelulusan >= 75)
                        <i class="ti ti-check-circle text-green me-1"></i> Mendekati kelulusan
                    @elseif($persentaseKelulusan >= 50)
                        <i class="ti ti-info-circle text-blue me-1"></i> Setengah jalan
                    @else
                        <i class="ti ti-clock text-amber me-1"></i> Masih dalam proses
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- KRS Status --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-file-text me-2"></i> KRS Aktif</h3>
            </div>
            <div class="card-body">
                @if($krsAktif)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Periode</span>
                        <strong>{{ $periode->nama ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Status</span>
                        {!! status_badge($krsAktif->status) !!}
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total SKS</span>
                        <strong>{{ $krsAktif->total_sks }}</strong>
                    </div>
                    @if($krsAktif->status === 'draft')
                        <a href="{{ route('akd.krs.form', encryptId($mahasiswa->mahasiswa_id)) }}" class="btn btn-primary w-100">
                            <i class="ti ti-edit me-1"></i> Buka KRS
                        </a>
                    @endif
                @else
                    <div class="text-center text-muted py-3">
                        <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                        <p>Belum ada KRS untuk periode ini.</p>
                        @if($periode)
                            <a href="{{ route('akd.krs.form', encryptId($mahasiswa->mahasiswa_id)) }}" class="btn btn-primary">
                                <i class="ti ti-plus me-1"></i> Isi KRS
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Akses Cepat --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-layout-grid me-2"></i> Akses Cepat</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <td><i class="ti ti-file-text text-blue me-2"></i> Isi KRS</td>
                            <td class="text-end">
                                <a href="{{ route('akd.krs.form', encryptId($mahasiswa->mahasiswa_id)) }}" class="btn btn-sm btn-ghost-primary">Buka</a>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="ti ti-school text-green me-2"></i> KHS</td>
                            <td class="text-end">
                                <a href="{{ route('akd.nilai.khs') }}" class="btn btn-sm btn-ghost-primary">Lihat</a>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="ti ti-file text-teal me-2"></i> Transkrip Nilai</td>
                            <td class="text-end">
                                <a href="{{ route('akd.nilai.transkrip') }}" class="btn btn-sm btn-ghost-primary">Lihat</a>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="ti ti-star text-amber me-2"></i> EDOM</td>
                            <td class="text-end">
                                <a href="{{ route('akd.edom-mahasiswa.index') }}" class="btn btn-sm btn-ghost-primary">Isi</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Nilai Terakhir --}}
    @if($nilaiTerakhir->isNotEmpty())
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-clipboard-check me-2"></i> Nilai Terakhir</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Mata Kuliah</th>
                            <th class="text-center">SKS</th>
                            <th class="text-center">Nilai</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nilaiTerakhir as $n)
                        <tr>
                            <td>{{ $n->mataKuliah?->nama ?? '-' }}</td>
                            <td class="text-center">{{ $n->sks }}</td>
                            <td class="text-center fw-bold">{{ $n->nilai_huruf ?? '-' }}</td>
                            <td class="text-center">
                                @if($n->is_lulus)
                                    <span class="badge bg-green-lt text-green">Lulus</span>
                                @else
                                    <span class="badge bg-red-lt text-red">Belum</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
