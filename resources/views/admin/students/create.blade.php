<x-layouts.admin title="Tambah Mahasiswa">
    <a href="{{ route('admin.students.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke daftar mahasiswa
    </a>

    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold text-ink">Tambah Mahasiswa Manual</h1>
                    <p class="mt-1 text-sm text-muted">Untuk menambahkan satu mahasiswa tanpa berkas import.</p>
                </div>
                <a href="{{ route('admin.students.import') }}" class="btn-secondary shrink-0 !py-2 text-xs">
                    <x-lucide-upload class="h-3.5 w-3.5" /> Import Banyak
                </a>
            </div>

            <form method="POST" action="{{ route('admin.students.store') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="nim" label="NIM" required hint="3–30 karakter; huruf, angka, titik, garis.">
                        <input type="text" id="nim" name="nim" value="{{ old('nim') }}"
                               placeholder="Contoh: 20230001" class="input font-mono" required autofocus>
                    </x-field>

                    <x-field name="cohort_year" label="Angkatan" required hint="Tahun masuk, 4 digit.">
                        <input type="number" id="cohort_year" name="cohort_year" min="1990" max="{{ date('Y') + 1 }}"
                               value="{{ old('cohort_year') }}" placeholder="2023" class="input" required>
                    </x-field>
                </div>

                <x-field name="full_name" label="Nama Lengkap" required>
                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="150"
                           placeholder="Nama sesuai data resmi" class="input" required>
                </x-field>

                <x-field name="program_study" label="Program Studi" required>
                    <input type="text" id="program_study" name="program_study" value="{{ old('program_study') }}" maxlength="150"
                           placeholder="Contoh: Teknik Informatika" class="input" required>
                </x-field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="email" label="Email Kampus (opsional)" hint="Dipakai untuk pencocokan otomatis saat mahasiswa login pertama.">
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               placeholder="20230001@student.universitas.ac.id" class="input">
                    </x-field>

                    <x-field name="student_status" label="Status Mahasiswa" required>
                        <select id="student_status" name="student_status" class="input" required>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('student_status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>

                <x-alert type="info">
                    Setelah data tersimpan, mahasiswa dapat login dengan Google dan mengisi NIM + angkatan
                    untuk menghubungkan akunnya. Perubahan tercatat pada audit log.
                </x-alert>

                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary">
                        <x-lucide-user-plus class="h-4 w-4" /> Simpan Mahasiswa
                    </button>
                    <a href="{{ route('admin.students.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
