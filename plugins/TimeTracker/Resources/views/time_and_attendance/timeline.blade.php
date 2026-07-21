<div class="timetracker-timeline-progress progress" style="height: 20px;">
    @php
        $timelineStart = $startTime; // 09:00
        $timelineEnd = $endTime;     // 18:00
        $totalMinutes = $timelineEnd->diffInMinutes($timelineStart);
        $colorMap = [
            'active' => 'bg-primary',
            'break' => 'bg-info',
            'idle' => 'bg-secondary',
            'manual' => 'bg-warning',
            'late' => 'bg-danger',
            // add more as needed
        ];
        $labelMap = [
            'active' => 'Active Time',
            'break' => 'Break Time',
            'idle' => 'Idle Time',
            'manual' => 'Manual Time',
            'late' => 'Late',
            // add more as needed
        ];
        // To ensure all types are shown in legend, even if not present in today's intervals:
        $legendTypes = array_keys($labelMap);

    @endphp
    @foreach ($intervals as $interval)
        @php
            $start = \Carbon\Carbon::parse($interval['start']);
            $end = \Carbon\Carbon::parse($interval['end']);
            if ($start < $timelineStart) $start = $timelineStart;
            if ($end > $timelineEnd) $end = $timelineEnd;
            if ($start >= $end) continue;
            $width = ($end->diffInMinutes($start) / $totalMinutes) * 100;
            $color = $colorMap[$interval['type']] ?? 'bg-dark';
        @endphp
        <div class="progress-bar {{ $color }} progress-bar-striped shadow-none"
             role="progressbar"
             style="width: {{ $width }}%"
             title="{{ $start->format('h:i A') }} - {{ $end->format('h:i A') }} ({{ ucfirst($interval['type']) }})">
        </div>
    @endforeach
</div>

{{-- Legend --}}
<div class="timetracker-timeline-legend d-flex flex-wrap gap-4 mt-3 mb-2" style="font-size:1rem;">
    @foreach($legendTypes as $type)
        <span>
            <span class="timetracker-timeline-dot {{ $colorMap[$type] ?? 'bg-dark' }}"></span>
            {{ $labelMap[$type] }}
        </span>
    @endforeach
</div>
<style>
    .timetracker-timeline-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    align-items: center;
}
.timetracker-timeline-dot {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 7px;
    vertical-align: middle;
}

</style>
