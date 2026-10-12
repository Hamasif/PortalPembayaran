<x-layouts.student title="Riwayat Pembayaran">
    <div>
        <h1 class="text-xl font-bold text-ink">Riwayat Pembayaran</h1>
        <p class="mt-1 text-sm text-muted">Semua transaksi pembayaran yang pernah Anda buat.</p>
    </div>

    <div class="mt-5">
        @if ($payments->isEmpty())
            <x-empty-state icon="history" title="Belum ada transaksi"
                description="Riwayat pembayaran Anda akan tampil di sini setelah Anda membuat transaksi pertama." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">Waktu</th>
                                <th class="table-th">Tagihan</th>
                                <th class="table-th">Periode</th>
                                <th class="table-th">Nominal</th>
                                <th class="table-th">Status</th>
                                <th class="table-th"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payments as $payment)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td whitespace-nowrap">{{ optional($payment->created_at)->format('d M Y H:i') }}</td>
                                    <td class="table-td font-mono text-xs">{{ $payment->bill->bill_number }}</td>
                                    <td class="table-td">{{ $payment->bill->academicTerm->label() }}</td>
                                    <td class="table-td font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="table-td"><x-status-pill :status="$payment->status->value" /></td>
                                    <td class="table-td text-right">
                                        @if ($payment->isSuccess())
                                            <a href="{{ route('student.payments.receipt', $payment) }}" class="btn-ghost !px-3 !py-1.5 text-xs">
                                                Bukti
                                            </a>
                                        @elseif ($payment->status->value === 'pending')
                                            <a href="{{ route('student.payments.show', $payment) }}" class="btn-ghost !px-3 !py-1.5 text-xs">
                                                Lanjutkan
                                            </a>
                                        @else
                                            <a href="{{ route('student.payments.show', $payment) }}" class="btn-ghost !px-3 !py-1.5 text-xs">
                                                Detail
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line p-4">
                    <x-pagination :paginator="$payments" />
                </div>
            </div>
        @endif
    </div>
</x-layouts.student>
