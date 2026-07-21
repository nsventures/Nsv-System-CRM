@php
    $statusCounts = $statusCounts ?? [];
    $statuses = $statuses ?? collect();
@endphp
<div class="mt-4 pt-3 border-top status-list">
    <div class="table-responsive tk-table">
        <table class="table table-sm mb-0">

            <tbody>
                @foreach ($statusCounts as $statusId => $count)
                    @php
                        $status = $statuses->where('id', $statusId)->first();
                        $percentage = $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td class="border-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="legend-dot bg-{{ $status->color }} me-2"
                                     style="width: 12px; height: 12px; border-radius: 50%;"></div>
                                <a href="{{ url(getUserPreferences($type, 'default_view') . '?status=' . $status->id) }}"
                                   class="text-decoration-none text-dark fw-medium">
                                    {{ $status->title }}
                                </a>
                            </div>
                        </td>
                        <td class="border-0 py-2 text-end">
                            <span class="fw-bold text-{{ $status->color }}">{{ $count }}</span>
                        </td>
                        <td class="border-0 py-2 text-end text-muted">
                            <small>{{ $percentage }}%</small>
                        </td>
                    </tr>
                @endforeach
                <tr class="border-top">
                    <td class="pt-2 fw-bold">
                        <i class="bx bx-menu me-2"></i>{{ get_label('total', 'Total') }}
                    </td>
                    <td class="pt-2 text-end fw-bold text-primary">{{ $totalCount }}</td>
                    <td class="pt-2 text-end text-muted">
                        <small>100%</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
