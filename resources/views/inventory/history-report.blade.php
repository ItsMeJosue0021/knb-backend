<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory History Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #222;
        }

        .header {
            display: table;
            width: auto;
            color: #000;
            text-decoration: none;
            margin-bottom: 14px;
        }

        .logo {
            display: table-cell;
            width: 60px;
            height: 60px;
            border-radius: 9999px;
            object-fit: cover;
            vertical-align: middle;
        }

        .brand {
            display: table-cell;
            padding-left: 8px;
            font-weight: 700;
            font-size: 16px;
            line-height: 1.2;
            vertical-align: middle;
        }

        .brand .title {
            display: block;
            font-size: 16px;
        }

        .brand .subtitle {
            display: block;
            font-size: 11px;
            font-weight: 400;
        }

        .report-heading {
            width: 100%;
            text-align: center;
            margin: 12px 0 8px;
        }

        .report-title {
            font-size: 16px;
            margin: 0;
        }

        .meta {
            font-size: 10px;
            color: #555;
            text-align: center;
        }

        .summary {
            font-size: 11px;
            text-align: center;
            margin-bottom: 8px;
        }

        .item-name {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            margin-top: 4px;
            color: #333;
        }

        .report-description {
            font-size: 10px;
            color: #444;
            margin: 0 0 10px;
            line-height: 1.45;
            text-align: justify;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px 6px;
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
    @php
        $historyItemName = $filters['item_name'] ?? '';
        $historyCollection = collect($history ?? []);

        if (empty($historyItemName) && $historyCollection->isNotEmpty()) {
            $firstHistoryRow = $historyCollection->first();
            $historyItemName = $firstHistoryRow['inventory_item_name']
                ?? $firstHistoryRow['item_name']
                ?? $firstHistoryRow['source_item_name']
                ?? $firstHistoryRow['source_name']
                ?? $firstHistoryRow['notes']
                ?? 'N/A';
        }

        if (empty($historyItemName)) {
            $historyItemName = 'N/A';
        }
    @endphp

    <div class="header">
        <img class="logo" src="{{ public_path('logo.png') }}" alt="logo">
        <div class="brand">
            <span class="title">Kalinga ng Kababaihan</span>
            <span class="subtitle">Women's League Las Pinas</span>
        </div>
    </div>

    <div class="report-heading">
        <div class="report-title">Inventory History Report</div>
        <div class="item-name">Item: {{ $historyItemName }}</div>
        <div class="meta">
            Generated: {{ $generatedAt->format('Y-m-d H:i') }}
            <span class="muted">|</span>
            Item: {{ $historyItemName }}
            @if (!empty($filters['item_name']) || !empty($filters['inventory_item_id']) || !empty($filters['category']) || !empty($filters['sub_category']) || !empty($filters['unit']) || !empty($filters['type']) || !empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['near_expiration_days']))
                <span class="muted">|</span>
                Filters:
                InventoryItem={{ $filters['inventory_item_id'] ?? 'Any' }},
                Category={{ $filters['category'] ?? 'Any' }},
                Subcategory={{ $filters['sub_category'] ?? 'Any' }},
                Unit={{ $filters['unit'] ?? 'Any' }},
                Type={{ $filters['type'] ?? 'Any' }},
                Date={{ $filters['start_date'] ?? 'Any' }} to {{ $filters['end_date'] ?? 'Any' }},
                NearExpiryDays={{ $filters['near_expiration_days'] ?? 'Any' }}
            @endif
        </div>
    </div>

    <div class="summary">
        Total History Rows: {{ count($history) }}
    </div>

    <p class="report-description">
        This report provides a chronological record of inventory transactions, including incoming, outgoing, and
        adjustment entries,
        together with related source item and project or donation references. It supports auditability, stock
        reconciliation, and accountability.
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Occurred</th>
                <th style="width: 9%;">Type</th>
                <th style="width: 10%;">Quantity</th>
                <th style="width: 7%;">Unit</th>
                <th style="width: 9%;">Remaining Qty.</th>
                <th style="width: 11%;">Status</th>
                <th style="width: 9%;">Expiry Date</th>
                <th style="width: 11%;">Project</th>
                <th style="width: 10%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($history as $entry)
                @php
                    $entryType = strtolower((string) ($entry['type'] ?? ''));
                    $quantityValue = is_numeric($entry['quantity'] ?? null) ? (float) $entry['quantity'] : 0;
                    $quantitySign = '';
                    if ($entryType === 'in') {
                        $quantitySign = '+';
                    } elseif ($entryType === 'out') {
                        $quantitySign = '-';
                    }
                    $quantityDisplay = $quantitySign . ($quantityValue === 0 ? '0' : $entry['quantity']);
                    $remainingQuantity = $entry['source_item_remaining_quantity']
                        ?? $entry['inventory_remaining_quantity']
                        ?? $entry['remaining_stock']
                        ?? '-';
                    $expiryDateValue = $entry['source_item_expiry_date'] ?? '-';
                    $isExpired = false;
                    if ($expiryDateValue !== '-' && $expiryDateValue !== null && $expiryDateValue !== '') {
                        $isExpired = strtotime((string) $expiryDateValue) <= strtotime(now()->toDateString());
                    }
                    $hasRemainingStock = false;
                    if (is_numeric($remainingQuantity)) {
                        $hasRemainingStock = ((float) $remainingQuantity) > 0;
                    }
                    $isUnavailable = $isExpired || !$hasRemainingStock;
                @endphp
                <tr>
                    <td>{{ optional($entry['occurred_at'])->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ strtoupper($entry['type'] ?? '-') }}</td>
                    <td>{{ $quantityDisplay }}</td>
                    <td>{{ $entry['unit'] !== '' ? $entry['unit'] : '-' }}</td>
                    <td>{{ $remainingQuantity }}</td>
                    <td>{{ $isUnavailable ? 'Unavailable' : 'Available' }}</td>
                    <td>{{ $entry['source_item_expiry_date'] ?? '-' }}</td>
                    <td>{{ $entry['project_title'] ?? '-' }}</td>
                    <td>{{ $entry['notes'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="muted">No inventory history records found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
