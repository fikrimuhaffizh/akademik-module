<x-ui.form-modal
    title="Set Kurikulum (Massal)"
    :route="route('akd.mahasiswa-draft.set-kurikulum-bulk')"
    method="PUT"
    size="modal-md"
    submitText="Terapkan"
>
    <input type="hidden" name="draft_ids" id="bulk-kurikulum-draft-ids" value="">
    <div class="text-secondary small mb-3">
        <i class="ti ti-info-circle me-1"></i> Draft terpilih: <strong id="bulk-kurikulum-count">0</strong>.
    </div>

    <x-ui.form-select name="mode" label="Cara Penentuan" required>
        <option value="auto">Otomatis — Ambil kurikulum sesuai program studi dan angkatan</option>
        <option value="manual">Manual — Pilih kurikulum sendiri</option>
    </x-ui.form-select>

    <div id="kur-manual-wrap" class="d-none">
        <div id="kur-loading" class="d-none text-center py-3">
            <div class="spinner-border spinner-border-sm me-2"></div> Memuat kurikulum...
        </div>
        <div id="kur-error" class="alert alert-danger d-none mb-0"></div>
        <div id="kur-select-wrap" class="d-none">
            <div class="text-secondary small mb-2" id="kur-info"></div>
            <select name="kurikulum_kode" id="kur-select" class="form-select" required>
                <option value="">-- Pilih Kurikulum --</option>
            </select>
        </div>
    </div>

    <script>
        (function () {
            const modeSelect = document.querySelector('[name="mode"]');
            const manualWrap = document.getElementById('kur-manual-wrap');
            const loadingEl = document.getElementById('kur-loading');
            const errorEl = document.getElementById('kur-error');
            const selectWrap = document.getElementById('kur-select-wrap');
            const infoEl = document.getElementById('kur-info');
            const selectEl = document.getElementById('kur-select');
            let fetched = false;

            modeSelect?.addEventListener('change', function () {
                const isManual = this.value === 'manual';
                manualWrap.classList.toggle('d-none', !isManual);
                if (isManual && !fetched) {
                    fetchKurikulum();
                }
            });

            function fetchKurikulum() {
                const ids = window._bulkDraftIds || [];
                if (!ids.length) {
                    errorEl.textContent = 'Tidak ada draft terpilih. Centang minimal satu draft di tabel.';
                    errorEl.classList.remove('d-none');
                    return;
                }

                loadingEl.classList.remove('d-none');
                errorEl.classList.add('d-none');
                selectWrap.classList.add('d-none');

                axios.post('{{ route("akd.mahasiswa-draft.kurikulum-options") }}', { draft_ids: ids }, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                }).then(function (res) {
                    const data = res.data?.data;
                    if (!data?.kurikulum?.length) {
                        errorEl.textContent = 'Tidak ada kurikulum aktif untuk prodi "' + (data?.prodi_name || '-') + '" angkatan ' + (data?.angkatan || '-') + '.';
                        errorEl.classList.remove('d-none');
                        return;
                    }
                    infoEl.textContent = 'Prodi: ' + data.prodi_name + ' | Angkatan: ' + data.angkatan;
                    selectEl.innerHTML = '<option value="">-- Pilih Kurikulum --</option>';
                    data.kurikulum.forEach(function (k) {
                        const label = (k.kode_kurikulum || '-') + ' — ' + k.nama + ' (' + k.tahun + ')';
                        selectEl.innerHTML += '<option value="' + k.kode_kurikulum + '">' + label + '</option>';
                    });
                    selectWrap.classList.remove('d-none');
                    fetched = true;
                }).catch(function (err) {
                    const msg = err.response?.data?.message || 'Gagal memuat kurikulum.';
                    errorEl.textContent = msg;
                    errorEl.classList.remove('d-none');
                }).finally(function () {
                    loadingEl.classList.add('d-none');
                });
            }
        })();
    </script>
</x-ui.form-modal>
