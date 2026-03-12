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
        This report details the liquidated resources for the selected project, including item names, quantities, units, and notes.
        It provides a formal record for post-activity documentation and resource utilization accountability.
    </p>

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
                <td>{{ $resource->item?->name ?? 'Unknown item' }}</td>
                <td>{{ $resource->quantity }}</td>
                <td>{{ $resource->item?->unit ?? '-' }}</td>
                <td>{{ $resource->item?->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="muted">No liquidated items found for this project.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
