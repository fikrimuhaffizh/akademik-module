@php
    $periodeAktif = $stats['periode_aktif'] ?? null;
@endphp

<x-ui.hero-banner
    icon="ti-school"
    label="Portal Akademik"
    label-color="cyan"
    prefix="akd"
    description="Kelola data akademik, jadwal kuliah, KRS, dan monitoring perkuliahan mahasiswa."
    gradient="linear-gradient(135deg, #0ea5e9 0%, #0284c7 40%, #075985 100%)"
    :badges="$periodeAktif ? [['icon' => 'ti-calendar-event', 'text' => $periodeAktif->nama . ' — ' . ucfirst($periodeAktif->semester)]] : []"
    :stats="[
        ['value' => $stats['total_mahasiswa'] ?? 0, 'label' => 'Total Mahasiswa'],
        ['value' => $stats['aktif'] ?? 0, 'label' => 'Aktif'],
        ['value' => $stats['kelas_active'] ?? 0, 'label' => 'Kelas Aktif'],
        ['value' => ($stats['cuti_pending'] ?? 0) + ($stats['transfer_pending'] ?? 0) + ($stats['krs_pending'] ?? 0), 'label' => 'Menunggu Persetujuan'],
    ]"
    stats-key="akd"
/>
