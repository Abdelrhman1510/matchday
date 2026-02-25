<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Analytics Report - {{ $cafe->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            background: #fff;
        }
        
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #c8ff00;
        }
        
        .info-card .label {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .info-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }
        
        .info-card .change {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .info-card .change.positive {
            color: #10b981;
        }
        
        .info-card .change.negative {
            color: #ef4444;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
        }
        
        table thead {
            background: #1e293b;
            color: white;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        table tbody tr:hover {
            background: #f8fafc;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c8ff00;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-confirmed {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $cafe->name }} - Analytics Report</h1>
        <div class="subtitle">Generated on {{ $generatedAt }}</div>
    </div>
    
    <!-- Performance Overview -->
    <div class="info-grid">
        <div class="info-card">
            <div class="label">Total Bookings</div>
            <div class="value">{{ number_format($performanceStats['bookings']) }}</div>
            @if($performanceStats['bookings_change'] != 0)
            <div class="change {{ $performanceStats['bookings_change'] > 0 ? 'positive' : 'negative' }}">
                {{ $performanceStats['bookings_change'] > 0 ? '+' : '' }}{{ $performanceStats['bookings_change'] }}% vs last period
            </div>
            @endif
        </div>
        
        <div class="info-card">
            <div class="label">Revenue</div>
            <div class="value">${{ number_format($performanceStats['revenue'] / 100, 2) }}</div>
            @if($performanceStats['revenue_change'] != 0)
            <div class="change {{ $performanceStats['revenue_change'] > 0 ? 'positive' : 'negative' }}">
                {{ $performanceStats['revenue_change'] > 0 ? '+' : '' }}{{ $performanceStats['revenue_change'] }}% vs last period
            </div>
            @endif
        </div>
        
        <div class="info-card">
            <div class="label">Occupancy Rate</div>
            <div class="value">{{ number_format($performanceStats['occupancy_rate'], 1) }}%</div>
        </div>
        
        <div class="info-card">
            <div class="label">Customer Rating</div>
            <div class="value">{{ number_format($performanceStats['rating'], 1) }}</div>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <h2 class="section-title">Bookings Details</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Match</th>
                <th>Branch</th>
                <th>Guests</th>
                <th>Status</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
            <tr>
                <td>{{ $booking->created_at->format('M d, Y H:i') }}</td>
                <td>{{ $booking->customer->name ?? 'N/A' }}</td>
                <td>
                    @if($booking->match)
                        {{ $booking->match->homeTeam->name ?? 'TBD' }} vs {{ $booking->match->awayTeam->name ?? 'TBD' }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $booking->branch->name ?? 'N/A' }}</td>
                <td>{{ $booking->guests_count ?? 0 }}</td>
                <td>
                    <span class="status-badge status-{{ $booking->status }}">
                        {{ ucfirst($booking->status) }}
                    </span>
                </td>
                <td>${{ number_format(($booking->payment->amount ?? 0) / 100, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                    No bookings found for this period
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <!-- Footer -->
    <div class="footer">
        <p><strong>{{ $cafe->name }}</strong> - Cafe Analytics Report</p>
        <p>This report contains confidential information. Handle with care.</p>
    </div>
</body>
</html>
