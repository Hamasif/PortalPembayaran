<x-layouts.admin title="Buat Tagihan">
    <a href="{{ route('admin.bills.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke daftar tagihan
    </a>

    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <h1 class="text-lg font-bold text-ink">Buat Tagihan Baru</h1>
            <p class="mt-1 text-sm text-muted">Nominal ditetapkan di sini oleh admin — tidak pernah diambil dari input mahasiswa.</p>

            <form method="POST" action="{{ route('admin.bills.store') }}" class="mt-6 space-y-4">
                @csrf

                <x-field name="student_id" label="Mahasiswa" required>
                    <select id="student_id" name="student_id" class="input" required>
                        <option value="">— Pilih mahasiswa (berdasarkan NIM) —</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id', $prefillStudent?->id) == $student->id)>
                                {{ $student->nim }} — {{ $student->full_name }} ({{ $student->program_study }})
                            </option>
                        @endforeach
                    </select>
                </x-field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="academic_term_id" label="Tahun Akademik & Semester" required>
                        <select id="academic_term_id" name="academic_term_id" class="input" required>
                            <option value="">— Pilih periode —</option>
                            @foreach ($terms as $term)
                                <option value="{{ $term->id }}" @selected(old('academic_term_id') == $term->id)>{{ $term->label() }}</option>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field name="bill_type" label="Jenis Tagihan" required>
                        <select id="bill_type" name="bill_type" class="input" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('bill_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="amount" label="Nominal (Rp)" required hint="Angka bulat rupiah tanpa desimal.">
                        <input type="number" id="amount" name="amount" min="1" step="1"
                               value="{{ old('amount') }}" placeholder="5000000" class="input" required>
                    </x-field>

                    <x-field name="due_date" label="Tanggal Jatuh Tempo" required>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" class="input" required>
                    </x-field>
                </div>

                <x-field name="description" label="Keterangan (opsional)">
                    <input type="text" id="description" name="description" maxlength="255"
                           value="{{ old('description') }}" placeholder="Contoh: UKT Semester Ganjil 2025/2026" class="input">
                </x-field>

                <x-alert type="warning">
                    Satu jenis tagihan hanya boleh ada satu per mahasiswa per periode. Duplikat akan ditolak sistem.
                </x-alert>

                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary">
                        <x-lucide-plus class="h-4 w-4" /> Terbitkan Tagihan
                    </button>
                    <a href="{{ route('admin.bills.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
