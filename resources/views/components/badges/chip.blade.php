@props(['active' => false, 'removable' => false])

<span {{ $attributes->merge(['class' => 'chip ' . ($active ? 'chip-on' : '')]) }}>
    {{ $slot }}
    @if($removable)
        <button type="button" aria-label="Remove" style="margin-left:2px;color:inherit;opacity:0.6;font-size:14px;line-height:1;">×</button>
    @endif
</span>
