/* ===========================================================================
   Ikonkalar to'plami.
   Har bir <symbol> Bladeda <x-icon> ning bir "case" i bo'ladi. Hammasi
   currentColor bilan chiziladi — mavzu bilan o'zi almashadi.
   Sinxron joylanadi: skript <body> ning boshida turadi va HTML tahlil
   qilinayotgan paytda ishlaydi, ya'ni ikonkalar birinchi renderdayoq joyida
   bo'ladi, hech qanday "sakrash" bo'lmaydi.
   =========================================================================== */

const SPRITE = `
<svg hidden aria-hidden="true">
    <symbol id="i-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V20h13V9.5"/><path d="M9.5 20v-5.5h5V20"/>
    </symbol>
    <symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 20v-6"/><path d="M13 20V9"/><path d="M18 20v-9"/>
    </symbol>
    <symbol id="i-receipt" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6"/><path d="M9 12h6"/>
    </symbol>
    <symbol id="i-speed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 17a8 8 0 1 1 16 0"/><path d="m12 14 4-4"/>
    </symbol>
    <symbol id="i-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>
    </symbol>
    <symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>
    </symbol>
    <symbol id="i-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="m6 6 12 12"/><path d="m18 6-12 12"/>
    </symbol>
    <symbol id="i-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m6 9 6 6 6-6"/>
    </symbol>
    <symbol id="i-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m9 6 6 6-6 6"/>
    </symbol>
    <symbol id="i-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4"/><path d="M12 17.2v.1"/>
    </symbol>
    <symbol id="i-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>
    </symbol>
    <symbol id="i-minus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <path d="M6 12h12"/>
    </symbol>
    <symbol id="i-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M12 5v14"/><path d="M5 12h14"/>
    </symbol>
    <symbol id="i-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6 7v13h12V7"/><path d="M10 11v5"/><path d="M14 11v5"/>
    </symbol>
    <symbol id="i-view" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 7h9"/><path d="M17 7h3"/><circle cx="15" cy="7" r="2.2"/>
        <path d="M4 17h3"/><path d="M11 17h9"/><circle cx="9" cy="17" r="2.2"/>
    </symbol>
    <symbol id="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
        <circle cx="12" cy="12" r="4"/><path d="M12 2.5v2"/><path d="M12 19.5v2"/><path d="M2.5 12h2"/><path d="M19.5 12h2"/>
        <path d="m5.3 5.3 1.4 1.4"/><path d="m17.3 17.3 1.4 1.4"/><path d="m18.7 5.3-1.4 1.4"/><path d="m6.7 17.3-1.4 1.4"/>
    </symbol>
    <symbol id="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/>
    </symbol>
    <symbol id="i-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2.5" y="4.5" width="19" height="13" rx="2"/><path d="M8 20.5h8"/>
    </symbol>
    <symbol id="i-router" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="13.5" width="18" height="7" rx="2"/><path d="M7 17v.1"/><path d="M12 4.5V9"/>
        <path d="M8.6 6.4a5 5 0 0 1 6.8 0"/>
    </symbol>
    <symbol id="i-tag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3.5 11.5V4.5a1 1 0 0 1 1-1h7l8.5 8.5-8 8-8.5-8.5Z"/><path d="M8 8v.1"/>
    </symbol>
    <symbol id="i-gift" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3.5 9h17v4h-17z"/><path d="M5 13v7h14v-7"/><path d="M12 9v11"/>
        <path d="M12 9S10.5 4 8 4a2 2 0 0 0 0 5"/><path d="M12 9s1.5-5 4-5a2 2 0 0 1 0 5"/>
    </symbol>
    <symbol id="i-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 12a7.5 7.5 0 0 1-11 6.6L4 20l1.4-4.2A7.5 7.5 0 1 1 20 12Z"/>
    </symbol>
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m5 12.5 4.5 4.5L19 7"/>
    </symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 12a8 8 0 1 1-2.6-5.9"/><path d="M20 4v4h-4"/>
    </symbol>
    <symbol id="i-in" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 4v14"/><path d="m6 12 6 6 6-6"/>
    </symbol>
    <symbol id="i-out" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20V6"/><path d="m6 12 6-6 6 6"/>
    </symbol>
</svg>
`;

document.currentScript.insertAdjacentHTML('afterend', SPRITE);
