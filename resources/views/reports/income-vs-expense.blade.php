@extends('layout')
@section('title')
    {{ get_label('income_vs_expense_report', 'Income vs Expense Report') }} - {{ get_label('reports', 'Reports') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('home.index') }}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="#">{{ get_label('reports', 'Reports') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('income_vs_expense', 'Income vs Expense') }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl col-lg-4 col-md-6">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('total_income', 'Total Income') }}</span>
                            <div class="avatar avatar-sm">
                                <i class="bx bx-wallet"></i>
                            </div>
                        </div>
                        <h3 class="mb-0 fw-bold lh-sm" id="total_income">{{ get_label('loading', 'Loading...') }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('total_expense', 'Total Expense') }}</span>
                            <div class="avatar avatar-sm">
                                <i class="bx bx-credit-card"></i>
                            </div>
                        </div>
                        <h3 class="mb-0 fw-bold lh-sm" id="total_expenses">{{ get_label('loading', 'Loading...') }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('profit_or_loss', 'Profit or Loss') }}</span>
                            <div class="avatar avatar-sm">
                                <i class="bx bx-bar-chart"></i>
                            </div>
                        </div>
                        <h3 class="mb-0 fw-bold lh-sm" id="profit_or_loss">{{ get_label('loading', 'Loading...') }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  
                    <div>
                        <h5 class="card-title mb-1">{{ get_label('income_vs_expense_report', 'Income vs Expense Report') }}</h5>
                        <p class="text-muted mb-0 small">{{ get_label('filter_income_vs_expense_report', 'Filter income and expense data by date range') }}</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-primary" id="export_button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('export_income_expense_report', 'Export Income vs Expense Report') }}">
                        <i class="bx bx-export"></i> {{ get_label('export', 'Export') }}
                    </button>
                    <button class="btn btn-secondary clear-report-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                        <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <x-advanced-date-filters prefix="report" :filters="['date_between']" colClass="col-md-6" />
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="invoices_table">
                                <thead>
                                    <tr>
                                        <th>{{ get_label('id', 'ID') }}</th>
                                        <th>{{ get_label('date_range','Date Range') }}</th>
                                        <th>{{ get_label('amount', 'Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="expenses_table">
                                <thead>
                                    <tr>
                                        <th>{{ get_label('id', 'ID') }}</th>
                                        <th>{{ get_label('title' ,'Title') }}</th>
                                        <th>{{ get_label('amount', 'Amount') }}</th>
                                        <th>{{ get_label('date', 'Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>var export_income_vs_expense_url = "{{ route('reports.export-income-vs-expense-report') }}";</script>
<script src="{{ asset('assets/js/pages/income-vs-expense-report.js') }}"></script>
@endsection
