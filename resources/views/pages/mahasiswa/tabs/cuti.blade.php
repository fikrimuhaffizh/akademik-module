@php
    use Carbon\Carbon;
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-bed me-2"></i>Cuti Akademik</h3>
    </div>
    <div class="card-body">
        @if(($tabData['records'] ?? collect())->isEmpty())
            <x-ui.empty-state icon="ti ti-bed-off" title="Tidak ada cuti" description="Mahasiswa ini belum pernah mengajukan cuti akademik." />
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Periode Akademik</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Disetujui Oleh</th>
                            <th>Tanggal Disetujui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tabData['records'] as $record)
                            <tr>
                                <td>{{ $record->periode_akademik_id }}</td>
                                <td>{{ $record->alasan }}</td>
                                <td>
                                    <span class="status status-{{ $record->status === 'disetujui' ? 'success' : ($record->status === 'ditolak' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                                <td>{{ $record->disetujui_oleh ?? '-' }}</td>
                                <td>{{ formatTanggalIndo($record->tgl_disetujui) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
