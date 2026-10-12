<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        $billsQuery = $student->bills()->with('academicTerm')->orderBy('due_date');

        $allBills = $billsQuery->get();

        $totalBilled = $allBills->whereNotIn('status', [BillStatus::Cancelled->value])->sum('amount');
        $totalPaid = $allBills->sum(fn ($bill) => $bill->paidAmount());
        $outstanding = max(0, $totalBilled - $totalPaid);

        // Tagihan mendekati tenggat: belum lunas, jatuh tempo <= 14 hari ke depan.
        $nearDue = $allBills
            ->filter(fn ($bill) => $bill->isPayable() && $bill->daysUntilDue() <= 14 && $bill->daysUntilDue() >= -30)
            ->sortBy('due_date')
            ->values();

        // Data grafik: sisa vs terbayar per semester (memakai data backend sungguhan).
        $chartData = $allBills
            ->groupBy(fn ($bill) => $bill->academicTerm->label())
            ->map(function ($group, $label) {
                $billed = $group->whereNotIn('status', [BillStatus::Cancelled->value])->sum('amount');
                $paid = $group->sum(fn ($bill) => $bill->paidAmount());

                return [
                    'label' => $label,
                    'billed' => $billed,
                    'paid' => $paid,
                ];
            })
            ->values();

        return view('student.dashboard', [
            'student' => $student,
            'totalBilled' => $totalBilled,
            'totalPaid' => $totalPaid,
            'outstanding' => $outstanding,
            'nearDue' => $nearDue,
            'recentBills' => $allBills->take(5),
            'hasBills' => $allBills->isNotEmpty(),
            'chartJson' => json_encode([
                'type' => 'bar',
                'data' => [
                    'labels' => $chartData->pluck('label'),
                    'datasets' => [
                        [
                            'label' => 'Diterbitkan',
                            'data' => $chartData->pluck('billed'),
                            'backgroundColor' => '#EAB308',
                            'borderRadius' => 6,
                        ],
                        [
                            'label' => 'Terbayar',
                            'data' => $chartData->pluck('paid'),
                            'backgroundColor' => '#087F5B',
                            'borderRadius' => 6,
                        ],
                    ],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'scales' => ['y' => ['beginAtZero' => true], 'x' => ['grid' => ['display' => false]]],
                    'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxHeight' => 6]]],
                ],
            ], JSON_UNESCAPED_SLASHES),
        ]);
    }
}
