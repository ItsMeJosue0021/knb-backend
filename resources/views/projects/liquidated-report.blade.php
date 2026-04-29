<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Liquidation Report</title>
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
            margin-bottom: 16px;
        }
        .report-description {
            font-size: 11px;
            color: #444;
            margin: 0 0 10px;
            line-height: 1.45;
            text-align: justify;
        }
        .label {
            font-weight: bold;
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
        <div class="report-title">Project Liquidation Report</div>
        <div class="meta">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</div>
    </div>

    <div class="section">
        <div><span class="label">Title:</span> {{ $project->title }}</div>
        <div><span class="label">Date:</span> {{ $project->date }}</div>
        <div><span class="label">Description:</span> {{ $project->description }}</div>
    </div>

    <p class="report-description">
        This report details the proposed project resources beside the actual liquidated items used, including quantities, units, and notes.
        It provides a formal reference for post-activity documentation, resource utilization accountability, and checking whether the planned materials were accomplished.
    </p>

    <div class="section">
        <div class="label" style="margin-bottom: 6px;">Planned vs Actual Comparison</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 28%;">Proposed Item</th>
                    <th style="width: 14%;">Proposed Qty</th>
                    <th style="width: 28%;">Actual Item(s) Used</th>
                    <th style="width: 14%;">Actual Qty</th>
                    <th style="width: 16%;">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($comparisonRows as $row)
                <tr>
                    <td>
                        @if (!empty($row['proposed']))
                            <div>{{ $row['proposed']['name'] ?? '-' }}</div>
                            <div class="muted">
                                {{ $row['proposed']['category_name'] ?? 'Uncategorized' }}
                                @if (!empty($row['proposed']['sub_category_name']))
                                    &bull; {{ $row['proposed']['sub_category_name'] }}
                                @endif
                            </div>
                            @if (!empty($row['proposed']['notes']))
                                <div class="muted">Notes: {{ $row['proposed']['notes'] }}</div>
                            @endif
                        @else
                            <span class="muted">No proposal</span>
                        @endif
                    </td>
                    <td>
                        @if (!empty($row['proposed']))
                            {{ $row['proposed_quantity'] }} {{ $row['proposed']['unit'] ?? '' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if (!empty($row['actual']))
                            @foreach ($row['actual'] as $actual)
                                <div>{{ $actual['name'] ?? $actual['item_name'] ?? 'Unknown item' }}</div>
                                <div class="muted">
                                    {{ $actual['category_name'] ?? 'Uncategorized' }}
                                    @if (!empty($actual['sub_category_name']))
                                        &bull; {{ $actual['sub_category_name'] }}
                                    @endif
                                </div>
                                @if (!empty($actual['notes']))
                                    <div class="muted">Notes: {{ $actual['notes'] }}</div>
                                @endif
                                @if (!$loop->last)
                                    <div style="height: 6px;"></div>
                                @endif
                            @endforeach
                        @else
                            <span class="muted">No actual items yet</span>
                        @endif
                    </td>
                    <td>
                        @if (($row['actual_quantity'] ?? 0) > 0)
                            <div>{{ $row['actual_quantity'] }} {{ $row['actual'][0]['unit'] ?? ($row['proposed']['unit'] ?? '') }}</div>
                            @if (($row['excess_quantity'] ?? 0) > 0)
                                <div class="muted">Excess: +{{ $row['excess_quantity'] }} {{ $row['actual'][0]['unit'] ?? ($row['proposed']['unit'] ?? '') }}</div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-transform: capitalize;">
                        <div>{{ $row['status'] ?? 'missing' }}</div>
                        @if (($row['excess_quantity'] ?? 0) > 0)
                            <div class="muted">+{{ $row['excess_quantity'] }} over planned</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No proposed or liquidated items found for this project.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="label" style="margin-bottom: 6px;">Actual Liquidated Items</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 45%;">Item</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 20%;">Unit</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($resources as $resource)
                <tr>
                    <td>{{ $resource['name'] ?? $resource['item_name'] ?? 'Unknown item' }}</td>
                    <td>{{ $resource['quantity'] ?? $resource['used_quantity'] ?? 0 }}</td>
                    <td>{{ $resource['unit'] ?? '-' }}</td>
                    <td>{{ $resource['notes'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No liquidated items found for this project.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
