<x-layouts.student title="Profil Saya">
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Identitas resmi (read-only) --}}
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-base font-bold text-ink">Data Identitas Resmi</h1>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-muted">
                    <x-lucide-lock class="h-3 w-3" /> Terkunci
                </span>
            </div>
            <p class="mt-1.5 text-xs leading-relaxed text-muted">
                Data ini berasal dari data induk kampus dan <strong>tidak dapat diubah langsung</strong>.
                Jika ada kekeliruan, ajukan koreksi ke bagian akademik.
            </p>

            <div class="mt-5 flex items-center gap-4 rounded-lg border border-line bg-surface p-4">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="Foto profil" class="h-12 w-12 rounded-full object-cover">
                @else
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink">{{ $student->full_name }}</p>
                    <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">NIM</dt>
                    <dd class="font-medium text-ink">{{ $student->nim }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Program studi</dt>
                    <dd class="text-right font-medium text-ink">{{ $student->program_study }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Angkatan</dt>
                    <dd class="font-medium text-ink">{{ $student->cohort_year }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Status mahasiswa</dt>
                    <dd><x-status-pill :status="$student->student_status->value" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Status akun</dt>
                    <dd><x-status-pill :status="$user->account_status->value" /></dd>
                </div>
            </dl>
        </div>

        {{-- Preferensi --}}
        <div class="card p-6">
            <h2 class="text-base font-bold text-ink">Preferensi Akun</h2>
            <p class="mt-1.5 text-xs text-muted">Sampai kapan pun, identitas resmi hanya dapat diubah oleh bagian akademik.</p>

            <form method="POST" action="{{ route('student.profile.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <x-field name="display_name" label="Nama panggilan (opsional)" hint="Hanya untuk tampilan sapaan di dashboard.">
                    <input type="text" id="display_name" name="display_name" maxlength="60"
                           value="{{ old('display_name', session('display_name')) }}" class="input">
                </x-field>

                <button type="submit" class="btn-primary">Simpan Preferensi</button>
            </form>

            <div class="mt-6 border-t border-line pt-5">
                <h3 class="text-sm font-bold text-ink">Keamanan</h3>
                <ul class="mt-2 space-y-2 text-xs leading-relaxed text-muted">
                    <li class="flex items-start gap-2">
                        <x-lucide-shield-check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                        Akun terhubung dengan Google: <span class="font-medium text-ink">{{ $user->email }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-lucide-key-round class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                        Tidak ada password di sistem ini — login hanya melalui Google.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-layouts.student>
