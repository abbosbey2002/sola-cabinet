/**
 * Data tables: sorting, search, pagination and print (spec §11).
 *
 * Replaces DataTables — the whole month arrives in one response, so all of this
 * is a few operations over an array of <tr> rather than a jQuery plugin.
 *
 * Markup contract:
 *   <div data-table data-page-size="10">
 *     <table>
 *       <thead><tr><th data-sort="date|text|number">…</th></tr></thead>
 *       <tbody><tr><td data-label="Дата" data-value="2026-08-01">01.08.2026</td>…
 *     </table>
 *     [data-table-search] [data-table-print] [data-table-nav]
 *   </div>
 *
 * data-value is the sort key when the visible text is formatted for humans
 * (a d.m.Y date or a thousands-separated number does not sort as a string).
 */

const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

function cellKey(row, index) {
    const cell = row.cells[index];
    if (!cell) return '';

    return cell.dataset.value ?? cell.textContent.trim();
}

function compare(type, a, b) {
    if (type === 'number') return (parseFloat(a) || 0) - (parseFloat(b) || 0);
    if (type === 'date') return String(a).localeCompare(String(b));

    return collator.compare(a, b);
}

function mount(root) {
    if (root.dataset.tableReady === '1') return;
    root.dataset.tableReady = '1';

    const table = root.querySelector('table');
    if (!table?.tBodies[0]) return;

    const all = [...table.tBodies[0].rows];
    const size = Number(root.dataset.pageSize) || 20;

    const nav = root.querySelector('[data-table-nav]');
    const prev = root.querySelector('[data-table-prev]');
    const next = root.querySelector('[data-table-next]');
    const status = root.querySelector('[data-table-status]');
    const search = root.querySelector('[data-table-search]');
    const printButton = root.querySelector('[data-table-print]');

    let visible = all;
    let page = 1;

    const render = () => {
        const pages = Math.max(1, Math.ceil(visible.length / size));
        page = Math.min(page, pages);

        const from = (page - 1) * size;
        const shown = new Set(visible.slice(from, from + size));

        all.forEach((row) => {
            row.hidden = !shown.has(row);
        });

        if (nav) {
            nav.hidden = pages <= 1;
            prev.disabled = page === 1;
            next.disabled = page === pages;

            if (status) {
                status.textContent = (status.dataset.template ?? '{page} / {pages}')
                    .replace('{page}', page)
                    .replace('{pages}', pages);
            }
        }
    };

    prev?.addEventListener('click', () => {
        page -= 1;
        render();
    });

    next?.addEventListener('click', () => {
        page += 1;
        render();
    });

    // Sorting -----------------------------------------------------------
    [...(table.tHead?.rows[0]?.cells ?? [])].forEach((th, index) => {
        const type = th.dataset.sort;
        if (!type) return;

        th.tabIndex = 0;
        th.setAttribute('role', 'button');
        th.classList.add('u-sortable');
        th.setAttribute('aria-sort', 'none');

        const apply = () => {
            const ascending = th.getAttribute('aria-sort') !== 'ascending';

            [...(table.tHead?.rows[0]?.cells ?? [])].forEach((other) => {
                if (other.dataset.sort) other.setAttribute('aria-sort', 'none');
            });
            th.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');

            visible = [...visible].sort((a, b) => {
                const result = compare(type, cellKey(a, index), cellKey(b, index));

                return ascending ? result : -result;
            });

            // Re-seat the rows so the printed sheet follows the visible order.
            visible.forEach((row) => table.tBodies[0].append(row));
            page = 1;
            render();
        };

        th.addEventListener('click', apply);
        th.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                apply();
            }
        });
    });

    // Search ------------------------------------------------------------
    search?.addEventListener('input', () => {
        const needle = search.value.trim().toLowerCase();

        visible = needle
            ? all.filter((row) => row.textContent.toLowerCase().includes(needle))
            : all;

        page = 1;
        render();
    });

    // Print ---------------------------------------------------------------
    // The whole month is already in the DOM; pagination only hides rows. So a
    // print would otherwise put the current ten on the paper and drop the rest
    // without saying so — and that is true of the browser's own Ctrl+P too,
    // which is why this hangs off beforeprint rather than off the button.
    //
    // Every row the search kept is printed, including the pages the subscriber
    // has not clicked through. Rows the search excluded are not: the sheet
    // should be the table they are looking at.
    let hiddenBeforePrint = null;

    window.addEventListener('beforeprint', () => {
        hiddenBeforePrint = all.map((row) => row.hidden);

        const kept = new Set(visible);
        all.forEach((row) => {
            row.hidden = !kept.has(row);
        });
    });

    window.addEventListener('afterprint', () => {
        if (!hiddenBeforePrint) return;

        all.forEach((row, i) => {
            row.hidden = hiddenBeforePrint[i];
        });

        hiddenBeforePrint = null;
    });

    printButton?.addEventListener('click', () => window.print());

    render();
}

/** Mount every table inside a container — used after an AJAX swap too. */
export function mountTables(scope = document) {
    scope.querySelectorAll('[data-table]').forEach(mount);
}

export default function initTables() {
    mountTables();
}
