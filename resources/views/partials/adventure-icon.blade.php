{{-- Decorative stage glyphs. Every icon is aria-hidden: the stage name next to
     it is the accessible text, so announcing the icon would duplicate it. --}}
@php($a = ['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'])
<svg @foreach ($a as $k => $v) {{ $k }}="{{ $v }}" @endforeach aria-hidden="true" focusable="false">
    @switch($name)
        @case('stack')
            <path d="M12 3l8 4.5-8 4.5-8-4.5L12 3z"/><path d="M4 12l8 4.5 8-4.5"/><path d="M4 16.5L12 21l8-4.5"/>
            @break
        @case('shield')
            <path d="M12 3l7 3v5.5c0 4-3 7.2-7 8.5-4-1.3-7-4.5-7-8.5V6l7-3z"/><path d="M9 12l2 2 4-4"/>
            @break
        @case('server')
            <rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.5h.01M7 16.5h.01"/>
            @break
        @case('spark')
            <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M18.6 15.4l.6 1.6 1.6.6-1.6.6-.6 1.6-.6-1.6-1.6-.6 1.6-.6.6-1.6z"/>
            @break
        @case('chat')
            <path d="M20 11.5a7.5 7.5 0 01-11 6.6L4 19.5l1.4-4.2A7.5 7.5 0 1120 11.5z"/>
            @break
        @case('card')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/>
            @break
        @case('bolt')
            <path d="M13 3l-8 10h6l-1 8 8-10h-6l1-8z"/>
            @break
        @case('target')
            <circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1"/>
            @break
    @endswitch
</svg>
