<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expenditure Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            display: table;
            width: auto;
            color: #000;
            text-decoration: none;
            margin-bottom: 16px;
        }

        .logo {
            display: table-cell;
            width: 70px;
            height: 70px;
            border-radius: 9999px;
            object-fit: cover;
            vertical-align: middle;
        }

        .brand {
            display: table-cell;
            padding-left: 8px;
            font-weight: 700;
            font-size: 18px;
            line-height: 1.2;
            vertical-align: middle;
        }

        .brand .title {
            display: block;
            font-size: 18px;
        }

        .brand .subtitle {
            display: block;
            font-size: 13px;
            font-weight: 400;
        }

        .report-heading {
            width: 100%;
            text-align: center;
            margin: 16px 0 12px;
        }

        .report-title {
            font-size: 18px;
            margin: 0;
        }

        .meta {
            font-size: 11px;
            color: #555;
            text-align: center;
        }

        .section {
            margin: 8px 0 16px;
        }

        .label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }

        .muted {
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        <img class="logo" src="{{ public_path('logo.png') }}" alt="logo">
        <div class="brand">
            <span class="title">Kalinga ng Kababaihan</span>
            <span class="subtitle">Women's League Las Piñas</span>
        </div>
    </div>

    <div class="report-heading">
        <div class="report-title">Expenditure Report</div>
        <div class="meta">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</div>
    </div>

    <div class="section">
        <div><span class="label">Reference:</span> {{ $expenditure->reference_number }}</div>
        <div><span class="label">Source:</span> {{ $expenditure->source_type === 'project_liquidation' ? 'Project Liquidation' : 'Manual Expense' }}</div>
        <div><span class="label">Project:</span> {{ $expenditure->project?->title ?? '-' }}</div>
        <div><span class="label">Name:</span> {{ $expenditure->name }}</div>
        <div><span class="label">Date Incurred:</span> {{ $expenditure->date_incurred }}</div>
        <div><span class="label">Amount:</span> {{ $expenditure->amount }}</div>
        <div><span class="label">Payment Method:</span> {{ $expenditure->payment_method }}</div>
        <div><span class="label">Status:</span> {{ $expenditure->status }}</div>
        <div><span class="label">Description:</span> {{ $expenditure->description }}</div>
        <div><span class="label">Notes:</span> {{ $expenditure->notes ?? '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Item</th>
                <th>Description</th>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 15%;">Unit Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit_price }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No items found for this expenditure.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
