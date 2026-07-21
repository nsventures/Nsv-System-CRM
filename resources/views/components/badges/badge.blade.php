@props([
    'tone' => 'neutral',  // neutral | primary | ok | warn | err | info
])

@php
    // Map standard bootstrap colors to design system tones
    $mappedTone = match($tone) {
        'success'   => 'ok',
        'danger'    => 'err',
        'warning'   => 'warn',
        'neutral', 'secondary' => 'neutral',
        default     => $tone,
    };
    $cls = ['badge'];
    if ($mappedTone !== 'neutral') $cls[] = 'badge-' . $mappedTone;
@endphp

<span {{ $attributes->merge(['class' => implode(' ', $cls)]) }}>
    {{ $slot }}
</span>
