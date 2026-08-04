@extends('layouts.tabler.app')

{{-- Perkuliahan module layout — extend this instead of the global layout directly.
     Push module-specific Vite assets here as the module grows. --}}

@push('scripts')
<script>
(function () {
    // Delegated handlers for clone-row forms (kelas-kuliah modal).
    // Inline <script> in AJAX-loaded modal content is NOT executed by core-ajax
    // (.html()), so these live here (rendered in the main layout) and use
    // event delegation — they keep working for any modal opened later.

    function reindex(listSel) {
        var list = document.querySelector(listSel);
        if (!list) return;
        list.querySelectorAll(':scope > .row').forEach(function (row, idx) {
            row.querySelectorAll('[name*="[__IDX__]"]').forEach(function (el) {
                el.name = el.name.replace('__IDX__', idx);
            });
        });
    }

    document.addEventListener('click', function (e) {
        // Add row
        var add = e.target.closest('[data-add-row]');
        if (add) {
            var listSel = add.getAttribute('data-list');
            var tplSel = add.getAttribute('data-tpl');
            var tpl = document.querySelector(tplSel);
            var list = document.querySelector(listSel);
            if (!tpl || !list) return;
            var wrap = document.createElement('div');
            wrap.innerHTML = tpl.innerHTML.trim();
            var row = wrap.firstElementChild;
            list.appendChild(row);
            reindex(listSel);
            // select2 (.js-select2-ajax) inside the clone auto-inits via
            // the MutationObserver in tabler.js — no manual init needed.
            return;
        }

        // Remove row
        var rm = e.target.closest('.remove-row');
        if (rm) {
            var rowEl = rm.closest('.row');
            if (!rowEl) return;
            var parentList = rowEl.parentElement;
            rowEl.remove();
            reindex('#' + parentList.id);
        }
    });
})();
</script>
@endpush
