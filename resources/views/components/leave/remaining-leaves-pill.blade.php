@props([
    'remaining' => 0,
    'total' => 0,
    'accrued' => null,
    'advanced_paid_leaves' => 0,
    'lowThreshold' => 3,
    'heading' => null,
    'annual' => null,
    'annualRemaining' => null,
])

@php
    $formatNumber = static function ($value) {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    };

    $remainingValue = (float) $remaining;
    $advancedPaidLeaves = (float) ($advanced_paid_leaves ?? 0);
    $accruedTotal = $accrued !== null && $accrued !== '' ? (float) $accrued : null;
    $annualTotal = $annual !== null ? (float) $annual : (float) $total;
    // Display remaining can be negative when there are advanced leaves
    // This shows the actual available balance (remaining - advanced)
    $displayRemainingValue = $remainingValue; // This can be negative
    $status = 'healthy';

    // Status determination: if negative, show as exhausted
    if ($displayRemainingValue < 0) {
        $status = 'exhausted';
    } elseif ($displayRemainingValue <= 0) {
        $status = 'exhausted';
    } elseif ($displayRemainingValue < (float) $lowThreshold) {
        $status = 'low';
    }

    $hintLabels = [
        'healthy' => get_label('remaining_leaves_good_hint', 'Plenty of paid days available.'),
        'low' => get_label('remaining_leaves_low_hint', 'Low balance — double-check before approving as paid.'),
        'exhausted' => get_label('remaining_leaves_exhausted_hint', 'No paid days left. Approvals convert to unpaid automatically.'),
    ];

    $statusLabels = [
        'healthy' => get_label('balance_status_healthy', 'Healthy'),
        'low' => get_label('balance_status_low', 'Low'),
        'exhausted' => get_label('balance_status_exhausted', 'Exhausted'),
    ];

    $statusBadgeClasses = [
        'healthy' => 'bg-label-success text-success',
        'low' => 'bg-label-warning text-warning',
        'exhausted' => 'bg-label-danger text-danger',
    ];

    $tooltipParts = [];
    if ($accruedTotal !== null) {
        $tooltipParts[] = get_label('available_now', 'Available now (accrued)') . ': ' . $formatNumber($remainingValue);
        $tooltipParts[] = get_label('accrued_to_date', 'Accrued to date') . ': ' . $formatNumber($accruedTotal);
    }
    if ($annualTotal > 0) {
        $tooltipParts[] = get_label('annual_allocation', 'Annual allocation') . ': ' . $formatNumber($annualTotal);
    }
    if ($advancedPaidLeaves > 0) {
        $tooltipParts[] = get_label('advanced_paid_leaves', 'Advanced Paid Leaves') . ': ' . $formatNumber($advancedPaidLeaves) . ' ' . get_label('days', 'days');
    }
    $tooltipParts[] = $hintLabels[$status];
    $tooltipText = implode(' • ', $tooltipParts);
@endphp

<div {{ $attributes->class(['d-flex flex-column gap-1']) }}>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted text-uppercase small fw-semibold">{{ $heading ?? get_label('remaining_paid_leaves', 'Remaining Paid Leaves') }}</span>
        @if (!empty($tooltipText))
            <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ e($tooltipText) }}">
                <i class="bx bx-info-circle"></i>
            </button>
        @endif
    </div>
    <h3 class="mb-0 fw-bold d-flex align-items-baseline gap-1">
        <span class="{{ $displayRemainingValue < 0 ? 'text-danger' : '' }}">{{ $formatNumber($displayRemainingValue) }}</span>
        <small class="text-muted fs-5 fw-normal">/ {{ $formatNumber($annualTotal) }}</small>
    </h3>
    @if ($advancedPaidLeaves > 0)
        <div class="d-flex align-items-center gap-2 mt-1">
            <span class="badge rounded-pill bg-label-info text-info">
                <i class="bx bx-info-circle me-1"></i>
                {{ get_label('advanced_paid_leaves', 'Advanced Paid Leaves') }}: {{ $formatNumber($advancedPaidLeaves) }}
                @if ($displayRemainingValue < 0)
                    <span class="ms-1">(<?= get_label('will_recover_on_accrual', 'Will recover on next accrual') ?>)</span>
                @endif
            </span>
        </div>
    @endif
    <span class="badge rounded-pill {{ $statusBadgeClasses[$status] }} mt-2 d-inline-block">{{ $statusLabels[$status] }}</span>
</div>


