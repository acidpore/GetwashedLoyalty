<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #4F46E5;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #4F46E5;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            padding: 15px;
            background: #F3F4F6;
            border-radius: 8px;
        }
        .stat-box {
            text-align: center;
        }
        .stat-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        thead {
            background: #4F46E5;
            color: white;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #E5E7EB;
        }
        tr:nth-child(even) {
            background: #F9FAFB;
        }
        .points-badge {
            background: #10B981;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #E5E7EB;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated on {{ $date }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="label">Total Customers</div>
            <div class="value">{{ $customers->count() }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Points</div>
            <div class="value">{{ $customers->sum($type . '_points') }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Visits</div>
            <div class="value">{{ $customers->sum($type . '_total_visits') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Points</th>
                <th>Total Visits</th>
                <th>Last Visit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $index => $customer)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $customer->user->name }}</td>
                <td>{{ $customer->user->phone }}</td>
                <td><span class="points-badge">{{ $customer->{$type . '_points'} }}</span></td>
                <td>{{ $customer->{$type . '_total_visits'} }}</td>
                <td>{{ $customer->{$type . '_last_visit_at'}?->format('d/m/Y H:i') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>© {{ now()->year }} Getwashed Loyalty System | Printed on {{ now()->format('d F Y H:i:s') }}</p>
    </div>
</body>
</html>
