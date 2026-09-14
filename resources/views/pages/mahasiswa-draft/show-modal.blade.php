<x-ui.form-modal
    title="Detail Draft — {{ $draft->nama }}"
    method="none"
    size="modal-xl"
    hideFooter="{{ false }}"
>
    @php
        $kandidat = $draft->snapshot_json['kandidat'] ?? [];
        $pendaftaran = $draft->snapshot_json['pendaftaran'] ?? [];
    @endphp

    {{-- ── Section: Akademik (editable) ── --}}
    <div class="mb-4">
        <h4 class="section-title mb-3">
            <i class="ti ti-school me-1"></i> Akademik
        </h4>
        <form action="{{ route('akd.mahasiswa-draft.update', $draft->draft_id) }}" method="POST" class="ajax-form" data-reload-page="true">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <x-ui.form-input name="nim" label="NIM" :value="$draft->nim" placeholder="NIM" />
                </div>
                <div class="col-md-8">
                    <x-ui.form-input name="nama" label="Nama Lengkap" :value="$draft->nama" placeholder="Nama Lengkap" />
                </div>
                <div class="col-md-6">
                    <x-ui.form-input name="email" type="email" label="Email" :value="$draft->email" help="Dipakai untuk akun login mahasiswa." />
                </div>
                <div class="col-md-6">
                    <x-ui.form-select name="kurikulum_kode" label="Kurikulum" :selected="$draft->kurikulum_kode">
                        <option value="">-- Belum ditentukan --</option>
                        @foreach(($kurikulumOptions ?? collect()) as $kur)
                            <option value="{{ $kur->kode_kurikulum }}" @selected($draft->kurikulum_kode === $kur->kode_kurikulum)>{{ $kur->kode_kurikulum }} — {{ $kur->nama }}</option>
                        @endforeach
                    </x-ui.form-select>
                </div>
                <div class="col-md-4">
                    <x-ui.form-input label="No. Pendaftaran" :value="($pendaftaran['no_pendaftaran'] ?? '-')" readonly />
                </div>
                <div class="col-md-4">
                    <x-ui.form-input label="Program Studi" :value="$draft->prodi?->name ?? '-'" readonly />
                </div>
                <div class="col-md-4">
                    <x-ui.form-input label="Angkatan" :value="$draft->angkatan" readonly />
                </div>
                <div class="col-md-4">
                    <x-ui.form-input label="Jenis Masuk" :value="ucfirst($draft->jenis_masuk ?? '-')" readonly />
                </div>
                <div class="col-md-4">
                    <x-ui.form-input label="Sistem Kuliah" :value="ucfirst($draft->sistem_kuliah ?? '-')" readonly />
                </div>
                <div class="col-md-4">
                    <x-ui.form-input label="Status" :value="ucfirst($draft->status_draft)" readonly />
                </div>
            </div>

            <div class="mt-3">
                <x-ui.button type="submit" icon="ti ti-device-floppy" text="Simpan" />
            </div>
        </form>
    </div>

    <hr class="my-4">

    {{-- ── Section: Biodata (readonly dari snapshot PMB) ── --}}
    <div class="mb-4">
        <h4 class="section-title mb-3">
            <i class="ti ti-user me-1"></i> Biodata
        </h4>
        @php
            $biodataSections = [
                'Data Pribadi' => [
                    'NIK' => $kandidat['nik'] ?? null,
                    'Tempat Lahir' => $kandidat['tempat_lahir'] ?? null,
                    'Tanggal Lahir' => $kandidat['tanggal_lahir'] ?? null,
                    'Jenis Kelamin' => $kandidat['jenis_kelamin'] ?? null,
                    'Agama' => $kandidat['agama'] ?? null,
                    'Kewarganegaraan' => $kandidat['kewarganegaraan'] ?? null,
                    'Suku' => $kandidat['suku'] ?? null,
                    'Alamat' => $kandidat['alamat_lengkap'] ?? $kandidat['alamat'] ?? null,
                ],
                'Ayah' => [
                    'Nama' => $kandidat['nama_ayah'] ?? null,
                    'NIK' => $kandidat['nik_ayah'] ?? null,
                    'Pendidikan' => $kandidat['pendidikan_ayah'] ?? null,
                    'Pekerjaan' => $kandidat['pekerjaan_ayah'] ?? null,
                    'Penghasilan' => $kandidat['penghasilan_ayah'] ?? null,
                ],
                'Ibu' => [
                    'Nama' => $kandidat['nama_ibu'] ?? null,
                    'NIK' => $kandidat['nik_ibu'] ?? null,
                    'Pendidikan' => $kandidat['pendidikan_ibu'] ?? null,
                    'Pekerjaan' => $kandidat['pekerjaan_ibu'] ?? null,
                    'Penghasilan' => $kandidat['penghasilan_ibu'] ?? null,
                ],
                'Wali' => [
                    'Nama' => $kandidat['nama_wali'] ?? null,
                    'NIK' => $kandidat['nik_wali'] ?? null,
                    'Pendidikan' => $kandidat['pendidikan_wali'] ?? null,
                    'Pekerjaan' => $kandidat['pekerjaan_wali'] ?? null,
                    'Penghasilan' => $kandidat['penghasilan_wali'] ?? null,
                ],
            ];
        @endphp

        @foreach($biodataSections as $sectionTitle => $fields)
            <h5 class="text-secondary mb-2">{{ $sectionTitle }}</h5>
            <div class="datagrid mb-3">
                @foreach($fields as $label => $value)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ $label }}</div>
                        <div class="datagrid-content">{{ $value ?? '-' }}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <hr class="my-4">

    {{-- ── Section: PMB (readonly metadata pendaftaran) ── --}}
    <div class="mb-3">
        <h4 class="section-title mb-3">
            <i class="ti ti-id-badge me-1"></i> PMB
        </h4>
        <div class="datagrid mb-3">
            <div class="datagrid-item">
                <div class="datagrid-title">No. Pendaftaran</div>
                <div class="datagrid-content">{{ $pendaftaran['no_pendaftaran'] ?? '-' }}</div>

                <div class="datagrid-title">PMB ID</div>
                <div class="datagrid-content">{{ $draft->pmb_pendaftar_id ?? '-' }}</div>

                <div class="datagrid-title">Jalur</div>
                <div class="datagrid-content">{{ $pendaftaran['jalur']['nama_jalur'] ?? '-' }}</div>

                <div class="datagrid-title">Sistem Kuliah (PMB)</div>
                <div class="datagrid-content">{{ $pendaftaran['jalur']['sistem_kuliah'] ?? '-' }}</div>

                <div class="datagrid-title">Status Terkini</div>
                <div class="datagrid-content">{{ $pendaftaran['status_terkini'] ?? '-' }}</div>

                <div class="datagrid-title">Nilai Seleksi</div>
                <div class="datagrid-content">
                    @forelse(($pendaftaran['nilai_seleksi'] ?? []) as $ns)
                        <span class="badge bg-blue-lt me-1">{{ $ns['jenis'] }}: {{ $ns['nilai'] }}</span>
                    @empty
                        -
                    @endforelse
                </div>

                <div class="datagrid-title">Wawancara</div>
                <div class="datagrid-content">
                    @if(isset($pendaftaran['wawancara']))
                        Nilai {{ $pendaftaran['wawancara']['nilai'] ?? '-' }} — {{ $pendaftaran['wawancara']['hasil'] ?? '-' }}
                    @else
                        -
                    @endif
                </div>

                <div class="datagrid-title">Sync Draft</div>
                <div class="datagrid-content">{{ $draft->created_at?->format('d M Y H:i') ?? '-' }}</div>

                @if($draft->submitted_at)
                    <div class="datagrid-title">Submitted</div>
                    <div class="datagrid-content">{{ $draft->submitted_at?->format('d M Y H:i') }}</div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .section-title {
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--tblr-border-color);
        }
    </style>
</x-ui.form-modal>
