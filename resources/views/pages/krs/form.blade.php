@extends('akademik::layouts.akademik-layout')

@php
    $krsId = $krs?->encrypted_krs_id;
    $statusKrs = $krs?->status ?? 'belum';
    $totalSks = $krs?->total_sks ?? 0;
@endphp

@section('title', 'Isi KRS — ' . ($mahasiswa->nama ?? ''))

@section('header')
    <x-ui.page-header title="Isi KRS" pretitle="{{ $mahasiswa->nim ?? '' }} — {{ $mahasiswa->nama ?? '' }}">
        <x-slot:actions>
            <a href="{{ route('akd.krs.index') }}" class="btn btn-ghost">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <!-- Info Mahasiswa -->
    <x-ui.card>
        <x-ui.card-body>
            <div class="row g-3 align-items-center">
                <div class="col">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">NIM</div>
                            <div class="datagrid-content">{{ $mahasiswa->nim ?? '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Nama</div>
                            <div class="datagrid-content">{{ $mahasiswa->nama ?? '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Prodi</div>
                            <div class="datagrid-content">{{ $prodiNama ?? '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Angkatan</div>
                            <div class="datagrid-content">{{ $mahasiswa->angkatan ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="text-center px-3 border-start">
                        <div class="text-secondary small">Periode</div>
                        <div class="h3 m-0">{{ $periode->nama ?? '-' }}</div>
                        <div class="mt-2" id="badge-status">{!! status_badge($statusKrs) !!}</div>
                    </div>
                </div>
            </div>
        </x-ui.card-body>
    </x-ui.card>

    @if($statusKrs === 'diajukan' || $statusKrs === 'disetujui')
        <div class="alert alert-info mb-3" role="alert">
            <i class="ti ti-info-circle me-2"></i>
            KRS periode ini sudah <strong>{{ $statusKrs === 'disetujui' ? 'disetujui' : 'diajukan' }}</strong>. Centang mata kuliah tidak dapat diubah.
        </div>
    @endif

    <div class="row g-3">
        <!-- Daftar MK Ditawarkan (checklist) -->
        <div class="col-lg-8">
            <x-ui.card>
                <x-ui.card-header class="border-bottom">
                    <h3 class="card-title">Daftar Mata Kuliah Ditawarkan</h3>
                    <span class="text-secondary ms-2">Centang untuk mengambil</span>
                </x-ui.card-header>
                <x-ui.card-body class="p-0">
                    <div class="table-responsive">
                        <table id="table-krs-kelas" class="table table-sm table-vcenter card-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:1%"></th>
                                    <th>Kode</th>
                                    <th>Mata Kuliah</th>
                                    <th>Kelas</th>
                                    <th class="text-center">SKS</th>
                                    <th class="text-center">Kuota</th>
                                    <th>Ruang</th>
                                    <th>Dosen</th>
                                    <th>Jadwal</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </x-ui.card-body>
            </x-ui.card>
        </div>

        <!-- Cart (Daftar Diambil) -->
        <div class="col-lg-4">
            <x-ui.card class="sticky-top">
                <x-ui.card-header>
                    <h3 class="card-title">Daftar Diambil</h3>
                </x-ui.card-header>
                <x-ui.card-body>
                    <div id="cart-list" class="list-group list-group-flush mb-3">
                        <div class="text-secondary small text-center py-3" id="cart-empty">Belum ada mata kuliah dipilih.</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <div>
                            <div class="text-secondary small">Total SKS</div>
                            <div class="h1 m-0" id="total-sks">{{ $totalSks }}</div>
                        </div>
                        <button type="button" id="btn-ajukan" class="btn btn-success"
                            {{ $statusKrs === 'diajukan' || $statusKrs === 'disetujui' ? 'disabled' : '' }}>
                            <i class="ti ti-send me-1"></i> Ajukan KRS
                        </button>
                    </div>
                    <div class="text-secondary small mt-2">Centang langsung tersimpan (status draft). Kuota dicek saat centang & saat ajukan.</div>
                </x-ui.card-body>
            </x-ui.card>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const MAHASISWA_ID = '{{ encryptId($mahasiswa->mahasiswa_id) }}';
    const KRS_ID = '{{ $krsId ?? '' }}';
    const LOCKED = @json($statusKrs === 'diajukan' || $statusKrs === 'disetujui');
    let currentKrsId = KRS_ID;
    let rows = []; // latest datatable rows (for cart rendering)

    const harianMap = {
        'senin': 'Sen', 'selasa': 'Sel', 'rabu': 'Rab', 'kamis': 'Kam',
        'jumat': 'Jum', 'sabtu': 'Sab', 'minggu': 'Min'
    };
    const fmtHari = (h) => harianMap[String(h).toLowerCase()] ?? h;

    function renderJadwal(jadwal) {
        if (!jadwal || !jadwal.length) return '<span class="text-secondary">-</span>';
        return jadwal.map(j =>
            `<span class="badge bg-blue-lt me-1">${fmtHari(j.hari)} ${j.jam_mulai}-${j.jam_selesai}</span>`
        ).join('');
    }

    function kuotaBadge(row) {
        const terisi = parseInt(row.terisi ?? 0, 10);
        const kapasitas = parseInt(row.kapasitas ?? 0, 10);
        if (kapasitas <= 0) return '<span class="text-secondary">-</span>';
        const sisa = Math.max(0, kapasitas - terisi);
        const cls = sisa <= 0 ? 'status-danger' : (sisa <= Math.ceil(kapasitas * 0.2) ? 'status-warning' : 'status-success');
        return `<span class="status ${cls}">${terisi}/${kapasitas}</span> <span class="text-secondary small">(${sisa} sisa)</span>`;
    }

    function renderRow(row) {
        const ambil = !!row.sudah_ambil;
        const checkbox = LOCKED
            ? (ambil ? '<i class="ti ti-check text-success"></i>' : '<i class="ti ti-x text-secondary"></i>')
            : `<label class="form-check mb-0"><input type="checkbox" class="form-check-input chk-ambil" data-kelas="${row.encrypted_kelas_id}" ${ambil ? 'checked' : ''}></label>`;

        return `<tr data-kelas="${row.encrypted_kelas_id}">
            <td class="text-center">${checkbox}</td>
            <td>${row.kode_mk ?? ''}</td>
            <td><strong>${row.nama_mk ?? '-'}</strong></td>
            <td>${row.nama_kelas ?? '-'}</td>
            <td class="text-center">${row.sks ?? 0}</td>
            <td class="text-center">${kuotaBadge(row)}</td>
            <td>${row.ruang ?? '-'}</td>
            <td>${row.dosen ?? '-'}</td>
            <td>${renderJadwal(row.jadwal)}</td>
        </tr>`;
    }

    function renderCart() {
        const ambilRows = rows.filter(r => r.sudah_ambil);
        const list = document.getElementById('cart-list');
        const empty = document.getElementById('cart-empty');

        list.querySelectorAll('.cart-item').forEach(el => el.remove());

        if (!ambilRows.length) {
            empty.style.display = '';
            document.getElementById('total-sks').textContent = 0;
            return;
        }
        empty.style.display = 'none';

        let total = 0;
        ambilRows.forEach(r => {
            total += parseInt(r.sks ?? 0, 10);
            const item = document.createElement('div');
            item.className = 'list-group-item cart-item px-0 d-flex justify-content-between align-items-center';
            item.innerHTML = `<div><div class="fw-bold">${r.nama_mk ?? '-'}</div><div class="text-secondary small">${r.kode_mk ?? ''} • ${r.nama_kelas ?? ''} • ${r.sks ?? 0} SKS</div></div>`
                + (LOCKED ? '' : `<button type="button" class="btn btn-sm btn-ghost-danger btn-lepas" data-kelas="${r.encrypted_kelas_id}"><i class="ti ti-x"></i></button>`);
            list.appendChild(item);
        });
        document.getElementById('total-sks').textContent = total;
    }

    function muatUlang() {
        return fetch('{{ route('akd.krs.datatable') }}?mahasiswa_id=' + encodeURIComponent(MAHASISWA_ID), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            rows = res.data || [];
            document.querySelector('#table-krs-kelas tbody').innerHTML = rows.map(renderRow).join('');
            renderCart();
        });
    }

    function statusBadge(status) {
        const map = {
            'belum': ['status-secondary', 'Belum'],
            'draft': ['status-secondary', 'Draft'],
            'diajukan': ['status-warning', 'Diajukan'],
            'disetujui': ['status-success', 'Disetujui'],
            'ditolak': ['status-danger', 'Ditolak'],
        };
        const [cls, label] = map[status] || map['belum'];
        return `<span class="status ${cls}">${label}</span>`;
    }

    function setStatus(status, totalSks) {
        document.getElementById('badge-status').innerHTML = statusBadge(status);
        document.getElementById('total-sks').textContent = totalSks ?? 0;
    }

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    function toggle(kelasEnc, ambil) {
        return fetch('{{ route('akd.krs.toggle') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                mahasiswa_id: MAHASISWA_ID,
                kelas_id: kelasEnc,
                ambil: ambil ? 1 : 0
            })
        }).then(r => r.json()).then(res => {
            if (!res.success) throw new Error(res.message || 'Gagal');
            return res;
        });
    }

    // Toggle centang/lepas — delegation pada tabel (elemen stabil, survive re-render)
    document.getElementById('table-krs-kelas').addEventListener('change', function (e) {
        const chk = e.target.closest('.chk-ambil');
        if (!chk) return;
        const kelasEnc = chk.dataset.kelas;
        const ambil = chk.checked;
        chk.disabled = true;
        toggle(kelasEnc, ambil)
            .then(res => {
                currentKrsId = res.encrypted_krs_id || currentKrsId;
                setStatus(res.status || 'draft', res.total_sks ?? 0);
                muatUlang();
            })
            .catch(err => {
                chk.checked = !ambil; // revert
                chk.disabled = false;
                showErrorMessage('Kuota!', err.message);
            });
    });

    // Tombol lepas di cart — delegation pada container cart
    document.getElementById('cart-list').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-lepas');
        if (!btn) return;
        e.preventDefault();
        const kelasEnc = btn.dataset.kelas;
        toggle(kelasEnc, false)
            .then(res => {
                currentKrsId = res.encrypted_krs_id || currentKrsId;
                setStatus(res.status || 'draft', res.total_sks ?? 0);
                muatUlang();
            })
            .catch(err => {
                showErrorMessage('Kesalahan!', err.message);
            });
    });

    document.getElementById('btn-ajukan').addEventListener('click', function () {
        if (!currentKrsId) {
            alert('Belum ada mata kuliah yang diambil.');
            return;
        }
        this.disabled = true;
        fetch('{{ route('akd.krs.ajukan') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ krs_id: currentKrsId })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                this.disabled = false;
                showErrorMessage('Gagal!', data.message || 'Terjadi kesalahan.');
                return;
            }
            setStatus(data.status || 'diajukan', data.total_sks ?? 0);
            showSuccessMessage('Berhasil!', data.message || 'KRS berhasil diajukan.');
            window.location.reload();
        })
        .catch(() => {
            this.disabled = false;
            showErrorMessage('Error!', 'Terjadi kesalahan saat mengajukan KRS.');
        });
    });

    muatUlang();
})();
</script>
@endpush
