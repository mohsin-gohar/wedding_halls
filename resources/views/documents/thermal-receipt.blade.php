<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $payment->receipt_no }} - Wedding_Hall</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 2mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 76mm;
            margin: 0 auto;
            padding: 2mm;
            font-size: 11px;
            color: #000;
            background: #fff;
            line-height: 1.25;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .brand {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 1px double #000;
            margin: 6px 0;
        }
        .flex {
            display: flex;
            justify-content: space-between;
        }
        .no-print {
            text-align: center;
            margin-bottom: 12px;
            background: #eee;
            padding: 8px;
            font-family: sans-serif;
            font-size: 12px;
        }
        .btn {
            background: #000;
            color: #fff;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            cursor: pointer;
            border: none;
            font-size: 11px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn">🖨️ Print 80mm Slip</button>
    <a href="{{ url()->current() }}?pdf=1" class="btn" style="background:#555;">PDF</a>
</div>

<div class="center">
    <div class="brand">WEDDING_HALL</div>
    <div>{{ $payment->tenant->name ?? 'Banquet & Convention Center' }}</div>
    <div>Venue: {{ $payment->booking->hall->venue->name ?? 'Grand Complex' }}</div>
    <div>Ph: {{ $payment->tenant->phone ?? '+92 300 1234567' }}</div>
</div>

<div class="divider"></div>

<div class="center bold">{{ $payment->payment_type === 'REFUND' ? 'CASH REFUND VOUCHER' : 'CASH PAYMENT RECEIPT' }}</div>
<div class="flex">
    <span>RCP #:</span>
    <span class="bold">{{ $payment->receipt_no }}</span>
</div>
<div class="flex">
    <span>DATE:</span>
    <span>{{ $payment->created_at->format('d/m/Y h:i A') }}</span>
</div>
<div class="flex">
    <span>OPERATOR:</span>
    <span>{{ $payment->receivedByUser->name ?? 'Front Desk' }}</span>
</div>

<div class="divider"></div>

<div><strong>HOST DETAILS:</strong></div>
<div class="flex">
    <span>Name:</span>
    <span class="bold">{{ $payment->booking->customer_name }}</span>
</div>
<div class="flex">
    <span>Phone:</span>
    <span>{{ $payment->booking->customer_phone }}</span>
</div>
<div class="flex">
    <span>Hall:</span>
    <span>{{ $payment->booking->hall->name }}</span>
</div>
<div class="flex">
    <span>Event Date:</span>
    <span class="bold">{{ \Carbon\Carbon::parse($payment->booking->event_date)->format('d-M-Y') }}</span>
</div>
<div class="flex">
    <span>Shift:</span>
    <span>{{ $payment->booking->slot_type }}</span>
</div>

<div class="divider"></div>

<div class="flex bold" style="font-size: 13px; margin: 4px 0;">
    <span>{{ $payment->payment_type === 'REFUND' ? 'AMOUNT REFUNDED:' : 'AMOUNT PAID:' }}</span>
    <span>PKR {{ number_format($payment->amount, 2) }}</span>
</div>
<div class="flex">
    <span>Payment Type:</span>
    <span>{{ str_replace('_', ' ', $payment->payment_type) }}</span>
</div>
<div class="flex">
    <span>Method:</span>
    <span>{{ $payment->payment_method }}</span>
</div>

<div class="double-divider"></div>

<div class="flex">
    <span>Event Grand Total:</span>
    <span>PKR {{ number_format($payment->booking->grand_total, 2) }}</span>
</div>
<div class="flex">
    <span>Total Advance Paid:</span>
    <span>PKR {{ number_format($payment->booking->advance_paid, 2) }}</span>
</div>
<div class="flex bold">
    <span>BALANCE DUE:</span>
    <span>PKR {{ number_format($payment->booking->balance_due, 2) }}</span>
</div>

<div class="divider"></div>

<div class="center" style="font-size: 9px; margin-top: 8px;">
    @if($payment->payment_type === 'REFUND')
        * Cash refund recorded against a cancelled event *<br>
    @else
        * Non-refundable Token Advance *<br>
        All milestone payments subject to company policy.<br>
    @endif
    Thank you for choosing Wedding_Hall!<br><br>
    *** COMPUTER GENERATED SLIP ***
</div>

</body>
</html>
