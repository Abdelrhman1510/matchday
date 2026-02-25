<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports Dashboard — {{ now()->format('Y-m-d') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #1e293b; background: #fff; font-size: 13px; line-height: 1.5; }

        .header {
            background: #1e293b;
            color: #fff;
            padding: 28px 32px;
            margin-bottom: 28px;
        }
        .header h1 { font-size: 24px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; }
        .header .meta { font-size: 12px; color: #94a3b8; }
        .header .accent { color: #c8ff00; }

        .section { margin-bottom: 28px; page-break-inside: avoid; }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 14px;
        }

        /* Stats grid */
        .stats-grid { width: 100%; border-collapse: collapse; }
        .stats-grid td { padding: 10px 14px; vertical-align: top; width: 33.33%; }
        .stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px;
        }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 4px; }
        .stat-value { font-size: 22px; font-weight: bold; color: #1e293b; }
        .stat-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        /* Data table */
        .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .data-table th {
            background: #1e293b;
            color: #fff;
            text-align: left;
            padding: 9px 12px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
        .data-table tr:nth-child(even) td { background: #f8fafc; }
        .data-table tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue  { background: #dbeafe; color: #1e40af; }
        .badge-purple{ background: #ede9fe; color: #5b21b6; }

        .two-col { width: 100%; border-collapse: collapse; }
        .two-col td { width: 50%; padding: 0 8px; vertical-align: top; }
        .two-col td:first-child { padding-left: 0; }
        .two-col td:last-child { padding-right: 0; }

        .footer {
            margin-top: 32px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reports <span class="accent">Dashboard</span></h1>
        <div class="meta">
            Generated: {{ now()->format('F j, Y \a\t g:i A') }} &nbsp;|&nbsp;
            Period: Last <strong>{{ $period }}</strong> days
        </div>
    </div>

    {{-- KEY METRICS --}}
    <div class="section">
        <div class="section-title">Key Metrics</div>
        <table class="stats-grid">
            <tr>
                <td>
                    <div class="stat-box">
                        <div class="stat-label">Total Bookings</div>
                        <div class="stat-value">{{ number_format($bookingReport['total_bookings']) }}</div>
                        <div class="stat-sub">{{ $bookingReport['completion_rate'] }}% completion rate</div>
                    </div>
                </td>
                <td>
                    <div class="stat-box">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">${{ number_format($revenueReport['total_revenue'] / 100, 2) }}</div>
                        <div class="stat-sub">
                            {{ $revenueReport['growth_rate'] >= 0 ? '+' : '' }}{{ $revenueReport['growth_rate'] }}% vs previous period
                        </div>
                    </div>
                </td>
                <td>
                    <div class="stat-box">
                        <div class="stat-label">Active Cafes</div>
                        <div class="stat-value">{{ number_format($cafePerformance['active_cafes']) }}</div>
                        <div class="stat-sub">{{ $cafePerformance['occupancy_rate'] }}% occupancy rate</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- TWO COLUMN: Booking + Revenue Details --}}
    <div class="section">
        <div class="section-title">Report Details</div>
        <table class="two-col">
            <tr>
                <td>
                    <table class="data-table">
                        <thead>
                            <tr><th colspan="2">Booking Report</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Total Bookings</td><td><strong>{{ number_format($bookingReport['total_bookings']) }}</strong></td></tr>
                            <tr><td>Avg Duration</td><td><strong>{{ $bookingReport['avg_duration'] }}h</strong></td></tr>
                            <tr><td>Completion Rate</td><td><strong>{{ $bookingReport['completion_rate'] }}%</strong></td></tr>
                        </tbody>
                    </table>
                </td>
                <td>
                    <table class="data-table">
                        <thead>
                            <tr><th colspan="2">Revenue Report</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Total Revenue</td><td><strong>${{ number_format($revenueReport['total_revenue'] / 100, 2) }}</strong></td></tr>
                            <tr><td>Avg Transaction</td><td><strong>${{ number_format($revenueReport['avg_transaction'] / 100, 2) }}</strong></td></tr>
                            <tr><td>Growth Rate</td><td><strong>{{ $revenueReport['growth_rate'] > 0 ? '+' : '' }}{{ $revenueReport['growth_rate'] }}%</strong></td></tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- REVENUE BREAKDOWN --}}
    <div class="section">
        <div class="section-title">Revenue Breakdown by Payment Type</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment Type</th>
                    <th>Revenue</th>
                    <th>Share</th>
                </tr>
            </thead>
            <tbody>
                @php $totalRev = array_sum($revenueBreakdown['data']); @endphp
                @foreach($revenueBreakdown['labels'] as $i => $label)
                    @php
                        $amount = $revenueBreakdown['data'][$i] ?? 0;
                        $share = $totalRev > 0 ? round(($amount / $totalRev) * 100, 1) : 0;
                        $badges = ['badge-green', 'badge-blue', 'badge-purple'];
                    @endphp
                    <tr>
                        <td><span class="badge {{ $badges[$i % 3] }}">{{ $label }}</span></td>
                        <td>${{ number_format($amount / 100, 2) }}</td>
                        <td>{{ $share }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- TOP PERFORMING CAFES --}}
    @if(count($topCafes['labels']) > 0)
    <div class="section">
        <div class="section-title">Top Performing Cafes (by Revenue)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cafe</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topCafes['labels'] as $i => $cafe)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $cafe }}</td>
                        <td>${{ number_format(($topCafes['data'][$i] ?? 0) / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- BOOKING TRENDS --}}
    <div class="section">
        <div class="section-title">Booking Trends (Last 30 Days)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bookings</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookingTrends['labels'] as $i => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ number_format($bookingTrends['data'][$i] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Reports Dashboard &bull; Generated by Cafe Platform &bull; {{ now()->format('Y-m-d H:i:s') }}
    </div>

</body>
</html>
