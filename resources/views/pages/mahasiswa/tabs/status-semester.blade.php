<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-calendar-check me-2"></i>Status Semester</h3>
    </div>
    <div class="card-body">
        @if(($tabData['records'] ?? collect())->isEmpty())
            <x-ui.empty-state icon="ti ti-calendar-off" title="Belum ada data" description="Status semester belum tercatat untuk mahasiswa ini." />
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Semester Ke</th>
                            <th>Periode Akademik</th>
                            <th>Status</th>
                            <th class="text-end">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tabData['records'] as $record)
                            <tr>
                                <td>{{ $record->semester_ke }}</td>
                                <td>{{ $record->periode_akademik_id }}</td>
                                <td>
                                    <span class="status status-{{ $record->status === 'aktif' ? 'success' : ($record->status === 'cuti' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                                <td class="text-end text-secondary">{{ $record->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
