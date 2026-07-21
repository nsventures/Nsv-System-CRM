@extends('layout')

@section('title')
    {{ get_label('social_accounts', 'Social Accounts') }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('social_accounts', 'Social Accounts') }}
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('social.accounts.create') }}">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="{{ get_label('create_account', 'Create Account') }}">
                        <i class="bx bx-plus"></i>
                    </button>
                </a>
            </div>
        </div>

        @if ($accounts->count() > 0)
            @php
                $visibleColumns = getUserPreferences('social_accounts');
            @endphp
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <select class="form-select" id="account_status_filter"
                                aria-label="Filter by status">
                                <option value="">{{ get_label('all_status', 'All Status') }}</option>
                                <option value="active">{{ get_label('active', 'Active') }}</option>
                                <option value="inactive">{{ get_label('inactive', 'Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <input type="hidden" id="data_type" value="social-accounts">
                        <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                            data-url="{{ route('social.accounts.list') }}" data-icons-prefix="bx" data-icons="icons"
                            data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-field="id"
                                        data-visible="{{ in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">{{ get_label('id', 'ID') }}</th>
                                    <th data-field="name"
                                        data-visible="{{ in_array('name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">{{ get_label('name', 'Name') }}</th>
                                    <th data-field="description"
                                        data-visible="{{ in_array('description', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="false">{{ get_label('description', 'Description') }}</th>
                                    <th data-field="platforms"
                                        data-visible="{{ in_array('platforms', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="false">{{ get_label('platforms', 'Platforms') }}</th>
                                    <th data-field="status"
                                        data-visible="{{ in_array('status', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">{{ get_label('status', 'Status') }}</th>
                                    <th data-field="created_at"
                                        data-visible="{{ in_array('created_at', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">{{ get_label('created_at', 'Created At') }}</th>
                                    <th data-field="updated_at"
                                        data-visible="{{ in_array('updated_at', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">{{ get_label('updated_at', 'Updated At') }}</th>
                                    <th data-field="actions"
                                        data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                        {{ get_label('actions', 'Actions') }}
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="card empty-state text-center">
                <div class="card-body">
                    <div class="misc-wrapper">
                        <h2 class="mx-2 mb-2">
                            <span>{{ get_label('social_accounts_not_found', 'Social Accounts Not Found') }}</span>
                        </h2>
                        <p class="mx-2 mb-4">
                            {{ get_label('no_accounts_available', 'No social accounts available yet. Create one to start posting.') }}
                        </p>

                        <a href="{{ route('social.accounts.create') }}" class="btn btn-md btn-primary m-1">
                            {{ get_label('create_now', 'Create now') }}
                        </a>

                        <div class="mt-3">
                            <img src="{{ asset('/storage/no-result.png') }}" alt="No result" width="500"
                                class="img-fluid" />
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        var label_update = '{{ get_label('update', 'Update') }}';
        var label_delete = '{{ get_label('delete', 'Delete') }}';

        function queryParams(p) {
            return {
                status: $('#account_status_filter').val(),
                page: p.offset / p.limit + 1,
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }

        $('#account_status_filter').on('change', function() {
            $('#table').bootstrapTable('refresh');
        });
    </script>












@endsection