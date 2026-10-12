<x-layouts.admin title="Verifikasi Akun">
    <div>
        <h1 class="text-xl font-bold text-ink">Verifikasi Akun Mahasiswa</h1>
        <p class="mt-1 text-sm text-muted">Akun yang tidak dapat dipastikan otomatis dan menunggu pemeriksaan Anda.</p>
    </div>

    <div class="mt-5">
        @if ($users->isEmpty())
            <x-empty-state icon="user-round-check" title="Tidak ada antrean verifikasi"
                description="Semua akun mahasiswa telah diproses. Akun baru yang gagal verifikasi otomatis akan muncul di sini." />
        @else
            <div class="space-y-4">
                @foreach ($users as $user)
                    <div class="card p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-3">
                                @if ($user->avatar)
                                    <img src="{{ $user->avatar }}" alt="" class="h-11 w-11 rounded-full object-cover">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-accent-500 text-sm font-bold text-ink">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-muted">{{ $user->email }} · Google terverifikasi</p>
                                    <p class="mt-1 text-xs text-muted">Terdaftar {{ $user->created_at->format('d M Y H:i') }}</p>

                                    @if ($user->student)
                                        <div class="mt-2 rounded-lg bg-surface px-3 py-2 text-xs">
                                            <span class="font-semibold text-ink">{{ $user->student->nim }}</span>
                                            — {{ $user->student->full_name }} ({{ $user->student->program_study }}, {{ $user->student->cohort_year }})
                                            @if ($user->student->user_id && $user->student->user_id !== $user->id)
                                                <span class="font-semibold text-red-600">· NIM dipakai akun lain</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="mt-2 rounded-lg bg-accent-50 px-3 py-2 text-xs text-accent-700">
                                            Akun belum pernah mencocokkan NIM — cari data mahasiswa resmi untuk penautan manual.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="shrink-0 space-y-2">
                                @if ($user->student)
                                    <form method="POST" action="{{ route('admin.verifications.approve', $user) }}" data-confirm="Setujui dan hubungkan akun ini ke NIM {{ $user->student->nim }}?">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $user->student->id }}">
                                        <button type="submit" class="btn-primary w-full lg:w-auto">
                                            <x-lucide-check class="h-4 w-4" /> Setujui & Tautkan
                                        </button>
                                    </form>
                                @endif
                                <details class="group">
                                    <summary class="flex cursor-pointer list-none items-center justify-center gap-1.5 rounded-lg border border-line px-4 py-2 text-sm font-semibold text-ink transition hover:bg-slate-50">
                                        <x-lucide-users class="h-4 w-4" /> Pilih mahasiswa lain…
                                    </summary>
                                    <form method="POST" action="{{ route('admin.verifications.approve', $user) }}" class="mt-2 flex flex-col gap-2">
                                        @csrf
                                        <select name="student_id" class="input text-xs" required>
                                            <option value="">— Pilih data mahasiswa resmi —</option>
                                            @foreach (\App\Models\Student::whereNull('user_id')->orderBy('nim')->get() as $student)
                                                <option value="{{ $student->id }}">{{ $student->nim }} — {{ $student->full_name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn-primary text-xs">Tautkan</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('admin.verifications.reject', $user) }}" class="flex items-center gap-2" data-confirm="Tolak dan blokir akun ini?">
                                    @csrf
                                    <input type="hidden" name="reason" value="Ditolak oleh admin: data tidak dapat diverifikasi.">
                                    <button type="submit" class="btn-danger w-full lg:w-auto !py-2 text-xs">
                                        <x-lucide-ban class="h-4 w-4" /> Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5">
                <x-pagination :paginator="$users" />
            </div>
        @endif
    </div>
</x-layouts.admin>
