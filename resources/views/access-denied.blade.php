<x-layouts.guest title="Akses Ditolak">
    <div class="mx-auto w-full max-w-lg px-4 py-12">
        <div class="card p-8 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                <x-lucide-shield-x class="h-7 w-7" />
            </span>
            <h1 class="text-2xl font-bold text-ink">Akses Ditolak</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-muted">
                Email Google yang Anda gunakan <strong>tidak termasuk domain resmi kampus</strong> yang
                diizinkan oleh sistem. Portal ini hanya dapat diakses menggunakan email resmi
                universitas/fakultas.
            </p>

            <div class="mt-6 rounded-lg border border-line bg-surface px-4 py-3 text-left text-xs leading-relaxed text-muted">
                <p class="font-semibold text-ink">Mengapa ini terjadi?</p>
                <p class="mt-1">
                    Sistem membatasi login ke domain email yang didaftarkan administrator
                    (misalnya <span class="font-mono">student.universitas.ac.id</span> atau
                    <span class="font-mono">universitas.ac.id</span>).
                </p>
                <p class="mt-2 font-semibold text-ink">Apa yang bisa saya lakukan?</p>
                <p class="mt-1">
                    Keluar dari akun Google ini dan login kembali menggunakan email resmi kampus Anda,
                    atau hubungi bagian TI/akademik untuk memastikan domain Anda terdaftar.
                </p>
            </div>

            <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                <a href="{{ route('login.google') }}" class="btn-primary flex-1">
                    <x-lucide-log-in class="h-4 w-4" /> Coba Login Lain
                </a>
                <a href="{{ route('landing') }}" class="btn-secondary flex-1">
                    <x-lucide-home class="h-4 w-4" /> Beranda
                </a>
            </div>
        </div>
    </div>
</x-layouts.guest>
