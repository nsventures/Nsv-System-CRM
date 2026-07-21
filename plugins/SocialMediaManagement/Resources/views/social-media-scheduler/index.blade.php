@extends('layout')

@section('title')
    {{ get_label('social_media_scheduler', 'Social Media Scheduler') }}
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/social/social.css') }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4">
        {{-- Breadcrumb --}}
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        {{ get_label('social_media', 'Social Media') }}
                    </li>
                    <li class="breadcrumb-item active">
                        {{ get_label('posts', 'Posts') }}
                    </li>
                </ol>
            </nav>
        </div>

        {{-- Default View Badge --}}
        <div>
            @php $socialsDefaultView = getUserPreferences('socials', 'default_view'); @endphp

            @if ($socialsDefaultView === 'socials')
                <span class="badge bg-primary">{{ get_label('default_view', 'Default View') }}</span>
            @else
                <a href="javascript:void(0);">
                    <span class="badge bg-secondary"
                          id="set-default-view"
                          data-type="socials"
                          data-view="socials">
                        {{ get_label('set_as_default_view', 'Set as Default View') }}
                    </span>
                </a>
            @endif
        </div>

        {{-- Buttons --}}
        <div>
            <a href="{{ route('social.create') }}">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                        data-bs-placement="right"
                        data-bs-original-title="{{ get_label('create_post', 'Create Post') }}">
                    <i class="bx bx-plus"></i>
                </button>
            </a>

            <a href="{{ route('social.calendar') }}">
                <button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip"
                        data-bs-placement="left"
                        data-bs-original-title="{{ get_label('calendar_view', 'Calendar view') }}">
                    <i class='bx bx-calendar'></i>
                </button>
            </a>

            <a href="{{ route('social.analytics') }}">
                <button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip"
                        data-bs-placement="left"
                        data-bs-original-title="{{ get_label('analytics', 'Analytics') }}">
                    <i class='bx bx-chart'></i>
                </button>
            </a>
        </div>
    </div>

    {{-- Posts Table --}}
    @if ($posts->count() > 0)

        @php $visibleColumns = getUserPreferences('socials'); @endphp

        <div class="card">
            <div class="card-body">
                {{-- Filters Row --}}
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <select class="form-select js-example-basic-multiple"
                                id="select_social_platforms"
                                name="platform"
                                data-placeholder="{{ get_label('filter_by_platform', 'Filter By Platform') }}"
                                data-allow-clear="true">
                            <option></option>

                            <option value="facebook" {{ request()->sort == 'facebook' ? 'selected' : '' }}>
                                {{ get_label('facebook','Facebook') }}
                            </option>
                            <option value="instagram" {{ request()->sort == 'instagram' ? 'selected' : '' }}>
                                {{ get_label('instagram','Instagram') }}
                            </option>
                            <option value="linkedin" {{ request()->sort == 'linkedin' ? 'selected' : '' }}>
                                {{ get_label('linkedin','Linkedin') }}
                            </option>
                            <option value="pinterest" {{ request()->sort == 'pinterest' ? 'selected' : '' }}>
                                {{ get_label('pinterest','Pinterest') }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <select class="form-select js-example-basic-multiple"
                                id="select_social_stastuses"
                                name="status"
                                data-placeholder="{{ get_label('select_by_status', 'Select By Status') }}"
                                data-allow-clear="true">
                            <option></option>

                            <option value="pending" {{ request()->sort == 'pending' ? 'selected' : '' }}>
                                {{ get_label('pending', 'Pending') }}
                            </option>
                            <option value="scheduled" {{ request()->sort == 'scheduled' ? 'selected' : '' }}>
                                {{ get_label('scheduled', 'Scheduled') }}
                            </option>
                            <option value="published" {{ request()->sort == 'published' ? 'selected' : '' }}>
                                {{ get_label('published', 'Published') }}
                            </option>
                            <option value="failed" {{ request()->sort == 'failed' ? 'selected' : '' }}>
                                {{ get_label('failed', 'Failed') }}
                            </option>
                            <option value="partially_published" {{ request()->sort == 'partially_published' ? 'selected' : '' }}>
                                {{ get_label('partially_published', 'Partially Published') }}
                            </option>
                        </select>
                    </div>

                    {{-- Account Filter --}}
                    <div class="col-md-3 mb-3">
                        <select class="form-select"
                                id="select_account_filter"
                                data-placeholder="{{ get_label('select_by_accounts', 'Select By Accounts') }}"
                                data-allow-clear="true">
                            <option></option>
                        </select>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive text-nowrap">
                    <input type="hidden" id="data_type" value="social-media-scheduler">
                    <input type="hidden" id="save_column_visibility">

                    <table id="table"
                           data-toggle="table"
                           data-loading-template="loadingTemplate"
                           data-url="{{ route('social.list') }}"
                           data-icons-prefix="bx"
                           data-icons="icons"
                           data-show-refresh="true"
                           data-total-field="total"
                           data-data-field="rows"
                           data-page-list="[5, 10, 20, 50, 100, 200]"
                           data-search="true"
                           data-side-pagination="server"
                           data-show-columns="true"
                           data-pagination="true"
                           data-sort-name="id"
                           data-sort-order="desc"
                           data-mobile-responsive="true"
                           data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-checkbox="true"></th>

                                <th data-field="id"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('id', $visibleColumns) }}">
                                    {{ get_label('id','ID') }}
                                </th>

                                <th data-field="caption"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('caption', $visibleColumns) }}">
                                    {{ get_label('caption','Caption') }}
                                </th>

                                <th data-field="platforms"
                                    data-visible="{{ empty($visibleColumns) || in_array('platforms', $visibleColumns) }}">
                                    {{ get_label('platforms','Platforms') }}
                                </th>

                                <th data-field="status"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('status', $visibleColumns) }}">
                                    {{ get_label('status','Status') }}
                                </th>

                                <th data-field="account"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('account', $visibleColumns) }}">
                                    {{ get_label('account','Account') }}
                                </th>

                                <th data-field="scheduled_at"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('scheduled_at', $visibleColumns) }}">
                                    {{ get_label('scheduled_at','Scheduled At') }}
                                </th>

                                <th data-field="created_at"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('created_at', $visibleColumns) }}">
                                    {{ get_label('created_at','Created At') }}
                                </th>

                                <th data-field="updated_at"
                                    data-sortable="true"
                                    data-visible="{{ empty($visibleColumns) || in_array('updated_at', $visibleColumns) }}">
                                    {{ get_label('updated_at','Updated At') }}
                                </th>

                                <th data-field="actions"
                                    data-visible="{{ empty($visibleColumns) || in_array('actions', $visibleColumns) }}">
                                    {{ get_label('actions','Actions') }}
                                </th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    @else
        {{-- Empty State --}}
        <div class="card empty-state text-center">
            <div class="card-body">
                <div class="misc-wrapper">

                    <h2 class="mx-2 mb-2">
                        {{ get_label('posts_not_found', 'Posts Not Found') }}
                    </h2>

                    <p class="mx-2 mb-4">
                        {{ get_label('no_posts_available','Oops! No posts available yet.') }}
                    </p>

                    <a href="{{ route('social.create') }}" class="btn btn-md btn-primary m-1">
                        {{ get_label('create_now','Create now') }}
                    </a>

                    <div class="mt-3">
                        <img src="{{ asset('/storage/no-result.png') }}" width="500" class="img-fluid" alt="No result">
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>

{{-- Quick View Modal --}}
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="bx bx-show-alt me-2"></i>
                    {{ get_label('post_publishing_details','Post Publishing Details') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="quickViewContent"></div>
            </div>
        </div>
    </div>
</div>

{{-- Correct JavaScript Vars --}}
<script>
    var label_update = "{{ get_label('update', 'Update') }}";
    var label_delete = "{{ get_label('delete', 'Delete') }}";
</script>

<script>
    $(document).ready(function () {

        // Initialize Select2
        $('#select_account_filter').select2({
            placeholder: "{{ get_label('select_by_accounts', 'Select By Accounts') }}",
            allowClear: true,
            width: '100%'
        });

        // Load Active Accounts
        loadActiveAccounts();

        $('#select_account_filter').on('change', function () {
            $('#table').bootstrapTable('refresh');
        });
    });

    // Load Active Accounts
    function loadActiveAccounts() {
        $.ajax({
            url: "{{ url('social-media-scheduler/social-accounts/active') }}",
            type: "GET",
            dataType: "json"
        })
        .done(function (response) {

            console.log(response);
            const $select = $('#select_account_filter');
            $select.find('option:not(:first)').remove();

            if (!response.error && response.data) {
                response.data.forEach(account => {
                    $select.append(
                        $('<option>', {
                            value: account.id,
                            text: account.name
                        })
                    );
                });

                $select.trigger('change.select2');
            }
        })
        .fail(function (xhr, status, error) {
            console.error("Failed to load accounts:", error);
        });
    }
</script>

<script src="{{ asset('assets/js/social/social.js') }}"></script>
@endsection
