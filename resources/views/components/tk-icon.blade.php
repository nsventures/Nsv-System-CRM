{{-- Taskify v2 — design-system icon (kit thin-stroke SVG set).
     Usage: <x-tk-icon name="star" /> · <x-tk-icon name="star" size="18" class="favorite-icon" data-favorite="1" />
     Any extra attributes (class, data-*, title) pass straight through. --}}
@props(['name', 'size' => 16, 'stroke' => 1.6])
@php
    $icons = [
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
        'eye'       => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'star'      => '<path d="m12 3 2.7 6.3 6.8.6-5.1 4.6 1.5 6.6L12 17.8 6.1 21.1l1.5-6.6L2.5 9.9l6.8-.6Z"/>',
        'pin'       => '<path d="M12 17v5"/><path d="M9 3h6l-1 7 3 2v1H7v-1l3-2-1-7Z"/>',
        'msg'       => '<path d="M21 12a8 8 0 0 1-11.3 7.3L4 21l1.7-5.7A8 8 0 1 1 21 12Z"/>',
        'sitemap'   => '<rect x="9" y="3" width="6" height="5" rx="1"/><rect x="2.5" y="16" width="6" height="5" rx="1"/><rect x="15.5" y="16" width="6" height="5" rx="1"/><path d="M12 8v3M5.5 16v-2.5h13V16"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'moreV'     => '<circle cx="12" cy="5" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.4" fill="currentColor" stroke="none"/>',
        'task'      => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'list'      => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
        'edit'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash'     => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/>',
        'copy'      => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'wallet'    => '<path d="M3 7a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M16 13h2"/>',
        'users'     => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 3-5 6-5s6 2 6 5"/><circle cx="17" cy="9" r="2.5"/><path d="M21 19c0-2-2-3.5-4-3.5"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>',
        'check'     => '<path d="m5 13 4 4 10-10"/>',
        'note'      => '<path d="M5 3h11l3 3v15a0 0 0 0 1 0 0H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M15 3v4h4M8 13h8M8 17h5"/>',
        'kanban'    => '<rect x="3" y="4" width="6" height="16" rx="1"/><rect x="11" y="4" width="6" height="10" rx="1"/><rect x="18.5" y="4" width="2.5" height="13" rx="1"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="M7 14l3-4 3 3 5-7"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
        'branch'    => '<circle cx="6" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="7" r="2.5"/><path d="M6 8.5v7M18 9.5c0 4-6 1.5-6 6"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'flag'      => '<path d="M4 21V4h12l-2 4 2 4H4"/>',
    ];
    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="9"/>';
@endphp
<svg {{ $attributes->merge(['class' => 'tk-ic']) }} width="{{ $size }}" height="{{ $size }}"
    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}"
    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $path !!}</svg>
