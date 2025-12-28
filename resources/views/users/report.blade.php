<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Report</title>
    <style>
        /* Embed fonts locally so DomPDF can use them */
        @font-face {
            font-family: "Poppins";
            src: url("{{ public_path('fonts/Poppins-Regular.ttf') }}") format("truetype");
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: "Poppins";
            src: url("{{ public_path('fonts/Poppins-SemiBold.ttf') }}") format("truetype");
            font-weight: 600;
            font-style: normal;
        }

        @font-face {
            font-family: "Chewy";
            src: url("{{ public_path('fonts/Chewy-Regular.ttf') }}") format("truetype");
            font-weight: 400;
            font-style: normal;
        }
        body {
            font-family: "Poppins", Arial, sans-serif;
            margin: 24px;
            color: #1f2937;
        }

        /* DomPDF does not support flex; use table layout for horizontal alignment */
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
            font-family: "Chewy", "Arial Rounded MT Bold", Arial, sans-serif;
        }

        .brand .subtitle {
            display: block;
            font-size: 13px;
            font-family: "Poppins", Arial, sans-serif;
            font-weight: 400;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .meta {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: 600;
            font-family: "Poppins", Arial, sans-serif;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .report-heading {
            width: 100%;
            text-align: center;
            margin: 32px 0 12px;
        }

        .report-title {
            font-size: 18px;
            margin: 0;
            font-family: "Chewy", "Arial Rounded MT Bold", Arial, sans-serif;
        }

        .meta {
            text-align: center;
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
        <h1 class="report-title">List of System Users</h1>
        <div class="meta">
            Generated on: {{ $generatedAt->format('Y-m-d H:i') }}
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ trim($user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name) }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->contact_number ?? '-' }}</td>
                    <td>{{ optional($user->role)->name ?? 'N/A' }}</td>
                    <td>{{ optional($user->created_at)->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
