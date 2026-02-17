<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Report</title>
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
        .summary {
            font-size: 12px;
            text-align: center;
            margin-bottom: 8px;
        }
        .report-description {
            font-size: 11px;
            color: #444;
            margin: 0 0 10px;
            line-height: 1.45;
            text-align: justify;
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
            <span class="subtitle">Women's League Las Pinas</span>
        </div>
    </div>

    <div class="report-heading">
        <div class="report-title">Inventory Report</div>
        <div class="meta">
            Generated: {{ $generatedAt->format('Y-m-d H:i') }}
            @if (!empty($filters['search']) || !empty($filters['category']) || !empty($filters['sub_category']) || array_key_exists('include_zero', $filters) || !empty($filters['near_expiration_days']))
                <span class="muted">|</span>
                Filters:
                Search={{ $filters['search'] ?? 'Any' }},
                Category={{ $filters['category'] ?? 'Any' }},
                Subcategory={{ $filters['sub_category'] ?? 'Any' }},
                IncludeZero={{ array_key_exists('include_zero', $filters) ? ($filters['include_zero'] ? 'Yes' : 'No') : 'No' }},
                NearExpiryDays={{ $filters['near_expiration_days'] ?? 'Any' }}
            @endif
        </div>
    </div>

    <div class="summary">
        Total Inventory Rows: {{ count($items) }}
    </div>

    <p class="report-description">
        This report presents current inventory balances grouped by subcategory based on the selected filters.
        When multiple units exist within the same subcategory, quantities are consolidated for display and detailed unit breakdowns are shown to support operational monitoring.
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Subcat ID</th>
                <th style="width: 22%;">Category</th>
                <th style="width: 22%;">Subcategory</th>
                <th style="width: 12%;">Quantity</th>
                <th style="width: 20%;">Unit Details</th>
                <th style="width: 14%;">Updated At</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>{{ $item['id'] }}</td>
                <td>{{ $item['category_name'] ?? '-' }}</td>
                <td>{{ $item['sub_category_name'] ?? '-' }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>
                    @if (!empty($item['has_mixed_units']))
                        Mixed ({{ $item['unit_breakdown_text'] ?? '-' }})
                    @else
                        {{ $item['unit'] !== '' ? $item['unit'] : '-' }}
                    @endif
                </td>
                <td>{{ optional($item['updated_at'])->format('Y-m-d H:i') ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted">No inventory records found for the selected filters.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
