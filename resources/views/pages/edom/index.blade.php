@extends('akademik::layouts.akademik-layout')

@section('title', 'Pengisian EDOM')

@section('header')
    <x-ui.page-header title="Pengisian EDOM" pretitle="Evaluasi Dosen Mengajar">
        <x-slot:actions>
            @if(isset($survei) && $survei && isset($kelasList) && $kelasList->isNotEmpty())
                <a href="{{ route('srv.public.welcome', ['slug' => $survei->slug]) }}" class="btn btn-outline-secondary" target="_blank">
                    <i class="ti ti-info-circle me-1"></i> Tentang EDOM
                </a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @if(isset($pesan))
        <x-ui.card>
            <x-ui.card-body>
                <div class="empty">
                    <div class="empty-icon"><i class="ti ti-mood-empty fs-1"></i></div>
                    <p class="empty-title">Belum Tersedia</p>
                    <p class="empty-subtitle text-secondary">{{ $pesan }}</p>
                </div>
            </x-ui.card-body>
        </x-ui.card>
    @else
        <x-ui.card>
            <x-ui.card-body>
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Periode EDOM</div>
                        <div class="datagrid-content">{{ $event->nama_kegiatan }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Jadwal</div>
                        <div class="datagrid-content">{{ $event->tgl_mulai?->format('d M Y') }} s.d. {{ $event->tgl_selesai?->format('d M Y') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Mahasiswa</div>
                        <div class="datagrid-content">{{ $mahasiswa->nama }} ({{ $mahasiswa->nim ?? '-' }})</div>
                    </div>
                </div>
            </x-ui.card-body>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <h3 class="card-title">Daftar Kelas yang Diikuti</h3>
                <span class="text-secondary ms-2">Isi EDOM satu kali untuk setiap mata kuliah</span>
            </x-ui.card-header>
            <x-ui.card-body>
                <div class="list-group list-group-flush">
                    @forelse($kelasList ?? [] as $kelas)
                        @php
                            $mk = $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah;
                            $status = $kelas->edom_status?->status;
                            $selesai = $status === 'selesai';
                            $badge = $selesai ? 'status-success' : 'status-warning';
                            $label = $selesai ? 'Selesai' : 'Belum Diisi';
                        @endphp
                        <div class="list-group-item px-0">
                            <div class="row align-items-center g-2">
                                <div class="col">
                                    <div class="fw-bold">{{ $mk?->nama ?? '-' }}</div>
                                    <div class="text-secondary small">
                                        {{ $mk?->kode ?? '-' }} • Kelas {{ $kelas->nama_kelas ?? '-' }}
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Dosen:
                                        @foreach($kelas->pembebananDosens as $p)
                                            <span class="badge bg-blue-lt me-1">{{ $p->pegawai?->nama ?? '-' }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-auto text-end">
                                    <span class="status {{ $badge }}">{{ $label }}</span>
                                    <div class="mt-2">
                                        @if($selesai)
                                            <span class="text-secondary small">Terima kasih sudah mengisi.</span>
                                        @elseif($kelas->edom_link)
                                            <a href="{{ $kelas->edom_link }}" class="btn btn-primary btn-sm">
                                                <i class="ti ti-edit me-1"></i> Isi EDOM
                                            </a>
                                        @else
                                            <span class="text-secondary small">Survei belum tersedia.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-bookmark fs-1"></i></div>
                            <p class="empty-title">Tidak ada kelas</p>
                            <p class="empty-subtitle text-secondary">Anda tidak memiliki kelas kuliah di periode EDOM ini.</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card-body>
        </x-ui.card>
    @endif
@endsection
