<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formal Quotation - {{ $booking->customer_name }} - {{ $booking->tenant->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #171513; line-height: 1.4; font-size: 12px; margin: 0; padding: 0; background: #fff; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #C6A15B; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .brand-title { font-size: 24px; font-weight: bold; color: #171513; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-family: Georgia, serif; }
        .brand-subtitle { font-size: 11px; color: #78716C; margin-top: 3px; }
        .badge { background-color: #171513; color: #C6A15B; padding: 6px 14px; font-weight: bold; font-size: 12px; text-align: right; text-transform: uppercase; }
        .section-title { font-size: 12px; font-weight: bold; color: #171513; border-bottom: 1px solid #E7E5E4; padding-bottom: 4px; margin: 15px 0 8px 0; text-transform: uppercase; font-family: Georgia, serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background-color: #FAFAF9; color: #44403C; font-weight: bold; text-align: left; padding: 6px 8px; border: 1px solid #E7E5E4; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border: 1px solid #E7E5E4; }
        .total-box { background-color: #F8F5EF; border: 1px solid #C6A15B; padding: 12px; margin-top: 15px; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .no-print { margin-bottom: 15px; text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()" style="padding: 8px 16px; background: #171513; color: #C6A15B; border: 1px solid #C6A15B; cursor: pointer; font-weight: bold;">
                Print Quotation / Save as PDF
            </button>
        </div>

        <div class="header">
            <div>
                <h1 class="brand-title">{{ $booking->tenant->name }}</h1>
                <div class="brand-subtitle">{{ $booking->tenant->address ?? 'Main Boulevard, Lahore' }} • Phone: {{ $booking->tenant->phone }}</div>
            </div>
            <div class="badge">
                Official Estimate / Quotation<br>
                <small style="font-size: 9px; color: #fff;">Ref: {{ $booking->booking_reference ?? substr($booking->id, 0, 8) }}</small>
            </div>
        </div>

        <div class="section-title">Host &amp; Event Schedule</div>
        <table>
            <tr>
                <th width="20%">Host Name</th>
                <td width="30%"><strong>{{ $booking->customer_name }}</strong></td>
                <th width="20%">Event Function</th>
                <td width="30%"><strong>{{ $booking->event_type }}</strong></td>
            </tr>
            <tr>
                <th>Mobile Number</th>
                <td>{{ $booking->customer_phone }}</td>
                <th>Reserved Date</th>
                <td><strong>{{ \Carbon\Carbon::parse($booking->event_date)->format('l, d F Y') }}</strong></td>
            </tr>
            <tr>
                <th>Banquet Hall</th>
                <td>{{ $booking->hall->name }}</td>
                <th>Operational Shift</th>
                <td><strong>{{ $booking->slot_type }} ({{ $booking->hall->slot_timings[$booking->slot_type] ?? 'Standard Shift' }})</strong></td>
            </tr>
            <tr>
                <th>Guest Headcount</th>
                <td>{{ number_format($booking->guest_count) }} Guaranteed Heads</td>
                <th>Quotation Date</th>
                <td>{{ date('d M Y') }} (Valid for 7 days)</td>
            </tr>
        </table>

        <div class="section-title">Culinary Menu &amp; Package</div>
        <p style="margin: 4px 0 8px 0; font-weight: bold; color: #292524;">
            Package: {{ $booking->package ? $booking->package->name : 'Custom Per-Head Menu' }} (PKR {{ number_format($booking->per_head_rate) }}/Head)
        </p>

        <div class="section-title">Itemized Financial Computation</div>
        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Calculation Unit</th>
                    <th class="text-right">Rate (PKR)</th>
                    <th class="text-right">Line Total (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Catering Food Service</strong></td>
                    <td>{{ $booking->guest_count }} Guests</td>
                    <td class="text-right">{{ number_format($booking->per_head_rate) }}</td>
                    <td class="text-right">{{ number_format($booking->per_head_rate * $booking->guest_count) }}</td>
                </tr>
                <tr>
                    <td><strong>Hall Rental Charge</strong></td>
                    <td>Full Shift Allocation</td>
                    <td class="text-right">{{ number_format($booking->hall_rent) }}</td>
                    <td class="text-right">{{ number_format($booking->hall_rent) }}</td>
                </tr>
                @if($booking->addons_total > 0)
                <tr>
                    <td><strong>Hospitality &amp; Decor Addons</strong></td>
                    <td>Selected Services</td>
                    <td class="text-right">—</td>
                    <td class="text-right">{{ number_format($booking->addons_total) }}</td>
                </tr>
                @endif
                @if($booking->discount_amount > 0)
                <tr>
                    <td style="color: #171513;"><strong>Management Courtesy Discount</strong></td>
                    <td>Special Concession</td>
                    <td class="text-right">—</td>
                    <td class="text-right" style="color: #171513;">- {{ number_format($booking->discount_amount) }}</td>
                </tr>
                @endif
                @if($booking->tax_amount > 0)
                <tr>
                    <td><strong>Provincial Services Sales Tax (PRA / SRB)</strong></td>
                    <td>Statutory Rate</td>
                    <td class="text-right">—</td>
                    <td class="text-right">{{ number_format($booking->tax_amount) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="total-box">
            <table style="margin: 0; border: none;">
                <tr style="border: none;">
                    <td style="border: none; font-size: 14px; font-weight: bold; color: #171513;">
                        ESTIMATED TOTAL QUOTATION:
                    </td>
                    <td style="border: none; text-align: right; font-size: 18px; font-weight: bold; color: #8F7239;">
                        PKR {{ number_format($booking->grand_total) }}
                    </td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; font-size: 10px; color: #78716C;" colspan="2">
                        * Token deposit of PKR {{ number_format($booking->grand_total * 0.25) }} (25%) is required to convert this quotation into a locked reservation.
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 35px; border-top: 1px dashed #D6D3D1; padding-top: 15px; font-size: 10px; color: #78716C; text-align: center;">
            Issued by {{ $booking->tenant->name }} Concierge Terminal • Powered by Riwaj Grand ERP
        </div>
    </div>
</body>
</html>
