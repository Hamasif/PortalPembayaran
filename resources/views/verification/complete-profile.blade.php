<x-layouts.guest title="Lengkapi Profil Mahasiswa">
    <div class="mx-auto w-full max-w-lg px-4 py-12">
        <div class="card p-8">
            <div class="text-center">
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white">
                    <x-lucide-user-round-check class="h-7 w-7" />
                </span>
                <h1 class="text-2xl font-bold text-ink">Lengkapi Profil Mahasiswa</h1>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    Untuk pertama kalinya, cocokkan akun Anda dengan data mahasiswa resmi kampus.
                </p>
            </div>

            <div class="mt-6 flex items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="Foto profil Google" class="h-10 w-10 rounded-full object-cover">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                    <p class="flex items-center gap-1.5 truncate text-xs text-muted">
                        <x-lucide-badge-check class="h-3.5 w-3.5 text-brand-600" />
                        {{ $user->email }} — terverifikasi, tidak dapat diubah
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('verification.complete.submit') }}" class="mt-6 space-y-4">
                @csrf

                <x-field name="nim" label="Nomor Induk Mahasiswa (NIM)" required>
                    <input type="text" id="nim" name="nim" value="{{ old('nim') }}"
                           placeholder="Contoh: 20230001" class="input" required autofocus>
                </x-field>

                <x-field name="cohort_year" label="Angkatan" required hint="Tahun masuk Anda, contoh: 2023">
                    <input type="number" id="cohort_year" name="cohort_year" min="1990" max="{{ date('Y') + 1 }}"
                           value="{{ old('cohort_year') }}" placeholder="2023" class="input" required>
                </x-field>

                <div class="rounded-lg border border-accent-200 bg-accent-100 px-4 py-3 text-xs leading-relaxed text-accent-700">
                    <strong>Catatan:</strong> Nama lengkap dan program studi akan diambil dari data resmi
                    kampus. Pastikan NIM dan angkatan sesuai dengan data induk yang terdaftar.
                </div>

                <button type="submit" class="btn-primary w-full">
                    <x-lucide-check-circle-2 class="h-4 w-4" /> Cocokkan Data Saya
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-xs text-muted">
            Data tidak cocok? Akun Anda akan masuk antrean pemeriksaan administrator.
        </p>
    </div>
</x-layouts.guest>
