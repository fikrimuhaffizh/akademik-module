@extends('akademik::layouts.akademik-layout')

@section('header')
    <x-ui.page-header :title="$mahasiswa->nama" pretitle="Mahasiswa">
        <x-slot:actions>
            <a href="{{ route('akd.mahasiswa.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
<div class="row">
    {{-- Left Sidebar --}}
    <div class="col-lg-3">
        {{-- Student Info Card --}}
        <div class="card mb-3">
            <div class="card-body text-center">
                @if($mahasiswa->foto)
                    <span class="avatar avatar-xl mb-3" style="background-image: url('{{ $mahasiswa->foto }}')"></span>
                @else
                    <span class="avatar avatar-xl mb-3">{{ strtoupper(substr($mahasiswa->nama, 0, 2)) }}</span>
                @endif
                <h3 class="mb-1">{{ $mahasiswa->nama }}</h3>
                <p class="text-secondary mb-2">{{ $mahasiswa->nim }}</p>
                <span class="status status-{{ $mahasiswa->status === 'aktif' ? 'success' : ($mahasiswa->status === 'lulus' ? 'blue' : 'warning') }}">
                    {{ ucfirst(str_replace('_', ' ', $mahasiswa->status)) }}
                </span>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Program Studi</span>
                    <span class="fw-medium">{{ $mahasiswa->prodi_id }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Angkatan</span>
                    <span class="fw-medium">{{ $mahasiswa->angkatan }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Semester Masuk</span>
                    <span class="fw-medium">{{ $mahasiswa->semester_masuk ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="card">
            <div class="list-group list-group-flush">
                @foreach($tabs as $key => $tabInfo)
                    <a href="{{ route('akd.mahasiswa.detail', ['id' => $mahasiswa->encrypted_mahasiswa_id, 'tab' => $key]) }}"
                       class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === $key ? 'active' : '' }}">
                        <i class="{{ $tabInfo['icon'] }} me-2"></i>
                        <span>{{ $tabInfo['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Right Content --}}
    <div class="col-lg-9">
        @include('akademik::pages.mahasiswa.tabs.' . $activeTab, [
            'mahasiswa' => $mahasiswa,
            'biodata' => $biodata,
            'tabData' => $tabData,
        ])
    </div>
</div>
@endsection
