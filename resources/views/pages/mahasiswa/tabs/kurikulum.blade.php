@php
    $kurikulum = $tabData['kurikulum'] ?? null;
    $semesters = $tabData['semesters'] ?? [];
    $totalSksKurikulum = $tabData['totalSksKurikulum'] ?? 0;
    $totalSksLulus = $tabData['totalSksLulus'] ?? 0;
    $totalSksBelum = $tabData['totalSksBelum'] ?? 0;
    $ipk = $tabData['ipk'] ?? 0;
@endphp

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0"><i class="ti ti-book me-2"></i>Ringkasan SKS Kuliah Sesuai Kurikulum</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="text-secondary">Kurikulum</div>
            <div class="fw-bold">{{ $kurikulum?->kode_kurikulum ?? '-' }} — {{ $kurikulum?->nama ?? 'Belum diatur' }}</div>
        </div>
        <div class="row text-center">
            <div class="col-md-3">
                <div class="h2 mb-1">{{ $totalSksKurikulum }}</div>
                <div class="text-secondary">Total SKS Minimal</div>
            </div>
            <div class="col-md-3">
                <div class="h2 mb-1 text-success">{{ $totalSksLulus }}</div>
                <div class="text-secondary">SKS Sudah Lulus</div>
            </div>
            <div class="col-md-3">
                <div class="h2 mb-1 {{ $totalSksBelum > 0 ? 'text-danger' : '' }}">{{ $totalSksBelum }}</div>
                <div class="text-secondary">SKS Belum Lulus</div>
            </div>
            <div class="col-md-3">
                <div class="h2 mb-1 text-primary">{{ number_format($ipk, 2) }}</div>
                <div class="text-secondary">IPK</div>
            </div>
        </div>
    </div>
</div>

@if(empty($semesters))
    <div class="card">
        <div class="card-body">
            <x-ui.empty-state icon="ti ti-book-off" title="Belum ada data kurikulum" description="Kurikulum belum diatur untuk mahasiswa ini. Pastikan mahasiswa memiliki kurikulum_kode yang valid dan mapping mata kuliah kurikulum sudah dibuat." />
        </div>
    </div>
@else
    @foreach($semesters as $sem => $items)
        @php
            $totalSksSemester = collect($items)->sum('sks');
        @endphp
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Semester {{ $sem }}</h3>
                <div class="card-actions">
                    {!! badge($totalSksSemester . ' SKS', 'blue') !!}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">No</th>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th class="text-center w-1">Jenis</th>
                            <th class="text-center w-1">SKS</th>
                            <th class="text-center w-1">Nilai</th>
                            <th class="text-center w-1">Bobot</th>
                            <th class="text-center w-1">Lulus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                            @php
                                $mk = $item['mata_kuliah'];
                                $nilai = $item['nilai'];
                                $isLulus = $item['is_lulus'];
                            @endphp
                            <tr>
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td><code>{{ $mk->kode ?? '-' }}</code></td>
                                <td>
                                    {{ $mk->nama }}
                                    @if(! empty($item['grup_pilihan']))
                                        <div class="text-secondary small">Grup pilihan: {{ $item['grup_pilihan'] }}</div>
                                    @endif
                                </td>
                                <td class="text-center">{!! badge($item['is_wajib'] ? 'Wajib' : 'Pilihan', $item['is_wajib'] ? 'green' : 'yellow') !!}</td>
                                <td class="text-center">{{ $item['sks'] }}</td>
                                <td class="text-center">
                                    @if($nilai)
                                        {!! badge($nilai->nilai_huruf, $isLulus ? 'success' : 'warning') !!}
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $nilai ? number_format($nilai->bobot, 2) : '-' }}</td>
                                <td class="text-center">
                                    @if($nilai)
                                        @if($isLulus)
                                            <span class="status status-success"><i class="ti ti-check"></i></span>
                                        @else
                                            <span class="status status-danger"><i class="ti ti-x"></i></span>
                                        @endif
                                    @else
                                        <span class="status status-secondary">Belum</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="4" class="fw-bold text-end">Total SKS Semester {{ $sem }}</td>
                            <td class="text-center fw-bold">{{ $totalSksSemester }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach
@endif
