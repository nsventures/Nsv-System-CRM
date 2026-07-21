@php
    $currency = $general_settings['currency_symbol'] ?? '$';
    $company_title = $general_settings['company_title'] ?? 'Company';
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
<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px; padding: 30px;">
    <!-- Header Section with Logo -->
    <div style="margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td width="66%" style="vertical-align: top;">
                    @if(!empty($logo_base64))
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $logo_base64 }}" alt="Logo" style="height: 50px; max-width: 200px;" />
                    </div>
                    @endif
                    <h1 style="font-size: 28px; font-weight: 700; color: #333; margin: 0;">{{ $company_title }}</h1>
                    @if($company_info['companyAddress'])
                    <p style="font-size: 12px; color: #666; margin: 3px 0;">{{ $company_info['companyAddress'] }}</p>
                    @endif
                    @if($city_state_country_zip)
                    <p style="font-size: 12px; color: #666; margin: 3px 0;">{{ $city_state_country_zip }}</p>
                    @endif
                    @if($company_info['companyPhone'])
                    <p style="font-size: 12px; color: #666; margin: 3px 0;">Phone: {{ $company_info['companyPhone'] }}</p>
                    @endif
                    @if($company_info['companyEmail'])
                    <p style="font-size: 12px; color: #666; margin: 3px 0;">Email: {{ $company_info['companyEmail'] }}</p>
                    @endif
                </td>
                <td width="34%" style="text-align: right; vertical-align: top;">
                    <p style="font-size: 14px; color: #666; margin: 0;">Payslip For the Month</p>
                    <h1 style="font-size: 24px; font-weight: 700; color: #333; margin: 5px 0;">{{ \Carbon\Carbon::parse($payslip->month)->format('F Y') }}</h1>
                </td>
            </tr>
        </table>
    </div>

    <!-- Employee Summary -->
    <div style="margin-bottom: 30px;">
        <h3 style="font-size: 14px; font-weight: 700; color: #333; margin-bottom: 15px;">EMPLOYEE SUMMARY</h3>
        <table width="100%" cellspacing="0" cellpadding="8" border="0" style="background-color: #f9f9f9;">
            <tr>
                <td width="50%" style="padding: 8px; border: none;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>Employee Name :</strong> {{ $payslip->user->first_name ?? '' }} {{ $payslip->user->last_name ?? '' }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>Employee ID :</strong> {{ $payslip->id }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>Pay Period :</strong> {{ \Carbon\Carbon::parse($payslip->month)->format('F Y') }}</p>
                            </td>
                        </tr>
                        @if($payslip->payment_date && $payslip->payment_date != '-')
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>Pay Date :</strong> {{ date('d/m/Y', strtotime($payslip->payment_date)) }}</p>
                            </td>
                        </tr>
                        @endif
                    </table>
                </td>
                <td width="50%" style="padding: 8px; border: none;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>Working Days :</strong> {{ $payslip->working_days }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>LOP Days :</strong> {{ $payslip->lop_days }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; border: none;">
                                <p style="margin: 0; font-size: 12px;"><strong>Paid Days :</strong> {{ $payslip->paid_days }}</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Earnings and Deductions Tables -->
    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 30px;">
        <tr>
            <!-- Earnings Column -->
            <td width="48%" style="vertical-align: top; padding-right: 2%;">
                <h4 style="font-size: 14px; font-weight: 700; color: #333; margin-bottom: 10px;">EARNINGS</h4>
                <table width="100%" cellspacing="0" cellpadding="5" border="1" style="border-collapse:collapse;">
                    <tr style="background-color: #f5f5f5;">
                        <td style="padding: 8px 5px; font-weight: 700; border: 1px solid #ddd;">EARNINGS</td>
                        <td style="padding: 8px 5px; font-weight: 700; text-align: right; border: 1px solid #ddd;">AMOUNT</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">Basic</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($payslip->basic_salary, 1, false) }}</td>
                    </tr>
                    @foreach($payslip->allowances as $a)
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">{{ $a->title }}</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($a->amount, 1, false) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">Incentive Pay</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($payslip->incentives, 1, false) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">Bonus</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($payslip->bonus, 1, false) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">Over Time</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($payslip->ot_payment, 1, false) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 5px; font-weight: 700; border: 1px solid #ddd; border-top: 2px solid #333;">Gross Earnings</td>
                        <td style="padding: 10px 5px; font-weight: 700; border: 1px solid #ddd; border-top: 2px solid #333; text-align: right;">{{ format_currency($actual_gross_earnings, 1, false) }}</td>
                    </tr>
                </table>
            </td>
            <!-- Deductions Column -->
            <td width="48%" style="vertical-align: top; padding-left: 2%;">
                <h4 style="font-size: 14px; font-weight: 700; color: #333; margin-bottom: 10px;">DEDUCTIONS</h4>
                <table width="100%" cellspacing="0" cellpadding="5" border="1" style="border-collapse:collapse;">
                    <tr style="background-color: #f5f5f5;">
                        <td style="padding: 8px 5px; font-weight: 700; border: 1px solid #ddd;">DEDUCTIONS</td>
                        <td style="padding: 8px 5px; font-weight: 700; text-align: right; border: 1px solid #ddd;">AMOUNT</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">Leave Deduction</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($payslip->leave_deduction, 1, false) }}</td>
                    </tr>
                    @foreach($payslip->deductions as $d)
                    <tr>
                        <td style="padding: 8px 5px; border: 1px solid #ddd;">{{ $d->title }}</td>
                        <td style="padding: 8px 5px; text-align: right; border: 1px solid #ddd;">{{ format_currency($d->amount, 1, false) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td style="padding: 10px 5px; font-weight: 700; border: 1px solid #ddd; border-top: 2px solid #333;">Total Deductions</td>
                        <td style="padding: 10px 5px; font-weight: 700; border: 1px solid #ddd; border-top: 2px solid #333; text-align: right;">{{ format_currency($actual_total_deductions, 1, false) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TOTAL NET PAYABLE Section -->
    <table width="100%" cellspacing="0" cellpadding="12" border="1" style="border: 2px dashed #ddd; margin-top: 30px; margin-bottom: 20px;">
        <tr>
            <td width="66%" style="vertical-align: top; border: none; padding: 8px;">
                <h4 style="font-size: 14px; font-weight: 600; color: #333; text-transform: uppercase; margin: 0 0 5px 0;">TOTAL NET PAYABLE</h4>
                <p style="font-size: 12px; color: #666; margin: 0;">Gross Earnings - Total Deductions</p>
            </td>
            <td width="34%" style="text-align: right; vertical-align: middle; border: none; padding: 8px;">
                <div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 12px;">
                    <h2 style="font-size: 24px; font-weight: 500; color: #155724; margin: 0 0 5px 0; text-align: right;">{{ format_currency($payslip->net_pay, 1, false) }}</h2>
                    <p style="font-size: 11px; color: #666; margin: 0; text-align: right;">Amount In Words : {{ number_to_words($payslip->net_pay) }} Only</p>
                </div>
            </td>
        </tr>
    </table>
</div>
