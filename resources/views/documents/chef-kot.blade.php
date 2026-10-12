<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOT #{{ $kot->kot_number }} - Kitchen Preparation Ticket</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #111;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }
        .container {
            max-width: 750px;
            margin: 0 auto;
            border: 3px solid #000;
            padding: 20px;
        }
        .header {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .title {
            font-size: 26px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .badge {
            background: #000;
            color: #fff;
            font-size: 18px;
            padding: 6px 12px;
            font-weight: bold;
        }
        .headcount-box {
            background: #F5F5F4;
            border: 2px solid #000;
            padding: 10px;
            text-align: center;
            margin: 15px 0;
        }
        .headcount-num {
            font-size: 36px;
            font-weight: 900;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #E7E5E4;
            font-size: 14px;
            font-weight: bold;
        }
        .large {
            font-size: 16px;
            font-weight: bold;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            background: #000;
            color: #fff;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                border: 2px solid #000;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn">🖨️ Print Chef KOT</button>
    <a href="{{ url()->current() }}?pdf=1" class="btn" style="background: #333;">Download PDF</a>
</div>

<div class="container">
    <div class="header">
        <div>
            <h1 class="title">KITCHEN ORDER TICKET (KOT)</h1>
            <div>WEDDING_HALL CATERING PRODUCTION DIVISION</div>
        </div>
        <div class="badge">
            {{ $kot->kot_number }}
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; font-size: 14px;">
        <div>
            <strong>EVENT DATE:</strong> {{ \Carbon\Carbon::parse($kot->booking->event_date)->format('l, d-M-Y') }}<br>
            <strong>SHIFT:</strong> {{ $kot->booking->slot_type }} ({{ $kot->booking->slot_type === 'AFTERNOON' ? 'Lunch' : 'Dinner' }})<br>
            <strong>HALL:</strong> {{ $kot->booking->hall->name }}
        </div>
        <div style="text-align: right;">
            <strong>HOST:</strong> {{ $kot->booking->customer_name }}<br>
            <strong>EVENT:</strong> {{ $kot->booking->event_type }}<br>
            <strong>STATUS:</strong> [ {{ $kot->preparation_status }} ]
        </div>
    </div>

    <div class="headcount-box">
        <div style="font-size: 13px; text-transform: uppercase; font-weight: bold;">TOTAL CONFIRMED HEADCOUNT</div>
        <div class="headcount-num">{{ $kot->guest_count }} GUESTS</div>
    </div>

    <h3 style="margin-bottom: 5px; text-transform: uppercase; border-bottom: 2px solid #000;">1. Required Raw Material Allocations (Calculated)</h3>
    <table>
        <thead>
            <tr>
                <th>Ingredient Description</th>
                <th style="text-align: center;">Per Head Multiplier</th>
                <th style="text-align: right;">Total Required Weight / Quantity</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="large">Raw Meat (Mutton / Chicken Cut)</td>
                <td style="text-align: center;">220 g / guest</td>
                <td style="text-align: right;" class="large">{{ $kot->raw_meat_kg }} KG</td>
            </tr>
            <tr>
                <td class="large">Super Basmati Rice (Kachi / Sella)</td>
                <td style="text-align: center;">160 g / guest</td>
                <td style="text-align: right;" class="large">{{ $kot->raw_rice_kg }} KG</td>
            </tr>
            <tr>
                <td class="large">Cooking Oil / Banaspati Ghee</td>
                <td style="text-align: center;">50 ml / guest</td>
                <td style="text-align: right;" class="large">{{ $kot->raw_oil_liters }} Liters</td>
            </tr>
            <tr>
                <td>Fresh Dairy Yogurt (For Raita & Gravy)</td>
                <td style="text-align: center;">60 g / guest</td>
                <td style="text-align: right;">{{ round($kot->guest_count * 0.06, 2) }} KG</td>
            </tr>
            <tr>
                <td>Salad Ingredients (Cucumber, Onion, Lemon)</td>
                <td style="text-align: center;">50 g / guest</td>
                <td style="text-align: right;">{{ round($kot->guest_count * 0.05, 2) }} KG</td>
            </tr>
            <tr>
                <td>Dessert Ingredients (Khoya, Sugar, Flour)</td>
                <td style="text-align: center;">80 g / guest</td>
                <td style="text-align: right;">{{ round($kot->guest_count * 0.08, 2) }} KG</td>
            </tr>
        </tbody>
    </table>

    <h3 style="margin-bottom: 5px; text-transform: uppercase; border-bottom: 2px solid #000;">2. Approved Dishes to Prepare</h3>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Dishes to Prepare</th>
            </tr>
        </thead>
        <tbody>
            @if(is_array($kot->dishes_summary))
                @foreach($kot->dishes_summary as $cat => $dishes)
                    <tr>
                        <td style="font-weight: bold; width: 30%;">{{ is_string($cat) ? $cat : 'Dish Item' }}</td>
                        <td>
                            @if(is_array($dishes))
                                {{ implode(' • ', $dishes) }}
                            @else
                                {{ $dishes }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>Catering Feast</td>
                    <td>{{ json_encode($kot->dishes_summary) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($kot->custom_instructions)
    <div style="border: 1px solid #000; padding: 8px; margin-top: 10px; background: #FFFFFF;">
        <strong>Special Kitchen Instructions:</strong> {{ $kot->custom_instructions }}
    </div>
    @endif

    <div style="margin-top: 35px; display: flex; justify-content: space-between;">
        <div style="border-top: 1px solid #000; width: 40%; text-align: center; padding-top: 5px; font-weight: bold;">
            Head Bawarchi / Executive Chef
        </div>
        <div style="border-top: 1px solid #000; width: 40%; text-align: center; padding-top: 5px; font-weight: bold;">
            Kitchen Store Dispatcher
        </div>
    </div>
</div>

</body>
</html>
