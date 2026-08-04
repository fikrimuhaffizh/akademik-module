@php
    use Carbon\Carbon;
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-ban me-2"></i>Data Cekal</h3>
    </div>
    <div class="card-body">
        @if(($tabData['records'] ?? collect())->isEmpty())
            <x-ui.empty-state icon="ti ti-check" title="Tidak ada cekal" description="Mahasiswa ini tidak memiliki catatan cekal." />
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tabData['records'] as $record)
                            <tr>
                                <td>{!! badge(ucfirst($record->jenis), $record->jenis === 'keuangan' ? 'warning' : ($record->jenis === 'akademik' ? 'danger' : 'info')) !!}</td>
                                <td>{{ $record->alasan }}</td>
                                <td>
                                    @if($record->is_aktif)
                                        <span class="status status-danger">Aktif</span>
                                    @else
                                        <span class="status status-success">Dicabut</span>
                                    @endif
                                </td>
                                <td>{{ $record->tgl_mulai ? Carbon::parse($record->tgl_mulai)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $record->tgl_selesai ? Carbon::parse($record->tgl_selesai)->format('d/m/Y') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
