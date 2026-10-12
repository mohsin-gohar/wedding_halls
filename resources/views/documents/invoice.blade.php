<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Tax Invoice - #{{ $booking->booking_reference ?? substr($booking->id, 0, 8) }} - {{ $booking->tenant->name }}</title>
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
        .no-print { margin-bottom: 15px; text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()" style="padding: 8px 16px; background: #171513; color: #C6A15B; border: 1px solid #C6A15B; cursor: pointer; font-weight: bold;">
                Print Official Invoice / PDF
            </button>
        </div>

        <div class="header">
            <div>
                <h1 class="brand-title">{{ $booking->tenant->name }}</h1>
                <div class="brand-subtitle">{{ $booking->tenant->address ?? 'Main Boulevard, Lahore' }} • Phone: {{ $booking->tenant->phone }}</div>
                <div class="brand-subtitle">NTN / STRN: {{ $booking->tenant->ntn_number ?? '4209182-7' }} • Provincial PRA/SRB Registered</div>
            </div>
            <div class="badge">
                Official Tax Invoice<br>
                <small style="font-size: 9px; color: #fff;">Invoice #: INV-{{ $booking->booking_reference ?? substr($booking->id, 0, 8) }}</small>
            </div>
        </div>

        <div class="section-title">Billed To (Client Details)</div>
        <table>
            <tr>
                <th width="20%">Customer Name</th>
                <td width="30%"><strong>{{ $booking->customer_name }}</strong></td>
                <th width="20%">Invoice Date</th>
                <td width="30%">{{ date('d F Y') }}</td>
            </tr>
            <tr>
                <th>Contact Phone</th>
                <td>{{ $booking->customer_phone }}</td>
                <th>Event Date &amp; Shift</th>
                <td><strong>{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }} ({{ $booking->slot_type }})</strong></td>
            </tr>
            <tr>
                <th>Hall Reserved</th>
                <td>{{ $booking->hall->name }}</td>
                <th>Event Type</th>
                <td>{{ $booking->event_type }}</td>
            </tr>
        </table>

        <div class="section-title">Itemized Charges</div>
        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Units / Guests</th>
                    <th class="text-right">Unit Rate (PKR)</th>
                    <th class="text-right">Total Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Catering Service ({{ $booking->package ? $booking->package->name : 'Custom Menu' }})</strong></td>
                    <td>{{ $booking->guest_count }} Heads</td>
                    <td class="text-right">{{ number_format($booking->per_head_rate) }}</td>
                    <td class="text-right">{{ number_format($booking->per_head_rate * $booking->guest_count) }}</td>
                </tr>
                <tr>
                    <td><strong>Banquet Hall Shift Rental</strong></td>
                    <td>1 Shift (4 Hours)</td>
                    <td class="text-right">{{ number_format($booking->hall_rent) }}</td>
                    <td class="text-right">{{ number_format($booking->hall_rent) }}</td>
                </tr>
                @if($booking->addons_total > 0)
                <tr>
                    <td><strong>Hospitality &amp; Decor Add-ons</strong></td>
                    <td>Selected Extras</td>
                    <td class="text-right">—</td>
                    <td class="text-right">{{ number_format($booking->addons_total) }}</td>
                </tr>
                @endif
                @if($booking->discount_amount > 0)
                <tr>
                    <td style="color: #171513;"><strong>Concession / Discount</strong></td>
                    <td>1 Event</td>
                    <td class="text-right">—</td>
                    <td class="text-right" style="color: #171513;">- {{ number_format($booking->discount_amount) }}</td>
                </tr>
                @endif
                @if($booking->tax_amount > 0)
                <tr>
                    <td><strong>Provincial Services Sales Tax</strong></td>
                    <td>Applicable %</td>
                    <td class="text-right">—</td>
                    <td class="text-right">{{ number_format($booking->tax_amount) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="section-title">Payment History &amp; Receipts</div>
        <table>
            <thead>
                <tr>
                    <th>Receipt No</th>
                    <th>Payment Type</th>
                    <th>Method</th>
                    <th class="text-right">Paid Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($booking->payments as $p)
                <tr>
                    <td>{{ $p->receipt_no }}</td>
                    <td>{{ $p->payment_type }}</td>
                    <td>{{ $p->payment_method }}</td>
                    <td class="text-right" style="color: #8F7239; font-weight: bold;">
                        PKR {{ number_format($p->amount) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #78716C;">No payments received yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="total-box">
            <table style="margin: 0; border: none;">
                <tr style="border: none;">
                    <td style="border: none; font-size: 13px; font-weight: bold;">Grand Total:</td>
                    <td style="border: none; text-align: right; font-size: 14px; font-weight: bold;">PKR {{ number_format($booking->grand_total) }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; font-size: 13px; font-weight: bold; color: #8F7239;">Total Paid to Date:</td>
                    <td style="border: none; text-align: right; font-size: 14px; font-weight: bold; color: #8F7239;">PKR {{ number_format($booking->advance_paid) }}</td>
                </tr>
                <tr style="border-top: 1px solid #C6A15B;">
                    <td style="border: none; font-size: 15px; font-weight: bold; color: #171513; padding-top: 6px;">
                        NET BALANCE DUE:
                    </td>
                    <td style="border: none; text-align: right; font-size: 18px; font-weight: bold; color: #171513; padding-top: 6px;">
                        PKR {{ number_format($booking->balance_due) }}
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 40px; display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 200px;">
                <div style="border-bottom: 1px solid #78716C; margin-bottom: 4px; height: 35px;"></div>
                <small style="font-size: 10px; color: #78716C;">Customer / Host Signature</small>
            </div>
            <div style="text-align: center; width: 200px;">
                <div style="border-bottom: 1px solid #78716C; margin-bottom: 4px; height: 35px;"></div>
                <small style="font-size: 10px; color: #78716C;">Authorized Venue Accountant</small>
            </div>
        </div>
    </div>
</body>
</html>
