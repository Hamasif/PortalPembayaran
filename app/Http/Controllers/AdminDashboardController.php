<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\PaymentStatus;
use App\Enums\StudentStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Statistik dari data backend sungguhan.
        $stats = [
            'students_total' => Student::count(),
            'students_active' => Student::where('student_status', StudentStatus::Active->value)->count(),
            'accounts_pending' => User::where('role', 'student')
                ->where('account_status', 'pending_verification')->count(),
            'bills_total' => Bill::whereNot('status', BillStatus::Cancelled->value)->count(),
            'bills_total_amount' => (int) Bill::whereNot('status', BillStatus::Cancelled->value)->sum('amount'),
            'bills_unpaid_amount' => (int) Bill::whereIn('status', [BillStatus::Unpaid->value, BillStatus::PartiallyPaid->value])
                ->get()->sum(fn (Bill $bill) => $bill->remainingAmount()),
            'payments_success_amount' => (int) Payment::where('status', PaymentStatus::Success->value)->sum('amount'),
            'payments_success_count' => Payment::where('status', PaymentStatus::Success->value)->count(),
            'payments_pending_count' => Payment::where('status', PaymentStatus::Pending->value)->count(),
        ];

        // Grafik pemasukan 6 bulan terakhir (pembayaran sukses).
        $monthly = Payment::query()
            ->where('status', PaymentStatus::Success->value)
            ->where('paid_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $months->push([
                'label' => $month->translatedFormat('M Y'),
                'total' => (int) ($monthly[$key]->total ?? 0),
                'count' => (int) ($monthly[$key]->count ?? 0),
            ]);
        }

        // Grafik distribusi status tagihan.
        $billStatusCounts = Bill::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.dashboard', [
            'stats' => $stats,
            'monthlyRevenue' => $months,
            'billStatusCounts' => $billStatusCounts,
            'revenueChartJson' => json_encode([
                'type' => 'line',
                'data' => [
                    'labels' => $months->pluck('label'),
                    'datasets' => [
                        [
                            'label' => 'Total terbayar',
                            'data' => $months->pluck('total'),
                            'borderColor' => '#087F5B',
                            'backgroundColor' => 'rgba(8,127,91,0.08)',
                            'fill' => true,
                            'tension' => 0.35,
                            'pointRadius' => 4,
                            'borderWidth' => 2,
                        ],
                    ],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['x' => ['grid' => ['display' => false]]],
                ],
            ], JSON_UNESCAPED_SLASHES),
            'billStatusChartJson' => json_encode([
                'type' => 'doughnut',
                'data' => [
                    'labels' => collect(BillStatus::labels())
                        ->only($billStatusCounts->keys()->all())
                        ->values(),
                    'datasets' => [
                        [
                            'data' => $billStatusCounts->values(),
                            'backgroundColor' => ['#087F5B', '#0EA5E9', '#EAB308', '#94A3B8'],
                            'borderWidth' => 0,
                        ],
                    ],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'cutout' => '62%',
                    'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxHeight' => 6]]],
                ],
            ], JSON_UNESCAPED_SLASHES),
        ]);
    }
}
