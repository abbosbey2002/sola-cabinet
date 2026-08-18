/**
 * Bulk-select for an admin table — a header checkbox that selects every
 * currently visible row, and an action bar that shows how many are checked.
 *
 * Every checkbox and submit button already carries its own name/value/form
 * attribute in the markup (the "form" attribute ties them to a <form> that
 * lives elsewhere in the page — see admin/tariffs.blade.php), so the bulk
 * actions submit correctly with no JavaScript at all. This module only adds
 * the "select all" convenience and the live count, both pure enhancement —
 * without it the action bar just stays on screen and the buttons still work.
 *
 * Markup contract:
 *   <div data-bulk-select>
 *     <input type="checkbox" data-bulk-select-all>
 *     …
 *     <input type="checkbox" data-bulk-item value="839" form="…" name="tariff_ids[]">
 *     …
 *     <div data-bulk-bar>
 *       <span data-bulk-count data-template="{count} tanlandi"></span>
 *       <button type="submit" form="…" name="bulk_action" value="disable">…
 *     </div>
 *   </div>
 *
 * "Select all" only checks rows a sibling [data-table] search/status filter
 * left visible — it must never reach into rows the admin has filtered out.
 */

function mount(root) {
    const selectAll = root.querySelector('[data-bulk-select-all]');
    const items = [...root.querySelectorAll('[data-bulk-item]')];
    const bar = root.querySelector('[data-bulk-bar]');
    const count = bar?.querySelector('[data-bulk-count]');

    if (!items.length) return;

    const refresh = () => {
        const checked = items.filter((item) => item.checked);

        if (selectAll) {
            selectAll.checked = checked.length === items.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < items.length;
        }

        if (bar) bar.hidden = checked.length === 0;

        if (count) {
            count.textContent = (count.dataset.template ?? '{count}').replace('{count}', String(checked.length));
        }
    };

    selectAll?.addEventListener('change', () => {
        items
            .filter((item) => !item.closest('tr')?.hidden)
            .forEach((item) => { item.checked = selectAll.checked; });

        refresh();
    });

    items.forEach((item) => item.addEventListener('change', refresh));

    refresh();
}

export default function initBulkSelect() {
    document.querySelectorAll('[data-bulk-select]').forEach(mount);
}
