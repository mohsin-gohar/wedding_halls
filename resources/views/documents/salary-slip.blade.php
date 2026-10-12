<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Disbursement Slip - {{ $payroll->user->name }} - {{ $payroll->month_year }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #171513; line-height: 1.4; font-size: 12px; margin: 0; padding: 0; background: #fff; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #C6A15B; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .brand-title { font-size: 24px; font-weight: bold; color: #171513; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-family: Georgia, serif; }
        .brand-subtitle { font-size: 11px; color: #78716C; margin-top: 3px; }
        .badge { background-color: #171513; color: #C6A15B; padding: 8px 16px; font-weight: bold; font-size: 12px; text-align: right; text-transform: uppercase; }
        .section-title { font-size: 12px; font-weight: bold; color: #171513; border-bottom: 1px solid #E7E5E4; padding-bottom: 4px; margin: 15px 0 8px 0; text-transform: uppercase; font-family: Georgia, serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background-color: #FAFAF9; color: #44403C; font-weight: bold; text-align: left; padding: 6px 8px; border: 1px solid #E7E5E4; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border: 1px solid #E7E5E4; }
        .total-box { background-color: #F8F5EF; border: 1px solid #C6A15B; padding: 12px; margin-top: 15px; }
        .text-right { text-align: right; }
        .no-print { margin-bottom: 15px; text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()" style="padding: 8px 16px; background: #171513; color: #C6A15B; border: 1px solid #C6A15B; cursor: pointer; font-weight: bold;">
                Print Official Salary Slip / Save PDF
            </button>
        </div>

        <div class="header">
            <div>
                <h1 class="brand-title">{{ $payroll->tenant->name ?? 'VenueCloud Hospitality' }}</h1>
                <div class="brand-subtitle">{{ $payroll->tenant->address ?? 'Main Boulevard, Lahore' }} • Phone: {{ $payroll->tenant->phone }}</div>
                <div class="brand-subtitle">Workforce ERP &amp; Payroll Management Suite</div>
            </div>
            <div class="badge">
                Confidential Salary Slip<br>
                <small style="font-size: 9px; color: #fff;">Slip #: SLIP-{{ strtoupper(substr($payroll->id, 0, 8)) }}</small>
            </div>
        </div>

        <div class="section-title">Employee &amp; Pay Period Information</div>
        <table>
            <tr>
                <th width="20%">Employee Name</th>
                <td width="30%"><strong>{{ $payroll->user->name }}</strong></td>
                <th width="20%">Pay Month</th>
                <td width="30%"><strong>{{ \Carbon\Carbon::parse($payroll->month_year . '-01')->format('F Y') }}</strong></td>
            </tr>
            <tr>
                <th>Designation / Role</th>
                <td>{{ str_replace('ROLE_', '', $payroll->user->role) }}</td>
                <th>Disbursement Status</th>
                <td>
                    <strong style="color: {{ $payroll->status === 'PAID' ? '#8F7239' : '#171513' }};">
                        {{ $payroll->status }} ({{ $payroll->payment_method }})
                    </strong>
                </td>
            </tr>
            <tr>
                <th>Contact Phone</th>
                <td>{{ $payroll->user->phone ?? 'N/A' }}</td>
                <th>Disbursed Date</th>
                <td>{{ $payroll->paid_at ? $payroll->paid_at->format('d F Y') : 'Pending Payout' }}</td>
            </tr>
        </table>

        <div class="section-title">Earnings &amp; Deductions Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th colspan="2" style="background: #F5F5F4; text-align: center; color: #292524;">Earnings (Aamdani)</th>
                    <th colspan="2" style="background: #F5F5F4; text-align: center; color: #292524;">Deductions (Katauti)</th>
                </tr>
                <tr>
                    <th width="30%">Component</th>
                    <th width="20%" class="text-right">Amount (PKR)</th>
                    <th width="30%">Component</th>
                    <th width="20%" class="text-right">Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Monthly Wage</td>
                    <td class="text-right">{{ number_format($payroll->basic_salary, 2) }}</td>
                    <td>Tax / Provident Deduction</td>
                    <td class="text-right">0.00</td>
                </tr>
                <tr>
                    <td>Overtime Hours ({{ number_format($payroll->overtime_hours, 1) }}h @ {{ number_format($payroll->overtime_rate, 2) }}/h)</td>
                    <td class="text-right">{{ number_format($payroll->overtime_hours * $payroll->overtime_rate, 2) }}</td>
                    <td>Advances &amp; Disciplinary Deductions</td>
                    <td class="text-right">{{ number_format($payroll->deductions, 2) }}</td>
                </tr>
                <tr>
                    <td>Special Allowances / Event Bonus</td>
                    <td class="text-right">{{ number_format($payroll->allowances, 2) }}</td>
                    <td>Other Recoveries</td>
                    <td class="text-right">0.00</td>
                </tr>
                <tr style="font-weight: bold; background: #FFFFFF;">
                    <td>Total Gross Earnings</td>
                    <td class="text-right">{{ number_format($payroll->basic_salary + ($payroll->overtime_hours * $payroll->overtime_rate) + $payroll->allowances, 2) }}</td>
                    <td>Total Deductions</td>
                    <td class="text-right">{{ number_format($payroll->deductions, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 14px; font-weight: bold; text-transform: uppercase;">
                    Net Disbursed Salary Payable:
                </span>
                <span style="font-size: 20px; font-weight: bold; color: #171513;">
                    PKR {{ number_format($payroll->net_salary, 2) }}
                </span>
            </div>
            @if(!empty($payroll->notes))
                <div style="font-size: 11px; color: #78716C; margin-top: 8px;">
                    <strong>Remarks:</strong> {{ $payroll->notes }}
                </div>
            @endif
        </div>

        <div style="margin-top: 40px; display: flex; justify-content: space-between; text-align: center; font-size: 11px;">
            <div style="width: 200px; border-top: 1px solid #78716C; padding-top: 5px;">
                Employee Signature / Thumb
            </div>
            <div style="width: 200px; border-top: 1px solid #78716C; padding-top: 5px;">
                Manager / Accountant Signature
            </div>
            <div style="width: 200px; border-top: 1px solid #78716C; padding-top: 5px;">
                Authorized Venue Stamp
            </div>
        </div>

        <div style="margin-top: 25px; border-top: 1px dashed #D6D3D1; padding-top: 10px; font-size: 9px; color: #78716C; text-align: center;">
            This computer-generated document is issued by the VenueCloud Enterprise ERP Suite. Strictly strictly confidential.
        </div>
    </div>
</body>
</html>
