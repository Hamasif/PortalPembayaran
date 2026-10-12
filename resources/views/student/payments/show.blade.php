<x-layouts.student title="Pembayaran QRIS">
    <div class="mx-auto max-w-xl">
        <a href="{{ route('student.bills.show', $payment->bill) }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
            <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke detail tagihan
        </a>

        <div class="card overflow-hidden">
            <div class="border-b border-line bg-surface px-6 py-4 text-center">
                <p class="text-sm text-muted">Total Pembayaran</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-ink">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                <p class="mt-1 font-mono text-xs text-muted">{{ $payment->bill->bill_number }}</p>
            </div>

            <div class="p-6">
                @if ($payment->isSuccess())
                    {{-- Status sukses --}}
                    <div class="text-center">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50">
                            <x-lucide-check-circle-2 class="h-9 w-9 text-brand-600" />
                        </span>
                        <h1 class="mt-4 text-xl font-bold text-ink">Pembayaran Berhasil</h1>
                        <p class="mt-2 text-sm text-muted">
                            Transaksi terverifikasi pada {{ optional($payment->paid_at)->format('d M Y H:i') }} WIB.
                        </p>
                        <a href="{{ route('student.payments.receipt', $payment) }}" class="btn-primary mt-6">
                            <x-lucide-file-check-2 class="h-4 w-4" /> Lihat Bukti Pembayaran
                        </a>
                    </div>
                @elseif ($payment->status->value === 'failed')
                    <div class="text-center">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
                            <x-lucide-x-circle class="h-9 w-9 text-red-600" />
                        </span>
                        <h1 class="mt-4 text-xl font-bold text-ink">Pembayaran Gagal</h1>
                        <p class="mt-2 text-sm text-muted">Transaksi tidak berhasil diproses. Silakan coba bayar kembali.</p>
                        <form method="POST" action="{{ route('student.payments.store') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="bill_id" value="{{ $payment->bill_id }}">
                            <button type="submit" class="btn-primary">Coba Bayar Lagi</button>
                        </form>
                    </div>
                @elseif ($payment->status->value === 'expired')
                    <div class="text-center">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                            <x-lucide-clock-alert class="h-9 w-9 text-slate-500" />
                        </span>
                        <h1 class="mt-4 text-xl font-bold text-ink">Kode QRIS Kedaluwarsa</h1>
                        <p class="mt-2 text-sm text-muted">Waktu pembayaran habis sebelum transaksi diselesaikan.</p>
                        <form method="POST" action="{{ route('student.payments.store') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="bill_id" value="{{ $payment->bill_id }}">
                            <button type="submit" class="btn-primary">Buat QRIS Baru</button>
                        </form>
                    </div>
                @else
                    {{-- Pending: tampilkan QRIS + contoh VA + panduan --}}
                    <div class="text-center">
                        <div class="mx-auto flex w-fit items-center gap-2 rounded-full bg-accent-100 px-4 py-1.5 text-sm font-semibold text-accent-700">
                            <span class="h-2 w-2 rounded-full bg-accent-500 animate-pulse-dot"></span>
                            Menunggu Pembayaran — kedaluwarsa dalam <span data-countdown="{{ $payment->expires_at?->toIso8601String() }}">--:--</span>
                        </div>

                        @if ($qrPayload)
                            <div class="mx-auto mt-6 w-fit rounded-2xl border-4 border-brand-600 p-4 shadow-inner">
                                <canvas id="qris-canvas" data-qris="{{ $qrPayload }}" width="220" height="220" aria-label="Kode QRIS"></canvas>
                            </div>
                            <p class="mt-4 text-sm leading-relaxed text-muted">
                                Pindai kode QRIS di atas menggunakan aplikasi bank atau e-wallet Anda,
                                lalu selesaikan pembayaran. Halaman ini akan menampilkan status terbaru
                                setelah transaksi diverifikasi.
                            </p>
                        @else
                            <x-alert type="warning" class="mt-6 text-left">
                                Kode QRIS tidak dapat dimuat. Silakan muat ulang halaman atau hubungi administrator.
                            </x-alert>
                        @endif

                        {{-- Contoh Virtual Account (mock) --}}
                        @if ($isMock)
                            <div class="mt-6 rounded-xl border border-dashed border-sky-300 bg-sky-50 p-5 text-left">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-[10px] font-bold tracking-widest text-sky-700 uppercase">Contoh Virtual Account</p>
                                        <p class="mt-0.5 text-xs text-sky-600">Bank Simulasi — hanya untuk pengembangan</p>
                                    </div>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600 text-white">
                                        <x-lucide-landmark class="h-5 w-5" />
                                    </span>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-sky-200 bg-white px-4 py-3">
                                    <p class="font-mono text-lg font-bold tracking-wider text-ink" id="va-number"
                                       data-copy="{{ $vaNumber }}">{{ $vaDisplay }}</p>
                                    <button type="button" data-copy-button class="btn-secondary !px-3 !py-1.5 text-xs shrink-0">
                                        <x-lucide-copy class="h-3.5 w-3.5" /> Salin
                                    </button>
                                </div>

                                <p class="mt-2 text-[11px] leading-relaxed text-sky-700">
                                    Nominal transfer harus tepat <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>.
                                    Pada mode ini VA hanyalah <strong>contoh tampilan</strong> dan tidak terhubung ke bank sungguhan —
                                    gunakan tombol simulasi di bawah untuk menandai transaksi terbayar.
                                </p>
                            </div>

                            <div class="mt-4 rounded-lg border border-dashed border-accent-500/50 bg-accent-50 px-4 py-3 text-left">
                                <p class="flex items-center gap-2 text-xs font-bold text-accent-700">
                                    <x-lucide-flask-conical class="h-4 w-4" /> MODE PENGEMBANGAN — provider mock
                                </p>
                                <p class="mt-1 text-xs leading-relaxed text-accent-700">
                                    QR dan VA di atas adalah simulasi dan tidak dapat dibayar sungguhan.
                                    Gunakan tombol di bawah untuk mensimulasikan pembayaran berhasil.
                                </p>
                                <form method="POST" action="{{ route('student.payments.simulate', $payment) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn-accent w-full">
                                        <x-lucide-play class="h-4 w-4" /> Simulasikan Pembayaran
                                    </button>
                                </form>
                            </div>
                        @endif

                        {{-- Panduan cara pembayaran --}}
                        <div class="mt-6 rounded-xl border border-line bg-surface p-5 text-left">
                            <p class="flex items-center gap-2 text-sm font-bold text-ink">
                                <x-lucide-circle-help class="h-4 w-4 text-brand-600" /> Cara Melakukan Pembayaran
                            </p>

                            <div class="mt-3 space-y-2">
                                <details class="group rounded-lg border border-line bg-white px-4 py-3" open>
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-ink">
                                        <span class="flex items-center gap-2">
                                            <x-lucide-qr-code class="h-4 w-4 text-brand-600" /> Scan QRIS (m-banking / e-wallet)
                                        </span>
                                        <x-lucide-chevron-down class="h-4 w-4 text-muted transition group-open:rotate-180" />
                                    </summary>
                                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-xs leading-relaxed text-muted">
                                        <li>Buka aplikasi mobile banking atau e-wallet Anda (BCA, Mandiri, BRI, GoPay, OVO, DANA, dll.).</li>
                                        <li>Pilih menu <strong>Bayar / Scan QRIS</strong>, lalu arahkan kamera ke kode QR di halaman ini.</li>
                                        <li>Periksa nama merchant dan pastikan nominal <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong> sudah sesuai.</li>
                                        <li>Selesaikan transaksi dan simpan notifikasi pembayaran Anda.</li>
                                        <li>Status di halaman ini akan berubah menjadi <strong>Berhasil</strong> setelah terverifikasi otomatis.</li>
                                    </ol>
                                </details>

                                <details class="group rounded-lg border border-line bg-white px-4 py-3">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-ink">
                                        <span class="flex items-center gap-2">
                                            <x-lucide-landmark class="h-4 w-4 text-brand-600" /> Transfer Virtual Account (ATM / m-banking)
                                        </span>
                                        <x-lucide-chevron-down class="h-4 w-4 text-muted transition group-open:rotate-180" />
                                    </summary>
                                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-xs leading-relaxed text-muted">
                                        <li>Salin nomor Virtual Account yang tertera di atas.</li>
                                        <li>Buka ATM atau m-banking, pilih menu <strong>Transfer → Virtual Account</strong>.</li>
                                        <li>Masukkan nomor VA, lalu periksa bahwa nama dan nominal yang muncul sesuai.</li>
                                        <li>Konfirmasi dan selesaikan transfer.</li>
                                        <li>Bukti pembayaran otomatis tersedia setelah transaksi terverifikasi.</li>
                                    </ol>
                                </details>

                                <details class="group rounded-lg border border-line bg-white px-4 py-3">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-ink">
                                        <span class="flex items-center gap-2">
                                            <x-lucide-timer class="h-4 w-4 text-brand-600" /> Sebelum waktu habis
                                        </span>
                                        <x-lucide-chevron-down class="h-4 w-4 text-muted transition group-open:rotate-180" />
                                    </summary>
                                    <ul class="mt-3 list-disc space-y-1.5 pl-5 text-xs leading-relaxed text-muted">
                                        <li>Selesaikan pembayaran sebelum hitungan mundur berakhir; QRIS kedaluwarsa otomatis.</li>
                                        <li>Jika QRIS kedaluwarsa, buat QRIS baru dari halaman detail tagihan — tidak ada dana yang tertagih.</li>
                                        <li>Transaksi gagal/kedaluwarsa <strong>tidak mengurangi tagihan Anda</strong>; tagihan tetap aman.</li>
                                    </ul>
                                </details>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Ringkasan transaksi --}}
        <div class="card mt-5 p-6">
            <h2 class="text-sm font-bold text-ink">Informasi Transaksi</h2>
            <dl class="mt-3 space-y-2.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Metode</dt>
                    <dd class="font-medium text-ink uppercase">{{ $payment->payment_method }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Mata uang</dt>
                    <dd class="font-medium text-ink">{{ $payment->currency }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Dibuat</dt>
                    <dd class="font-medium text-ink">{{ $payment->created_at->format('d M Y H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Kedaluwarsa</dt>
                    <dd class="font-medium text-ink">{{ optional($payment->expires_at)->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('qris-canvas');
                if (!canvas) return;
                QRCode.toCanvas(canvas, canvas.dataset.qris, { width: 220, margin: 1 }, (error) => {
                    if (error) console.error('Gagal merender QRIS.', error);
                });
            });
        </script>
    @endpush
</x-layouts.student>
