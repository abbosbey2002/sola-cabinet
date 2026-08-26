@props(['name', 'size' => 'size-5'])

{{--
    One inline sprite instead of the 20 loose PNGs the old templates loaded.
    Inline, not a CDN icon font: this cabinet is opened on connections that
    drop, and a missing icon font leaves a page of empty boxes.

    Everything is stroked in currentColor, so an icon inherits the colour of
    whatever it sits in and needs no separate asset per theme.

    The stroke is 1.9 rather than the usual 1.6 — at the sizes this interface
    uses, and for the eyes it is drawn for, a hairline icon reads as a smudge.
    A few glyphs that are pure chevrons carry more weight still, otherwise they
    look thinner than their neighbours at the same nominal width.
--}}

@php
    $paths = [
        // Navigation
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V20h13V9.5"/><path d="M9.5 20v-5.5h5V20"/>',
        'chart' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 20v-6"/><path d="M13 20V9"/><path d="M18 20v-9"/>',
        'receipt' => '<path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        'speed' => '<path d="M4 17a8 8 0 1 1 16 0"/><path d="m12 14 4-4"/>',
        'tag' => '<path d="M3.5 11.5V4.5a1 1 0 0 1 1-1h7l8.5 8.5-8 8-8.5-8.5Z"/><path d="M8 8v.1"/>',
        'router' => '<rect x="3" y="13.5" width="18" height="7" rx="2"/><path d="M7 17v.1"/><path d="M12 4.5V9"/><path d="M8.6 6.4a5 5 0 0 1 6.8 0"/>',
        'gift' => '<path d="M3.5 9h17v4h-17z"/><path d="M5 13v7h14v-7"/><path d="M12 9v11"/><path d="M12 9S10.5 4 8 4a2 2 0 0 0 0 5"/><path d="M12 9s1.5-5 4-5a2 2 0 0 1 0 5"/>',
        'chat' => '<path d="M20 12a7.5 7.5 0 0 1-11 6.6L4 20l1.4-4.2A7.5 7.5 0 1 1 20 12Z"/>',

        // Chrome
        'menu' => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'close' => '<path d="m6 6 12 12"/><path d="m18 6-12 12"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.75"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'id' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M7.5 15h9"/><path d="M7.5 11.5h4"/>',
        'logout' => '<path d="M14.5 8.5V6a2 2 0 0 0-2-2h-6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2.5"/><path d="M10 12h10.5m0 0-3-3m3 3-3 3"/>',
        'globe' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.2 2.4 3.3 5.3 3.3 8.5S14.2 18.1 12 20.5c-2.2-2.4-3.3-5.3-3.3-8.5S9.8 5.9 12 3.5z"/>',
        'phone' => '<path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'calendar' => '<rect x="3.5" y="5.5" width="17" height="15" rx="2.5"/><path d="M3.5 10.5h17"/><path d="M8 3.5v4"/><path d="M16 3.5v4"/>',
        'download' => '<path d="M12 4v11m0 0 4-4m-4 4-4-4"/><path d="M4.5 19.5h15"/>',
        'copy' => '<rect x="8.5" y="8.5" width="12" height="12" rx="2"/><path d="M15.5 8.5V5.5A2 2 0 0 0 13.5 3.5h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h3"/>',
        // The sheet feeding out of the machine, because the button opens the
        // print dialog — where "Save as PDF" is one of the destinations.
        'printer' => '<path d="M7 9V4.5h10V9"/><path d="M7 17H5.5A1.5 1.5 0 0 1 4 15.5v-5A1.5 1.5 0 0 1 5.5 9h13a1.5 1.5 0 0 1 1.5 1.5v5a1.5 1.5 0 0 1-1.5 1.5H17"/><path d="M7 14h10v5.5H7Z"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.6-5.9"/><path d="M20 4v4h-4"/>',

        // State — each one is paired with a word in the markup, never used alone
        'check' => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'alert' => '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4"/><path d="M12 17.2v.1"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'minus' => '<path d="M6 12h12"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6 7v13h12V7"/><path d="M10 11v5"/><path d="M14 11v5"/>',

        // Traffic direction
        'in' => '<path d="M12 4v14"/><path d="m6 12 6 6 6-6"/>',
        'out' => '<path d="M12 20V6"/><path d="m6 12 6-6 6 6"/>',

        // View settings: two sliders, sun, moon, and a screen for "follow the system"
        'view' => '<path d="M4 7h9"/><path d="M17 7h3"/><circle cx="15" cy="7" r="2.2"/><path d="M4 17h3"/><path d="M11 17h9"/><circle cx="9" cy="17" r="2.2"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2"/><path d="M12 19.5v2"/><path d="M2.5 12h2"/><path d="M19.5 12h2"/><path d="m5.3 5.3 1.4 1.4"/><path d="m17.3 17.3 1.4 1.4"/><path d="m18.7 5.3-1.4 1.4"/><path d="m6.7 17.3-1.4 1.4"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/>',
        'auto' => '<rect x="2.5" y="4.5" width="19" height="13" rx="2"/><path d="M8 20.5h8"/>',

        // Still referenced by empty states and the old dashboard cards
        'gauge' => '<path d="M4.6 18a9 9 0 1 1 14.8 0"/><path d="m12 14 3.5-3.8"/><circle cx="12" cy="14.5" r="1.3"/>',
        'wallet' => '<path d="M3.5 8.5a2 2 0 0 1 2-2H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5.5a2 2 0 0 1-2-2z"/><path d="M3.5 9.2V7a1.5 1.5 0 0 1 1.2-1.47l9.6-1.9"/><path d="M16.5 13.5h2"/>',
        'mail' => '<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="m3.8 7.2 8.2 5.6 8.2-5.6"/>',
        'pin' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
    ];

    // Chevrons and the bare rules read thin next to the closed shapes at the
    // same nominal stroke, so they carry their own weight.
    $weights = [
        'chevron-down' => '2.2', 'chevron-left' => '2.2', 'chevron-right' => '2.2',
        'check' => '2.2', 'in' => '2.2', 'out' => '2.2', 'minus' => '2.2',
        'menu' => '2', 'close' => '2', 'plus' => '2',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $size.' shrink-0', 'aria-hidden' => 'true', 'focusable' => 'false']) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="{{ $weights[$name] ?? '1.9' }}" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] ?? $paths['alert'] !!}
</svg>
