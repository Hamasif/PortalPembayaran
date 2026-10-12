<x-layouts.student title="Bukti Pembayaran">
    <div class="mx-auto max-w-xl">
        <a href="{{ route('student.payments.history') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
            <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke riwayat
        </a>

        <div class="card overflow-hidden" id="receipt">
            <div class="bg-brand-700 px-6 py-5 text-center text-white">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white/15">
                    <x-lucide-badge-check class="h-6 w-6" />
                </span>
                <p class="mt-3 text-xs font-semibold tracking-widest uppercase">Bukti Pembayaran Resmi</p>
                <p class="mt-1 text-sm text-brand-100">{{ config('app.name') }}</p>
            </div>

            <div class="p-6">
                <div class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-center">
                    <p class="text-xs text-brand-700">Pembayaran terverifikasi</p>
                    <p class="mt-1 text-2xl font-bold text-brand-700">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                </div>

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Nomor tagihan</dt>
                        <dd class="font-mono text-xs font-medium text-ink">{{ $payment->bill->bill_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Nama mahasiswa</dt>
                        <dd class="font-medium text-ink">{{ $payment->bill->student->full_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">NIM</dt>
                        <dd class="font-medium text-ink">{{ $payment->bill->student->nim }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Periode</dt>
                        <dd class="font-medium text-ink">{{ $payment->bill->academicTerm->label() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Jenis tagihan</dt>
                        <dd class="font-medium text-ink">{{ $payment->bill->bill_type->label() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Metode</dt>
                        <dd class="font-medium uppercase text-ink">{{ $payment->payment_method }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Dibayar pada</dt>
                        <dd class="font-medium text-ink">{{ optional($payment->paid_at)->format('d M Y, H:i') }} WIB</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-line pt-3">
                        <dt class="text-muted">ID transaksi</dt>
                        <dd class="max-w-[60%] break-all text-right font-mono text-xs font-medium text-ink">
                            {{ $payment->external_transaction_id ?? $payment->idempotency_key }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-lg bg-surface px-4 py-3 text-center text-[11px] leading-relaxed text-muted">
                    Dokumen ini merupakan bukti pembayaran yang telah diverifikasi sistem.
                    Simpan atau cetak untuk keperluan administrasi Anda.
                </div>
            </div>
        </div>

        <button type="button" onclick="window.print()" class="btn-secondary mt-4 w-full">
            <x-lucide-printer class="h-4 w-4" /> Cetak / Simpan PDF
        </button>
    </div>
</x-layouts.student>
