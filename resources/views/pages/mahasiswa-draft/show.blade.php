@extends('layouts.' . active_theme() . '.app')

@section('title', 'Detail Draft Mahasiswa')

@section('header')
<x-ui.page-header :title="$draft->nama" pretitle="Mahasiswa Draft">
    <x-slot:actions>
        <a href="{{ route('akd.mahasiswa-draft.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </x-slot:actions>
</x-ui.page-header>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('akd.mahasiswa-draft.update', $draft->draft_id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Academic Info --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Informasi Akademik</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-ui.form-input name="nim" label="NIM" :value="$draft->nim" placeholder="NIM" />
                        </div>
                        <div class="col-md-8">
                            <x-ui.form-input name="nama" label="Nama Lengkap" :value="$draft->nama" placeholder="Nama Lengkap" />
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Program Studi</label>
                                <input type="text" class="form-control" value="{{ $draft->prodi?->name ?? '-' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Angkatan</label>
                                <input type="text" class="form-control" value="{{ $draft->angkatan }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-input name="kurikulum_kode" label="Kurikulum" :value="$draft->kurikulum_kode" placeholder="Kode Kurikulum" />
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Jenis Masuk</label>
                                <input type="text" class="form-control" value="{{ ucfirst($draft->jenis_masuk ?? '-') }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Sistem Kuliah</label>
                                <input type="text" class="form-control" value="{{ ucfirst($draft->sistem_kuliah ?? '-') }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="pt-2">{!! status_badge($draft->status_draft) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <x-ui.button type="submit" icon="ti ti-device-floppy" text="Simpan Perubahan" />
                </div>
            </div>
        </form>

        {{-- Biodata --}}
        @php
            $camaba = $draft->snapshot_json['camaba'] ?? [];
            $pendaftaran = $draft->snapshot_json['pendaftaran'] ?? [];
        @endphp
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Biodata (dari PMB)</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">NIK</label>
                            <input type="text" class="form-control" value="{{ $camaba['nik'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" value="{{ $camaba['tempat_lahir'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="text" class="form-control" value="{{ $camaba['tanggal_lahir'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <input type="text" class="form-control" value="{{ $camaba['jenis_kelamin'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Agama</label>
                            <input type="text" class="form-control" value="{{ $camaba['agama'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="{{ $draft->email ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" class="form-control" value="{{ $draft->no_hp ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" rows="2" readonly>{{ $camaba['alamat'] ?? '-' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Orang Tua --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Orang Tua</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Ayah</h6>
                        <div class="mb-2">
                            <label class="form-label text-secondary small">Nama</label>
                            <input type="text" class="form-control" value="{{ $camaba['nama_ayah'] ?? '-' }}" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary small">Pekerjaan</label>
                            <input type="text" class="form-control" value="{{ $camaba['pekerjaan_ayah'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Ibu</h6>
                        <div class="mb-2">
                            <label class="form-label text-secondary small">Nama</label>
                            <input type="text" class="form-control" value="{{ $camaba['nama_ibu'] ?? '-' }}" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary small">Pekerjaan</label>
                            <input type="text" class="form-control" value="{{ $camaba['pekerjaan_ibu'] ?? '-' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sekolah Asal --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Sekolah Asal</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Nama Sekolah</label>
                            <input type="text" class="form-control" value="{{ $camaba['nama_sekolah'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Jurusan</label>
                            <input type="text" class="form-control" value="{{ $camaba['jurusan_sekolah'] ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tahun Lulus</label>
                            <input type="text" class="form-control" value="{{ $camaba['tahun_lulus'] ?? '-' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nilai Seleksi --}}
        @php
            $nilai = $camaba['nilai_seleksi'] ?? $pendaftaran['nilai_seleksi'] ?? null;
        @endphp
        @if($nilai)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Nilai Seleksi</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($nilai as $key => $value)
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                                <input type="text" class="form-control" value="{{ $value }}" readonly>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $draft->nama }}</h3>
                <p class="text-secondary mb-2">{{ $draft->nim ?? 'NIM belum ditentukan' }}</p>
                <div class="mb-3">{!! status_badge($draft->status_draft) !!}</div>

                <div class="d-grid gap-2">
                    <form action="{{ route('akd.mahasiswa-draft.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="draft_ids[]" value="{{ $draft->draft_id }}">
                        <button type="submit" class="btn btn-primary w-100" {{ $draft->status_draft !== 'draft' ? 'disabled' : '' }}>
                            <i class="ti ti-send me-1"></i> Submit Draft
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Program Studi</span>
                    <span class="fw-medium">{{ $draft->prodi?->name ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Angkatan</span>
                    <span class="fw-medium">{{ $draft->angkatan }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Kurikulum</span>
                    <span class="fw-medium">{{ $draft->kurikulum_kode ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Jenis Masuk</span>
                    <span class="fw-medium">{{ ucfirst($draft->jenis_masuk ?? '-') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Sistem Kuliah</span>
                    <span class="fw-medium">{{ ucfirst($draft->sistem_kuliah ?? '-') }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">PMB ID</span>
                    <span class="fw-medium">{{ $draft->pmb_pendaftar_id }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Dibuat</span>
                    <span class="fw-medium">{{ $draft->created_at?->format('d M Y H:i') ?? '-' }}</span>
                </div>
                @if($draft->submitted_at)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Submitted</span>
                    <span class="fw-medium">{{ $draft->submitted_at?->format('d M Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
