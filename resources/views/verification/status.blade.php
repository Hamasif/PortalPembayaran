<x-layouts.guest title="Status Verifikasi">
    <div class="mx-auto w-full max-w-lg px-4 py-12">
        <div class="card p-8 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-accent-100 text-accent-700">
                <x-lucide-hourglass class="h-7 w-7" />
            </span>
            <h1 class="text-2xl font-bold text-ink">Menunggu Pemeriksaan Admin</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-muted">
                Akun Google Anda berhasil terhubung, namun data mahasiswa belum dapat dipastikan
                otomatis. Tim administrator akan memeriksa data Anda.
            </p>

            <div class="mt-6 rounded-lg border border-line bg-surface px-4 py-4 text-left">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Email terverifikasi</dt>
                        <dd class="truncate font-medium text-ink">{{ $user->email }}</dd>
                    </div>
                    @if ($student)
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">NIM</dt>
                            <dd class="font-medium text-ink">{{ $student->nim }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Nama (data resmi)</dt>
                            <dd class="font-medium text-ink">{{ $student->full_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Status verifikasi</dt>
                            <dd><x-status-pill :status="'pending_verification'" /></dd>
                        </div>
                    @else
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Status verifikasi</dt>
                            <dd><x-status-pill :status="'pending_verification'" /></dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="mt-6 rounded-lg border border-accent-200 bg-accent-100 px-4 py-3 text-xs leading-relaxed text-accent-700">
                Sementara ini Anda <strong>belum dapat mengakses tagihan dan pembayaran</strong>.
                Silakan cek kembali nanti atau hubungi bagian akademik jika Anda yakin data Anda sudah benar.
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="btn-secondary w-full">
                    <x-lucide-log-out class="h-4 w-4" /> Keluar
                </button>
            </form>
        </div>
    </div>
</x-layouts.guest>
