@extends('layout')
@section('title')
<?= get_label('payslip', 'Payslip') ?>
@endsection
@section('content')
@php
    $currency = $general_settings['currency_symbol'] ?? '$';
    $company_title = $general_settings['company_title'] ?? 'Company';
    $logo_url = asset($general_settings['full_logo'] ?? 'storage/logos/default_full_logo.png');
    // Build company address
    $addressParts = [
        $company_info['companyCity'] ?? '',
        $company_info['companyState'] ?? '',
        $company_info['companyCountry'] ?? '',
        $company_info['companyZip'] ?? '',
    ];
    $addressParts = array_filter($addressParts);
    $city_state_country_zip = implode(', ', $addressParts);

    // Calculate actual gross earnings and total deductions for display
    $actual_gross_earnings = $payslip->basic_salary + $payslip->incentives + $payslip->bonus + $payslip->ot_payment;
    foreach($payslip->allowances as $allowance) {
        $actual_gross_earnings += $allowance->amount;
    }
    $actual_total_deductions = $payslip->leave_deduction + $payslip->total_deductions;
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4" id="section-not-to-print">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <a href="{{ url('payslips') }}"><?= get_label('payslips', 'Payslips') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('view', 'View') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Display Payslip Information -->
    <div class="card">
        <div class="card-body p-4">
            <div id='section-to-print' class="bg-white">
                <!-- Header Section with Logo -->
                <div class="row mb-4 payslip-header-section">
                    <div class="col-md-8">
                        @if($general_settings['full_logo'])
                        <div class="img-box-100">
                            <img src="{{ $logo_url }}" alt="Logo" />
                        </div>
                        @endif
                        <h2 class="payslip-company-title">{{ $company_title }}</h2>
                        @if($company_info['companyAddress'])
                        <p class="text-muted small">{{ $company_info['companyAddress'] }}</p>
                        @endif
                        @if($city_state_country_zip)
                        <p class="text-muted small">{{ $city_state_country_zip }}</p>
                        @endif
                        @if($company_info['companyPhone'])
                        <p class="text-muted small"><?= get_label('phone','Phone') ?>: {{ $company_info['companyPhone'] }}</p>
                        @endif
                        @if($company_info['companyEmail'])
                        <p class="text-muted small"><?= get_label('email','Email') ?>: {{ $company_info['companyEmail'] }}</p>
                        @endif
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="text-muted small mb-0"><?= get_label('payslip_for_month', 'Payslip For the Month') ?></p>
                        <h2 class="display-6 fw-bold mb-0">{{ $payslip->month->format('F Y') }}</h2>
                    </div>
                </div>

                <!-- Employee Summary -->
                <div class="row mb-4 mt-4">
                    <div class="col-md-12">
                        <h3 class="payslip-section-title mb-3"><?= get_label('employee_summary', 'Employee Summary') ?></h3>
                        <div class="payslip-employee-summary-box">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong><?= get_label('employee_name', 'Employee Name') ?> :</strong> {{ $payslip->user_name }}</p>
                                    <p class="mb-2"><strong><?= get_label('employee_id', 'Employee ID') ?> :</strong> {{ $payslip->id }}</p>
                                    <p class="mb-2"><strong><?= get_label('payslip_month', 'Pay Period') ?> :</strong> {{ $payslip->month->format('F Y') }}</p>
                                    @if($payslip->payment_date && $payslip->payment_date != '-')
                                    <p class="mb-2"><strong><?= get_label('payment_date', 'Pay Date') ?> :</strong> {{ date('d/m/Y', strtotime($payslip->payment_date)) }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong><?= get_label('working_days', 'Working Days') ?> :</strong> {{ $payslip->working_days }}</p>
                                    <p class="mb-2"><strong><?= get_label('lop_days', 'LOP Days') ?> :</strong> {{ $payslip->lop_days }}</p>
                                    <p class="mb-2"><strong><?= get_label('paid_days', 'Paid Days') ?> :</strong> {{ $payslip->paid_days }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings and Deductions Tables -->
                <div class="row mb-4 mt-4">
                    <div class="col-md-6">
                        <h4 class="payslip-section-title mb-2"><?= get_label('earnings', 'EARNINGS') ?></h4>
                        <table class="table table-bordered mb-0">
                            <thead class="payslip-table-header">
                                <tr>
                                    <th class="fw-bold"><?= get_label('earnings', 'EARNINGS') ?></th>
                                    <th class="fw-bold text-end"><?= get_label('amount', 'AMOUNT') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= get_label('basic_salary', 'Basic') ?></td>
                                    <td class="text-end">{{ format_currency($payslip->basic_salary, 1, false) }}</td>
                                </tr>
                                @foreach($payslip->allowances as $allowance)
                                <tr>
                                    <td>{{ $allowance->title }}</td>
                                    <td class="text-end">{{ format_currency($allowance->amount, 1, false) }}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td><?= get_label('incentives', 'Incentive Pay') ?></td>
                                    <td class="text-end">{{ format_currency($payslip->incentives, 1, false) }}</td>
                                </tr>
                                <tr>
                                    <td><?= get_label('bonus', 'Bonus') ?></td>
                                    <td class="text-end">{{ format_currency($payslip->bonus, 1, false) }}</td>
                                </tr>
                                <tr>
                                    <td><?= get_label('over_time_payment', 'Over Time') ?></td>
                                    <td class="text-end">{{ format_currency($payslip->ot_payment, 1, false) }}</td>
                                </tr>
                                <tr class="payslip-total-row">
                                    <td><?= get_label('gross_earnings', 'Gross Earnings') ?></td>
                                    <td class="text-end">{{ format_currency($actual_gross_earnings, 1, false) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h4 class="payslip-section-title mb-2"><?= get_label('deductions', 'DEDUCTIONS') ?></h4>
                        <table class="table table-bordered mb-0">
                            <thead class="payslip-table-header">
                                <tr>
                                    <th class="fw-bold"><?= get_label('deductions', 'DEDUCTIONS') ?></th>
                                    <th class="fw-bold text-end"><?= get_label('amount', 'AMOUNT') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= get_label('leave_deduction', 'Leave Deduction') ?></td>
                                    <td class="text-end">{{ format_currency($payslip->leave_deduction, 1, false) }}</td>
                                </tr>
                                @foreach($payslip->deductions as $deduction)
                                <tr>
                                    <td>{{ $deduction->title }}</td>
                                    <td class="text-end">{{ format_currency($deduction->amount, 1, false) }}</td>
                                </tr>
                                @endforeach
                                <tr class="payslip-total-row">
                                    <td><?= get_label('total_deductions', 'Total Deductions') ?></td>
                                    <td class="text-end">{{ format_currency($actual_total_deductions, 1, false) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TOTAL NET PAYABLE Section -->
                <div class="row mb-4 mt-4">
                    <div class="col-12">
                        <div class="border border-2 border-dashed p-3 rounded">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="text-uppercase fw-semibold mb-2"><?= get_label('total_net_payable', 'TOTAL NET PAYABLE') ?></h4>
                                    <p class="text-muted mb-0"><?= get_label('gross_earnings', 'Gross Earnings') ?> - <?= get_label('total_deductions', 'Total Deductions') ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="p-3">
                                        <h2 class=" mb-1">{{ format_currency($payslip->net_pay, 1, false) }}</h2>
                                        <small class="text-muted"><?= get_label('amount_in_words', 'Amount In Words') ?> : {{ number_to_words($payslip->net_pay) }} Only</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
    <div class="col-md-12 text-center mt-4" id="section-not-to-print">
        <a href="{{ route('payslips.pdf', $payslip->id) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="<?= get_label('download_pdf', 'Download PDF') ?>"><i class='bx bx-download'></i></a>
        <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#payslipEmailModal" data-bs-original-title="<?= get_label('send_via_email', 'Send via email') ?>"><i class='bx bx-mail-send'></i></button>
        <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="<?= get_label('print_payslip', 'Print payslip') ?>" onclick="window.print()"><i class='bx bx-printer'></i></button>
    </div>
</div>
<!-- Email Modal -->
<div class="modal fade" id="payslipEmailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('payslips.send-email', $payslip->id) }}" method="POST" class="form-submit-event">
      @csrf
      <input type="hidden" name="dnr" value="1">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= get_label('send_via_email', 'Send via email') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?= get_label('email_to','Email to') ?></label>
            <input type="email" name="to" id="payslip_email_to" class="form-control" value="{{ $payslip->user_email ?? '' }}" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= get_label('email_cc','CC') ?></label>
            <input type="text" name="cc" id="payslip_email_cc" class="form-control" placeholder="name1@example.com,name2@example.com">
        </div>
        <div class="mb-3">
          <label class="form-label"><?= get_label('email_subject','Subject') ?></label>
            <input type="text" name="subject" id="payslip_email_subject" class="form-control" value="<?= get_label('payslip','Payslip') ?> <?= get_label('payslip_id_prefix','PSL-') ?>{{ $payslip->id }}">
        </div>
        <div class="mb-3">
          <label class="form-label"><?= get_label('email_body','Body') ?></label>
            <textarea name="body" id="payslip_email_body" class="form-control" rows="4"><?= get_label('please_find_attached_payslip', 'Please find the attached payslip.') ?></textarea>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= get_label('cancel','Cancel') ?></button>
          <button type="submit" id="submit_btn" class="btn btn-primary"><?= get_label('send','Send') ?></button>
        </div>
      </div>
    </form>
  </div>
  </div>

<script>
    var label_email_to_required = '<?= get_label('email_to_required', 'Email is required') ?>';
    var label_payslip_email_sent = '<?= get_label('payslip_email_sent', 'Payslip emailed successfully') ?>';
</script>
@endsection
