<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Agreement - {{ $booking->customer_name }} - Wedding_Hall</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #171513;
            line-height: 1.4;
            font-size: 12px;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #C6A15B;
            padding-bottom: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand-title {
            font-size: 24px;
            font-weight: bold;
            color: #171513;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #78716C;
            margin-top: 3px;
        }
        .contract-badge {
            background-color: #171513;
            color: #C6A15B;
            padding: 6px 14px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 13px;
            text-align: right;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #171513;
            border-bottom: 1px solid #E7E5E4;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .col {
            flex: 1;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #F5F5F4;
        }
        .info-table td.label {
            font-weight: 600;
            color: #44403C;
            width: 40%;
        }
        .finance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .finance-table th {
            background: #171513;
            color: #ffffff;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }
        .finance-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #E7E5E4;
        }
        .finance-table tr.total-row {
            font-weight: bold;
            background: #FAFAF9;
        }
        .terms {
            font-size: 9.5px;
            color: #44403C;
            margin-top: 12px;
            line-height: 1.35;
        }
        .terms ol {
            padding-left: 18px;
            margin: 4px 0;
        }
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
        }
        .sig-box {
            width: 45%;
            border-top: 1px dashed #78716C;
            text-align: center;
            padding-top: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .no-print {
            background: #F5F5F4;
            padding: 12px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .btn {
            background: #171513;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-gold {
            background: #C6A15B;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn btn-gold">🖨️ Print Agreement (A4)</button>
    <a href="{{ url()->current() }}?pdf=1" class="btn">📥 Download Official PDF</a>
    <a href="{{ route('calendar') }}" class="btn" style="background: #44403C;">⬅️ Return to Calendar</a>
</div>

<div class="container">
    <div class="header">
        <div>
            <h1 class="brand-title">WEDDING_HALL</h1>
            <div class="brand-subtitle">
                {{ $booking->tenant->name ?? 'Wedding_Hall Banquet & Events Group' }}<br>
                Venue: {{ $booking->hall->venue->name ?? 'Main Banquet Complex' }} | Phone: {{ $booking->tenant->phone ?? '+92 300 1234567' }}
            </div>
        </div>
        <div class="contract-badge">
            OFFICIAL EVENT CONTRACT<br>
            <span style="font-size: 11px; font-weight: normal; color: #fff;">Ref: #{{ substr($booking->id, 0, 8) }}</span>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <div class="section-title">Host & Client Information</div>
            <table class="info-table">
                <tr><td class="label">Host Name:</td><td><strong>{{ $booking->customer_name }}</strong></td></tr>
                <tr><td class="label">Contact Phone:</td><td>{{ $booking->customer_phone }}</td></tr>
                <tr><td class="label">CNIC / ID:</td><td>{{ $booking->customer_cnic ?? 'N/A' }}</td></tr>
                <tr><td class="label">Email Address:</td><td>{{ $booking->customer_email ?? 'N/A' }}</td></tr>
            </table>
        </div>
        <div class="col">
            <div class="section-title">Reservation Details</div>
            <table class="info-table">
                <tr><td class="label">Banquet Hall:</td><td><strong>{{ $booking->hall->name }}</strong></td></tr>
                <tr><td class="label">Event Date:</td><td><strong>{{ \Carbon\Carbon::parse($booking->event_date)->format('l, d F Y') }}</strong></td></tr>
                <tr><td class="label">Operational Shift:</td><td><strong style="color: #C6A15B;">{{ $booking->slot_type }} ({{ \App\Support\ShiftWindows::labelForSlot($booking->hall?->tenant?->shift_windows, $booking->slot_type) }})</strong></td></tr>
                <tr><td class="label">Event Function:</td><td>{{ $booking->event_type }} ({{ $booking->guest_count }} Guests)</td></tr>
            </table>
        </div>
    </div>

    <div class="section-title">Financial Calculation & Billing Summary</div>
    <table class="finance-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Rate / Basis</th>
                <th style="text-align: center;">Units / Headcount</th>
                <th style="text-align: right;">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Catering Menu Package ({{ $booking->package->name ?? 'Custom Per-Head Feast' }})</td>
                <td style="text-align: center;">PKR {{ number_format($booking->per_head_rate, 2) }}</td>
                <td style="text-align: center;">{{ $booking->guest_count }} Heads</td>
                <td style="text-align: right;">{{ number_format($booking->per_head_rate * $booking->guest_count, 2) }}</td>
            </tr>
            <tr>
                <td>Hall Space & Climate Conditioning Base Rent</td>
                <td style="text-align: center;">Standard</td>
                <td style="text-align: center;">1 Shift</td>
                <td style="text-align: right;">{{ number_format($booking->hall_rent, 2) }}</td>
            </tr>
            @if($booking->addons_total > 0)
            <tr>
                <td>Decor, Lighting, Audio Stage & Addons</td>
                <td style="text-align: center;">Custom Addons</td>
                <td style="text-align: center;">-</td>
                <td style="text-align: right;">{{ number_format($booking->addons_total, 2) }}</td>
            </tr>
            @endif
            @if($booking->discount_amount > 0)
            <tr style="color: #171513;">
                <td>Special Approved Discount</td>
                <td style="text-align: center;">Concession</td>
                <td style="text-align: center;">-</td>
                <td style="text-align: right;">- {{ number_format($booking->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($booking->tax_amount > 0)
            <tr>
                <td>Provincial Sales Tax (SRB / PRA SST)</td>
                <td style="text-align: center;">Tax</td>
                <td style="text-align: center;">-</td>
                <td style="text-align: right;">+ {{ number_format($booking->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row" style="font-size: 13px; background: #F5F5F4;">
                <td colspan="3"><strong>GRAND TOTAL PAYABLE</strong></td>
                <td style="text-align: right; color: #171513;"><strong>PKR {{ number_format($booking->grand_total, 2) }}</strong></td>
            </tr>
            <tr style="color: #8F7239; font-weight: 600;">
                <td colspan="3">Token Advance Received (Initial Deposit)</td>
                <td style="text-align: right;">PKR {{ number_format($booking->advance_paid, 2) }}</td>
            </tr>
            <tr style="font-size: 13px; background: #E7E5E4; color: #171513; font-weight: bold;">
                <td colspan="3">NET BALANCE DUE BEFORE EVENT COMMENCEMENT</td>
                <td style="text-align: right;">PKR {{ number_format($booking->balance_due, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Legal Terms & Hospitality Policies</div>
    <div class="terms">
        <ol>
            <li><strong>Shift Timing & Punctuality:</strong> The banquet slot is strictly defined for the allocated hours. Management reserves the right to dim main illumination 15 minutes post-cutoff.</li>
            <li><strong>Guest Count Verification:</strong> The final guest count cannot be reduced within 72 hours of the event date. Additional attendees will be charged at the per-head contract rate.</li>
            <li><strong>Uninterrupted Power Backup:</strong> In case of grid failure, automatic commercial generator backup is engaged within 45 seconds. Diesel fuel margins are included under standard operations.</li>
            <li><strong>Government Regulations:</strong> Sound limits, fireworks prohibitions, and one-dish/multiple-dish provincial banquet ordinances must be strictly observed by the client and guests.</li>
            <li><strong>Payment Milestones:</strong> 50% second installment is due 14 days before the event. The final balance settlement must be cleared 48 hours prior to function start time.</li>
        </ol>
    </div>

    <div class="signatures">
        <div class="sig-box">
            Authorized Banquet Representative<br>
            <span style="font-size: 10px; color: #78716C;">Wedding_Hall Management</span>
        </div>
        <div class="sig-box">
            Client / Host Signature<br>
            <span style="font-size: 10px; color: #78716C;">{{ $booking->customer_name }}</span>
        </div>
    </div>
</div>

</body>
</html>
