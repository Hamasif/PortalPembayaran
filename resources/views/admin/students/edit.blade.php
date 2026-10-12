<x-layouts.admin title="Ubah Data Mahasiswa">
    <a href="{{ route('admin.students.show', $student) }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke detail
    </a>

    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <h1 class="text-lg font-bold text-ink">Ubah Data Mahasiswa</h1>
            <p class="mt-1 text-sm text-muted">Perubahan tercatat pada audit log. NIM tidak dapat diubah.</p>

            <form method="POST" action="{{ route('admin.students.update', $student) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <x-field name="nim" label="NIM">
                    <input type="text" id="nim" value="{{ $student->nim }}" class="input" disabled>
                </x-field>

                <x-field name="full_name" label="Nama Lengkap" required>
                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $student->full_name) }}" class="input" required>
                </x-field>

                <x-field name="program_study" label="Program Studi" required>
                    <input type="text" id="program_study" name="program_study" value="{{ old('program_study', $student->program_study) }}" class="input" required>
                </x-field>

                <x-field name="student_status" label="Status Mahasiswa" required>
                    <select id="student_status" name="student_status" class="input" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('student_status', $student->student_status->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field name="email" label="Email Kampus (opsional)" hint="Dipakai untuk pencocokan otomatis saat verifikasi.">
                    <input type="email" id="email" name="email" value="{{ old('email', $student->email) }}" class="input">
                </x-field>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('admin.students.show', $student) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
