<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expenditures Report</title>
    <style>
        body {
            font-family: "Poppins", DejaVu Sans, Arial, sans-serif;
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
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
        <div class="report-title">Expenditures Report</div>
        <div class="meta">
            Generated: {{ $generatedAt->format('Y-m-d H:i') }}
            @if ($startDate || $endDate)
                <span class="muted">|</span>
                Date filter:
                {{ $startDate ?? 'Any' }} to {{ $endDate ?? 'Any' }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Date</th>
                <th style="width: 18%;">Reference</th>
                <th style="width: 18%;">Name</th>
                <th>Description</th>
                <th style="width: 14%;">Amount</th>
                <th style="width: 14%;">Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($expenditures as $expenditure)
            <tr>
                <td>{{ $expenditure->date_incurred }}</td>
                <td>{{ $expenditure->reference_number }}</td>
                <td>{{ $expenditure->name }}</td>
                <td>{{ $expenditure->description }}</td>
                <td>{{ $expenditure->amount }}</td>
                <td>{{ $expenditure->status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted">No expenditures found for the selected date range.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
