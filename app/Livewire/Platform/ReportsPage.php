<?php

namespace App\Livewire\Platform;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Services\ExportService;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Reports'])]
class ReportsPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.reports');
    }

    public $period = 30; // days

    public function downloadReport($type, $format)
    {
        $data = $this->generateReportData($type);

        if ($format === 'csv') {
            $filename = "{$type}_report_" . now()->format('Y-m-d') . ".csv";
            return ExportService::downloadCsv($filename, $data['headers'], $data['rows']);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadHTML($this->buildSimpleReportHtml($type, $data))
                ->setPaper('a4', 'portrait');
            return response()->streamDownload(
                fn() => print ($pdf->output()),
                "{$type}_report_" . now()->format('Y-m-d') . ".pdf",
                ['Content-Type' => 'application/pdf']
            );
        }
    }

    public function exportAllPdf()
    {
        $pdf = Pdf::loadView('exports.reports-pdf', [
            'period' => $this->period,
            'bookingReport' => $this->getBookingReportStats(),
            'revenueReport' => $this->getRevenueReportStats(),
            'cafePerformance' => $this->getCafePerformanceStats(),
            'bookingTrends' => $this->getBookingTrendsData(),
            'revenueBreakdown' => $this->getRevenueBreakdownData(),
            'topCafes' => $this->getTopPerformingCafes(),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            'reports_dashboard_' . now()->format('Y-m-d') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportAllExcel()
    {
        $bookingStats = $this->getBookingReportStats();
        $revenueStats = $this->getRevenueReportStats();
        $cafeStats = $this->getCafePerformanceStats();
        $bookingTrends = $this->getBookingTrendsData();
        $revenueBreakdown = $this->getRevenueBreakdownData();
        $topCafes = $this->getTopPerformingCafes();

        $data = [];

        // Section 1: Report Header
        $data[] = ['COMPREHENSIVE REPORTS DASHBOARD'];
        $data[] = ['Generated:', now()->format('Y-m-d H:i:s')];
        $data[] = ['Period:', $this->period . ' days'];
        $data[] = [];

        // Section 2: Booking Report
        $data[] = ['BOOKING REPORT'];
        $data[] = ['Metric', 'Value'];
        $data[] = ['Total Bookings', $bookingStats['total_bookings']];
        $data[] = ['Average Duration (hours)', $bookingStats['avg_duration']];
        $data[] = ['Completion Rate (%)', $bookingStats['completion_rate']];
        $data[] = [];

        // Section 3: Revenue Report
        $data[] = ['REVENUE REPORT'];
        $data[] = ['Metric', 'Value'];
        $data[] = ['Total Revenue', '$' . number_format($revenueStats['total_revenue'] / 100, 2)];
        $data[] = ['Average Transaction', '$' . number_format($revenueStats['avg_transaction'] / 100, 2)];
        $data[] = ['Growth Rate (%)', $revenueStats['growth_rate'] . '%'];
        $data[] = [];

        // Section 4: Cafe Performance
        $data[] = ['CAFE PERFORMANCE'];
        $data[] = ['Metric', 'Value'];
        $data[] = ['Active Cafes', $cafeStats['active_cafes']];
        $data[] = ['Average Rating', $cafeStats['avg_rating']];
        $data[] = ['Occupancy Rate (%)', $cafeStats['occupancy_rate'] . '%'];
        $data[] = [];

        // Section 5: Booking Trends (Last 30 Days)
        $data[] = ['BOOKING TRENDS (LAST 30 DAYS)'];
        $data[] = ['Date', 'Bookings'];
        foreach ($bookingTrends['labels'] as $index => $label) {
            $data[] = [$label, $bookingTrends['data'][$index] ?? 0];
        }
        $data[] = [];

        // Section 6: Revenue Breakdown by Payment Type
        $data[] = ['REVENUE BREAKDOWN BY PAYMENT TYPE'];
        $data[] = ['Payment Type', 'Revenue'];
        foreach ($revenueBreakdown['labels'] as $index => $label) {
            $amount = $revenueBreakdown['data'][$index] ?? 0;
            $data[] = [$label, '$' . number_format($amount / 100, 2)];
        }
        $data[] = [];

        // Section 7: Top Performing Cafes
        $data[] = ['TOP PERFORMING CAFES (BY REVENUE)'];
        $data[] = ['Cafe Name', 'Revenue'];
        foreach ($topCafes['labels'] as $index => $cafe) {
            $revenue = $topCafes['data'][$index] ?? 0;
            $data[] = [$cafe, '$' . number_format($revenue / 100, 2)];
        }

        $filename = 'comprehensive_reports_' . now()->format('Y-m-d_His') . '.csv';

        return ExportService::downloadCsvFlat($filename, $data);
    }

    private function generateReportData($type)
    {
        switch ($type) {
            case 'bookings':
                $stats = $this->getBookingReportStats();
                return [
                    'headers' => ['Metric', 'Value'],
                    'rows' => [
                        ['Total Bookings', $stats['total_bookings']],
                        ['Average Duration (hours)', $stats['avg_duration']],
                        ['Completion Rate (%)', $stats['completion_rate']],
                    ]
                ];

            case 'revenue':
                $stats = $this->getRevenueReportStats();
                return [
                    'headers' => ['Metric', 'Value'],
                    'rows' => [
                        ['Total Revenue ($)', number_format($stats['total_revenue'] / 100, 2)],
                        ['Average Transaction ($)', number_format($stats['avg_transaction'] / 100, 2)],
                        ['Growth Rate (%)', $stats['growth_rate']],
                    ]
                ];

            case 'cafe-performance':
                $stats = $this->getCafePerformanceStats();
                return [
                    'headers' => ['Metric', 'Value'],
                    'rows' => [
                        ['Active Cafes', $stats['active_cafes']],
                        ['Average Rating', $stats['avg_rating']],
                        ['Occupancy Rate (%)', $stats['occupancy_rate']],
                    ]
                ];

            default:
                return ['headers' => [], 'rows' => []];
        }
    }

    private function getBookingReportStats()
    {
        $startDate = Carbon::now()->subDays($this->period);

        $totalBookings = Booking::where('created_at', '>=', $startDate)->count();

        // Average duration: time between booking creation and match date
        $avgDuration = Booking::where('created_at', '>=', $startDate)
            ->whereHas('match')
            ->with('match')
            ->get()
            ->avg(function ($booking) {
                if ($booking->match) {
                    return $booking->created_at->diffInHours($booking->match->match_date);
                }
                return 0;
            });

        // Completion rate: completed/checked_in vs total
        $completedBookings = Booking::where('created_at', '>=', $startDate)
            ->whereIn('status', ['completed', 'checked_in'])
            ->count();

        $completionRate = $totalBookings > 0
            ? round(($completedBookings / $totalBookings) * 100, 1)
            : 0;

        return [
            'total_bookings' => $totalBookings,
            'avg_duration' => round($avgDuration, 1),
            'completion_rate' => $completionRate,
        ];
    }

    private function getRevenueReportStats()
    {
        $startDate = Carbon::now()->subDays($this->period);
        $previousStartDate = Carbon::now()->subDays($this->period * 2);

        $currentRevenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $previousRevenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', $previousStartDate)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $growthRate = $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        $transactionCount = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $avgTransaction = $transactionCount > 0
            ? $currentRevenue / $transactionCount
            : 0;

        return [
            'total_revenue' => $currentRevenue,
            'avg_transaction' => $avgTransaction,
            'growth_rate' => $growthRate,
        ];
    }

    private function getCafePerformanceStats()
    {
        $startDate = Carbon::now()->subDays($this->period);

        $activeCafes = Cafe::whereHas('subscriptions', function ($query) {
            $query->where('status', 'active')
                ->where('expires_at', '>', Carbon::now());
        })->count();

        $avgRating = Cafe::whereNotNull('avg_rating')
            ->avg('avg_rating');

        // Occupancy rate: booked seats / total seats across all matches in period
        $totalSeats = 0;
        $bookedSeats = 0;

        $cafes = Cafe::with([
            'branches.matches' => function ($query) use ($startDate) {
                $query->where('match_date', '>=', $startDate);
            }
        ])->get();

        foreach ($cafes as $cafe) {
            foreach ($cafe->branches as $branch) {
                foreach ($branch->matches as $match) {
                    $totalSeats += $branch->total_seats ?? 0;
                    $bookedSeats += Booking::where('match_id', $match->id)
                        ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                        ->sum('guests_count');
                }
            }
        }

        $occupancyRate = $totalSeats > 0
            ? round(($bookedSeats / $totalSeats) * 100, 1)
            : 0;

        return [
            'active_cafes' => $activeCafes,
            'avg_rating' => round($avgRating, 1),
            'occupancy_rate' => $occupancyRate,
        ];
    }

    private function getBookingTrendsData()
    {
        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');

            $bookings = Booking::whereDate('created_at', $date->toDateString())->count();
            $data[] = $bookings;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getRevenueBreakdownData()
    {
        $startDate = Carbon::now()->subDays($this->period);

        $bookingsRevenue = Payment::where('type', 'booking')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $subscriptionsRevenue = Payment::where('type', 'subscription')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $otherRevenue = Payment::whereNotIn('type', ['booking', 'subscription'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        return [
            'labels' => ['Bookings', 'Subscriptions', 'Other'],
            'data' => [$bookingsRevenue, $subscriptionsRevenue, $otherRevenue],
        ];
    }

    private function getTopPerformingCafes()
    {
        $startDate = Carbon::now()->subDays($this->period);

        $topCafes = Cafe::select('cafes.id', 'cafes.name')
            ->leftJoin('branches', 'cafes.id', '=', 'branches.cafe_id')
            ->leftJoin('bookings', 'branches.id', '=', 'bookings.branch_id')
            ->leftJoin('payments', 'bookings.id', '=', 'payments.booking_id')
            ->where('payments.status', 'completed')
            ->where('payments.created_at', '>=', $startDate)
            ->groupBy('cafes.id', 'cafes.name')
            ->selectRaw('SUM(payments.amount) as total_revenue')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return [
            'labels' => $topCafes->pluck('name')->toArray(),
            'data' => $topCafes->pluck('total_revenue')->toArray(),
        ];
    }

    public function exportPDF()
    {
        return $this->exportAllPdf();
    }

    private function buildSimpleReportHtml($type, $data): string
    {
        $title = ucwords(str_replace('-', ' ', $type)) . ' Report';
        $date = now()->format('F j, Y');
        $period = $this->period;
        $h0 = e($data['headers'][0]);
        $h1 = e($data['headers'][1]);
        $rows = '';
        foreach ($data['rows'] as $row) {
            $rows .= '<tr>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">' . e($row[0]) . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;font-weight:bold;">' . e($row[1]) . '</td>'
                . '</tr>';
        }
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;color:#1e293b;font-size:13px;padding:20px;}
.hdr{background:#1e293b;color:#fff;padding:20px 24px;border-radius:6px;margin-bottom:24px;}
.hdr h1{font-size:20px;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;}
.hdr small{color:#94a3b8;font-size:11px;}
table{width:100%;border-collapse:collapse;}
th{background:#1e293b;color:#fff;text-align:left;padding:9px 12px;font-size:11px;text-transform:uppercase;}
</style></head><body>
<div class="hdr">
  <h1>{$title}</h1>
  <small>Period: last {$period} days &bull; Generated: {$date}</small>
</div>
<table><thead><tr><th>{$h0}</th><th>{$h1}</th></tr></thead><tbody>{$rows}</tbody></table>
</body></html>
HTML;
    }

    public function exportCSV()
    {
        return $this->exportAllExcel();
    }

    public function render()
    {
        $bookingReport = $this->getBookingReportStats();
        $revenueReport = $this->getRevenueReportStats();
        $cafePerformance = $this->getCafePerformanceStats();
        $bookingTrends = $this->getBookingTrendsData();
        $revenueBreakdown = $this->getRevenueBreakdownData();
        $topCafes = $this->getTopPerformingCafes();

        return view('livewire.platform.reports-page', [
            'bookingReport' => $bookingReport,
            'revenueReport' => $revenueReport,
            'cafePerformance' => $cafePerformance,
            'bookingTrends' => $bookingTrends,
            'revenueBreakdown' => $revenueBreakdown,
            'topCafes' => $topCafes,
        ]);
    }
}
