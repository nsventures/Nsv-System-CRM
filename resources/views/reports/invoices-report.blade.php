@extends('layout')
@section('title')
{{ get_label('estimates_invoices_report', 'Estimates/Invoices Report') }}
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home.index') }}">{{ get_label('home', 'Home') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        {{ get_label('reports', 'Reports') }}
                    </li>
                    <li class="breadcrumb-item active">
                        {{ get_label('estimates_invoices', 'Estimates/Invoices') }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Summary Tiles -->
    <div class="row g-3 mb-4 tk-stats-grid">
        <!-- Total -->
        <div class="col-6 col-md-4 col-xl">
            <div class="tk-fact h-100">
                <i class="bx bx-receipt" style="font-size:22px; color:var(--signal);"></i>
                <div class="tk-fact-txt">
                    <span class="tk-fact-k">{{ get_label('total', 'Total') }}</span>
                    <span class="tk-fact-v" id="total-invoices">—</span>
                </div>
            </div>
        </div>
        <!-- Total Amount -->
        <div class="col-6 col-md-4 col-xl">
            <div class="tk-fact h-100">
                <i class="bx bx-money" style="font-size:22px; color:var(--ok);"></i>
                <div class="tk-fact-txt">
                    <span class="tk-fact-k">{{ get_label('total_amount', 'Total Amount') }}</span>
                    <span class="tk-fact-v" id="total-amount">—</span>
                </div>
            </div>
        </div>
        <!-- Total Tax -->
        <div class="col-6 col-md-4 col-xl">
            <div class="tk-fact h-100">
                <i class="bx bx-purchase-tag" style="font-size:22px; color:var(--warn);"></i>
                <div class="tk-fact-txt">
                    <span class="tk-fact-k">{{ get_label('total_tax', 'Total Tax') }}</span>
                    <span class="tk-fact-v" id="total-tax">—</span>
                </div>
            </div>
        </div>
        <!-- Final Total -->
        <div class="col-6 col-md-4 col-xl">
            <div class="tk-fact h-100">
                <i class="bx bx-money" style="font-size:22px; color:var(--info);"></i>
                <div class="tk-fact-txt">
                    <span class="tk-fact-k">{{ get_label('final_total', 'Final Total') }}</span>
                    <span class="tk-fact-v" id="total-final">—</span>
                </div>
            </div>
        </div>
        <!-- Average Value -->
        <div class="col-6 col-md-4 col-xl">
            <div class="tk-fact h-100">
                <i class="bx bx-trending-up" style="font-size:22px; color:var(--fg-1);"></i>
                <div class="tk-fact-txt">
                    <span class="tk-fact-k">{{ get_label('average_value', 'Average Value') }}</span>
                    <span class="tk-fact-v" id="average-invoice-value">—</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
               
                <div>
                    <h5 class="card-title mb-1">{{ get_label('estimates_invoices_report', 'Estimates/Invoices Report') }}</h5>
                    <p class="text-muted mb-0 small">{{ get_label('filter_estimates_invoices_report', 'Filter estimates and invoices by date, type, client and creator') }}</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <button class="btn btn-primary" id="export_button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('export_report', 'Export Report') }}">
                    <i class="bx bx-export"></i> {{ get_label('export', 'Export') }}
                </button>
                <button class="btn btn-secondary clear-report-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                    <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters Row -->
            <div class="row g-3 align-items-end">
                <x-advanced-date-filters prefix="report" />
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('types', 'Types') }}</label>
                    <select class="form-select tom_static_select" id="type_filter" aria-label="{{ get_label('select_types', 'Select types') }}" data-placeholder="<?= get_label('select_types', 'Select types') ?>" multiple>
                        <option value="estimate">{{ get_label('estimates', 'Estimates') }}</option>
                        <option value="invoice">{{ get_label('invoices', 'Invoices') }}</option>
                    </select>
                </div>
                @if (!isClient() || isAdminOrHasAllDataAccess())
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('clients', 'Clients') }}</label>
                    <select class="form-select tom_clients_select" id="client_filter" aria-label="{{ get_label('select_clients', 'Select Clients') }}" data-placeholder="<?= get_label('select_clients', 'Select Clients') ?>" multiple>
                    </select>
                </div>
                @endif
                @if(isAdminOrHasAllDataAccess())
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('user_creators', 'User Creators') }}</label>
                    <select class="form-select tom_users_select" id="user_creators_filter" aria-label="{{ get_label('select_user_creators', 'Select User Creators') }}" data-placeholder="<?= get_label('select_user_creators', 'Select User Creators') ?>" multiple>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('client_creators', 'Client Creators') }}</label>
                    <select class="form-select tom_clients_select" id="client_creators_filter" aria-label="{{ get_label('select_client_creators', 'Select Client Creators') }}" data-placeholder="<?= get_label('select_client_creators', 'Select Client Creators') ?>" multiple>
                    </select>
                </div>
                @endif
            </div>
        </div>
    @php
        $visibleColumns = getUserPreferences('estimates_invoices_report');
    @endphp
    <div class="card border shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive text-nowrap tk-table">
                <input type="hidden" id="multi_select">
                <input type="hidden" id="data_type" value="report">
                <input type="hidden" id="save_column_visibility" data-type="estimates_invoices_report" data-table="invoices_report_table">
                <table id="invoices_report_table" data-toggle="table"
                    data-url="{{ route('reports.invoices-report-data') }}" data-loading-template="loadingTemplate"
                    data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total"
                    data-trim-on-search="false" data-data-field="invoices" data-page-list="[5, 10, 20, 50, 100, 200]"
                    data-search="true" data-side-pagination="server" data-show-columns="true" data-pagination="true"
                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                    data-query-params="invoices_report_query_params">
                    <thead>
                        <tr>
                            <th rowspan="2" data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('id', 'ID') }}</th>
                            <th rowspan="2" data-field="type" data-visible="{{ (in_array('type', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('type', 'Type') }}</th>
                            <th rowspan="2" data-field="client" data-visible="{{ (in_array('client', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="false">{{ get_label('client', 'Client') }}</th>
                            <th colspan="3">{{ get_label('amount', 'Amount') }}</th>
                            <th colspan="2">{{ get_label('date_range', 'Date Range') }}</th>
                            <th rowspan="2" data-field="status" data-visible="{{ (in_array('status', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('status', 'Status') }}</th>
                            <th rowspan="2" data-field="created_by" data-visible="{{ (in_array('created_by', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="false">{{ get_label('created_by', 'Created By') }}</th>
                            <th colspan="2">{{ get_label('timestamps', 'Timestamps') }}</th>
                        </tr>
                        <tr>
                            <th data-field="total" data-visible="{{ (in_array('total', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('total', 'Total') }}</th>
                            <th data-field="tax_amount" data-visible="{{ (in_array('tax_amount', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('tax', 'Tax') }}</th>
                            <th data-field="final_total" data-visible="{{ (in_array('final_total', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('final_total', 'Final Total') }}</th>
                            <th data-field="from_date" data-visible="{{ (in_array('from_date', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('from', 'From') }}</th>
                            <th data-field="to_date" data-visible="{{ (in_array('to_date', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('to', 'To') }}</th>
                            <th data-field="created_at" data-visible="{{ (in_array('created_at', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}">{{ get_label('created_at', 'Created At') }}</th>
                            <th data-field="updated_at" data-visible="{{ (in_array('updated_at', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}">{{ get_label('updated_at', 'Updated At') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    var invoices_report_export_url = "{{ route('reports.export-invoices-report') }}";
</script>
<script src="{{ asset('assets/js/pages/invoices-report.js') }}?v={{ time() }}"></script>
@endsection

