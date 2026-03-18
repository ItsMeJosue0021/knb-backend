<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Activity Logs Report</title>
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
            word-wrap: break-word;
            max-width: 0;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }

        .muted {
            color: #777;
        }

        .status-ok {
            color: #0f766e;
            font-weight: 700;
        }

        .status-fail {
            color: #b91c1c;
            font-weight: 700;
        }
    </style>
</head>
<body>
    @php
        $totalLogs = count($logs);
        $successCount = collect($logs)->filter(function ($log) {
            return !empty($log->status_code) && (int) $log->status_code < 400;
        })->count();
        $failedCount = collect($logs)->filter(function ($log) {
            return !empty($log->status_code) && (int) $log->status_code >= 400;
        })->count();

        $activeFilters = [];
        if ($filters['search'] !== '') {
            $activeFilters[] = "Search={$filters['search']}";
        }
        if ($filters['severity'] !== '') {
            $activeFilters[] = "Severity={$filters['severity']}";
        }
        if ($filters['action_type'] !== '') {
            $activeFilters[] = "ActionType={$filters['action_type']}";
        }
        if ($filters['status_filter'] !== '') {
            $activeFilters[] = "Status={$filters['status_filter']}";
        }
        if ($filters['status_code'] !== '') {
            $activeFilters[] = "StatusCode={$filters['status_code']}";
        }
        if ($filters['start_date'] !== '' || $filters['end_date'] !== '') {
            $start = $filters['start_date'] !== '' ? $filters['start_date'] : 'Any';
            $end = $filters['end_date'] !== '' ? $filters['end_date'] : 'Any';
            $activeFilters[] = "Date={$start} to {$end}";
        }

        $filtersText = count($activeFilters) > 0 ? implode(" | ", $activeFilters) : "None";
    @endphp

    <div class="header">
        <img class="logo" src="{{ public_path('logo.png') }}" alt="logo">
        <div class="brand">
            <span class="title">Kalinga ng Kababaihan</span>
            <span class="subtitle">Women's League Las Pinas</span>
        </div>
    </div>

    <div class="report-heading">
        <div class="report-title">Admin Activity Logs Report</div>
        <div class="meta">
            Generated: {{ $generatedAt->format('Y-m-d H:i') }}
            <span class="muted">|</span>
            Filters: {{ $filtersText }}
        </div>
    </div>

    <div class="summary">
        Total Logs: {{ $totalLogs }} |
        Successful: {{ $successCount }} |
        Failed: {{ $failedCount }}
    </div>

    <p class="report-description">
        This report summarizes administrative and super-admin activities recorded by the system based on the selected filters.
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Timestamp</th>
                <th style="width: 22%;">Actor</th>
                <th style="width: 33%;">Action</th>
                <th style="width: 10%;">IP</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 8%;">Severity</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                @php
                    $actor = $log->actor;
                    $fullName = trim(implode(" ", array_filter([
                        optional($actor)->first_name,
                        optional($actor)->middle_name,
                        optional($actor)->last_name,
                    ])));
                    $actorDisplay = $fullName !== '' ? $fullName : optional($actor)->email;
                    $status = $log->status_code;
                    $statusDisplay = $status === null ? '-' : ((int)$status >= 400 ? "{$status} Failed" : "{$status} Success");
                    $statusClass = (!is_null($status) && (int) $status >= 400) ? "status-fail" : "status-ok";
                @endphp
                <tr>
                    <td>{{ optional($log->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $actorDisplay ?: 'Unknown' }}</td>
                    <td>{{ $log->action ?? '-' }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                    <td class="{{ $statusClass }}">{{ $statusDisplay }}</td>
                    <td>{{ strtoupper($log->severity ?? 'LOW') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No admin activity logs found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
