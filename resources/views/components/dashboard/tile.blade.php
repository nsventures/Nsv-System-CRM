@props([
    'id' => '',
    'label' => '',
    'count' => 0,
    'url' => '#',
    'icon' => '',
    'iconBg' => '',
    'linkColor' => '',
    'customCardClass' => '',
    'extraAttributes' => '',
])
{{-- Taskify v2 KPI metric cell (design-system metric-strip). The cell is a
     link so navigation survives; #id + .count keep the dashboard.js binding.
     The trend badge + sparkline are rendered by initMetricSparklines() in
     custom.js, derived deterministically from the live .count value. --}}
<a href="{{ $url }}" class="tk-metric" id="{{ $id }}">
    <div class="tk-metric-row">
        <span class="tk-metric-label">{{ $label }}</span>
        <span class="tk-metric-trend" aria-hidden="true"></span>
    </div>
    <div class="tk-metric-value count">
        <span class="skel" style="width: 32px; height: 24px; display: inline-block; margin-bottom: 0; border-radius: 4px;"></span>
    </div>
    <div class="tk-metric-spark" aria-hidden="true"></div>
</a>
