@php
    use Carbon\Carbon;
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-history me-2"></i>Riwayat Status</h3>
    </div>
    <div class="card-body">
        @if(($tabData['records'] ?? collect())->isEmpty())
            <x-ui.empty-state icon="ti ti-history-off" title="Belum ada riwayat" description="Belum ada perubahan status tercatat untuk mahasiswa ini." />
        @else
            <div class="timeline">
                @foreach($tabData['records'] as $record)
                    <div class="timeline-event">
                        <div class="timeline-event-icon bg-{{ $record->status_baru === 'aktif' ? 'success' : ($record->status_baru === 'do' ? 'danger' : 'primary') }}-lt">
                            <i class="ti ti-arrow-right"></i>
                        </div>
                        <div class="timeline-event-card card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="status status-secondary">{{ ucfirst(str_replace('_', ' ', $record->status_lama)) }}</span>
                                        <i class="ti ti-arrow-right mx-2 text-secondary"></i>
                                        <span class="status status-primary">{{ ucfirst(str_replace('_', ' ', $record->status_baru)) }}</span>
                                    </div>
                                    <span class="text-secondary small">{{ formatTanggalIndo($record->tgl_efektif) }}</span>
                                </div>
                                @if($record->alasan)
                                    <p class="text-secondary mt-2 mb-0">{{ $record->alasan }}</p>
                                @endif
                                <div class="text-secondary small mt-1">Diproses oleh: {{ $record->diproses_oleh ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
