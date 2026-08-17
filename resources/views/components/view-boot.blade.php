{{--
    Restores the subscriber's theme and text size BEFORE the stylesheet paints.

    This has to be inline and blocking. Deferring it to the bundle means the
    page renders once in the default theme and then repaints — the white flash
    a dark-theme user sees on every navigation.

    Kept deliberately tiny and dependency-free: it reads two keys and sets two
    attributes. Everything else about these preferences lives in
    resources/js/modules/prefs.js.
--}}
<script>
    (() => {
        try {
            const t = localStorage.getItem('sola-theme');
            if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-theme', t);

            const s = localStorage.getItem('sola-text');
            if (s === 'lg' || s === 'xl') document.documentElement.setAttribute('data-text', s);
        } catch (e) {
            // Storage blocked (private mode, cookies off): fall through to the
            // system theme and the base size. Never let this break the page.
        }
    })();
</script>
