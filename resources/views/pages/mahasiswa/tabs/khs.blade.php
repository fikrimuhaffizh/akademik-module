@php
    $byPeriode = $tabData['byPeriode'] ?? collect();
    $ipk = $tabData['ipk'] ?? 0;
    $transkrip = $tabData['transkrip'] ?? collect();
    $totalSksLulus = $transkrip->sum('sks');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <div class="h2 text-primary">{{ number_format($ipk, 2) }}</div>
                <div class="text-secondary">IPK Kumulatif</div>
            </div>
            <div class="col-md-4">
                <div class="h2 text-success">{{ $totalSksLulus }}</div>
                <div class="text-secondary">Total SKS Lulus</div>
            </div>
            <div class="col-md-4">
                <div class="h2">{{ $transkrip->count() }}</div>
                <div class="text-secondary">Mata Kuliah Lulus</div>
            </div>
        </div>
    </div>
</div>

@if($byPeriode->isEmpty())
    <div class="card">
        <div class="card-body">
            <x-ui.empty-state icon="ti ti-report-off" title="Belum ada nilai" description="KHS belum tersedia. Nilai akan muncul setelah dosen mempublikasikan nilai di LMS." />
        </div>
    </div>
@else
    @foreach($byPeriode as $periodeId => $nilaiList)
        @php
            $sksPeriode = $nilaiList->sum('sks');
            $bobotPeriode = $nilaiList->sum(fn($n) => (float)$n->bobot * (int)$n->sks);
            $ips = $sksPeriode > 0 ? round($bobotPeriode / $sksPeriode, 2) : 0;
        @endphp
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Periode {{ $periodeId }}</h3>
                <div class="card-actions">
                    {!! badge($sksPeriode . ' SKS', 'blue') !!}
                    {!! badge('IPS: ' . number_format($ips, 2), 'primary') !!}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">No</th>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th class="text-center w-1">SKS</th>
                            <th class="text-center w-1">Nilai</th>
                            <th class="text-center w-1">Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nilaiList as $i => $nilai)
                            <tr>
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td><code>{{ $nilai->kode_mk ?? '-' }}</code></td>
                                <td>{{ $nilai->nama_mk }}</td>
                                <td class="text-center">{{ $nilai->sks }}</td>
                                <td class="text-center">
                                    {!! badge($nilai->nilai_huruf, $nilai->is_lulus ? 'success' : 'warning') !!}
                                </td>
                                <td class="text-center">{{ number_format($nilai->bobot, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
