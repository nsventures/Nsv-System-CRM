@props(['status' => 'progress'])

@php
    // Map database slugs/colors to status-pill states
    $statusKey = strtolower($status);
    $mappedStatus = match($statusKey) {
        'success', 'done', 'completed', 'finished', 'active', 'ok' => 'done',
        'danger', 'blocked', 'cancelled', 'err', 'error' => 'blocked',
        'warning', 'review', 'pending', 'warn' => 'review',
        'primary', 'info', 'progress', 'ongoing', 'todo' => 'progress',
        default => 'progress',
    };

    $cls = 'status status-' . $mappedStatus;
    $dotColor = match($mappedStatus) {
        'progress' => 'var(--signal)',
        'review'   => 'var(--warn)',
        'done'     => 'var(--ok)',
        'blocked'  => 'var(--err)',
        default    => 'var(--fg-3)',
    };
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}>
    <span class="dot" style="background: {{ $dotColor }}"></span>
    {{ $slot }}
</span>
