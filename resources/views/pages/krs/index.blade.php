@extends('layouts.' . active_theme() . '.app')

@section('title', 'KRS Mahasiswa')

@section('header')
    <x-ui.page-header title="KRS Mahasiswa" pretitle="Akademik">
        <x-slot:actions>
            @if($mahasiswa && ($isSuperadmin ?? false))
                <a href="{{ route('akd.krs.index', ['clear' => 1]) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-switch me-1"></i> Ganti Mahasiswa
                </a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @if(!$periode)
        <x-ui.card>
            <x-ui.card-body>
                <div class="empty">
                    <div class="empty-icon"><i class="ti ti-calendar-off fs-1"></i></div>
                    <p class="empty-title">Tidak ada periode akademik aktif</p>
                    <p class="empty-subtitle text-secondary">Aktifkan periode akademik terlebih dahulu di modul Perkuliahan.</p>
                </div>
            </x-ui.card-body>
        </x-ui.card>
    @elseif(!$mahasiswa)
        {{-- Pencarian mahasiswa dulu (Superadmin / PA) --}}
        <x-ui.card>
            <x-ui.card-header>
                <h3 class="card-title">Pilih Mahasiswa</h3>
                <span class="text-secondary ms-2">Periode: <strong>{{ $periode->nama }}</strong></span>
            </x-ui.card-header>
            <x-ui.card-body>
                <form id="form-pilih" method="POST" action="{{ route('akd.krs.pilih') }}">
                    @csrf
                    <div class="row align-items-end g-3">
                        <div class="col-md-8">
                            <x-ui.select2-ajax name="mahasiswa_id" id="cari-mahasiswa"
                                placeholder="Ketik NIM atau nama..."
                                :ajaxUrl="route('akd.mahasiswa.search')"
                                :minimumInputLength="2" />
                        </div>
                        <div class="col-md-4">
                            <button type="submit" id="btn-lanjut" class="btn btn-primary w-100" disabled>
                                <i class="ti ti-arrow-right me-1"></i> Lihat KRS
                            </button>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">Superadmin / PA dapat mengisi KRS atas nama mahasiswa. Mahasiswa yang login otomatis melihat data diri sendiri.</div>
                </form>
            </x-ui.card-body>
        </x-ui.card>
    @else
        {{-- Banner periode KRS --}}
        @if($banner)
            @php
                $bannerClass = match($banner['status']) {
                    'aktif' => 'alert-success',
                    'belum_dibuka' => 'alert-info',
                    'berakhir' => 'alert-warning',
                    default => 'alert-secondary',
                };
            @endphp
            <div class="alert {{ $bannerClass }} d-flex align-items-center mb-3" role="alert">
                <i class="ti ti-calendar-event me-2 fs-3"></i>
                <div>
                    <div class="fw-bold">Periode Pengisian KRS — {{ $periode->nama }}</div>
                    <div>{{ $banner['pesan'] }}</div>
                </div>
            </div>
        @endif

        {{-- Info mahasiswa --}}
        <x-ui.card class="mb-3">
            <x-ui.card-body>
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-secondary">Mahasiswa</div>
                        <div class="h3 mb-0">{{ $mahasiswa->nama }} <span class="text-secondary fs-5">(@php echo e($mahasiswa->nim ?? '-') @endphp)</span></div>
                        <div class="text-secondary small">Angkatan {{ $mahasiswa->angkatan }} • NIM {{ $mahasiswa->nim ?? '-' }}</div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('akd.krs.form', encryptId($mahasiswa->mahasiswa_id)) }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Ambil KRS
                        </a>
                    </div>
                </div>
            </x-ui.card-body>
        </x-ui.card>

        {{-- Riwayat KRS (1 row per KRS) --}}
        <x-ui.card>
            <x-ui.card-header>
                <h3 class="card-title">Riwayat Pengisian KRS</h3>
                <span class="text-secondary ms-2">Semua periode yang pernah diisi</span>
            </x-ui.card-header>
            <x-ui.card-body class="p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter card-table mb-0">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Total SKS</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayat as $row)
                                <tr>
                                    <td>{{ $row['periode'] }}</td>
                                    <td class="text-center">{!! status_badge($row['status']) !!}</td>
                                    <td class="text-center"><strong>{{ $row['total_sks'] }}</strong></td>
                                    <td class="text-center">
                                        <a href="{{ route('akd.krs.form', encryptId($mahasiswa->mahasiswa_id)) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye me-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary">Belum ada KRS yang diisi. Klik "Ambil KRS" untuk memulai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card-body>
        </x-ui.card>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('cari-mahasiswa');
    const btn = document.getElementById('btn-lanjut');
    if (!sel || !btn) return;

    sel.addEventListener('change', function () {
        btn.disabled = !this.value;
    });
})();
</script>
@endpush
