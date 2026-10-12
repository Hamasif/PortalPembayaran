<x-layouts.admin title="Import Data Mahasiswa">
    <a href="{{ route('admin.students.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke daftar mahasiswa
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <h1 class="text-lg font-bold text-ink">Import Data Mahasiswa</h1>
            <p class="mt-1 text-sm leading-relaxed text-muted">
                Unggah berkas Excel/CSV berisi data induk mahasiswa resmi. Data akan divalidasi
                terlebih dahulu dan <strong>tidak langsung tersimpan</strong> — Anda akan melihat
                pratinjau untuk dikonfirmasi.
            </p>

            <form method="POST" action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data" class="mt-6" id="import-form">
                @csrf

                <label for="file" class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-line px-6 py-10 text-center transition hover:border-brand-600 hover:bg-brand-50/40">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <x-lucide-file-up class="h-6 w-6" />
                    </span>
                    <span class="mt-3 text-sm font-semibold text-ink">Pilih berkas CSV / XLSX</span>
                    <span class="mt-1 text-xs text-muted">Maksimal 5 MB — kolom: nim, nama_lengkap, program_studi, angkatan, email, status</span>
                    <input type="file" id="file" name="file" accept=".csv,.xls,.xlsx" class="hidden"
                           onchange="document.getElementById('file-name').textContent = this.files[0] ? this.files[0].name : ''" required>
                    <span id="file-name" class="mt-2 text-xs font-medium text-brand-700"></span>
                </label>

                <button type="submit" class="btn-primary mt-5 w-full sm:w-auto">
                    <x-lucide-eye class="h-4 w-4" /> Unggah & Pratinjau
                </button>
            </form>

            <div class="mt-6 rounded-lg border border-line bg-surface p-4 text-xs leading-relaxed text-muted">
                <p class="font-semibold text-ink">Ketentuan validasi:</p>
                <ul class="mt-2 list-disc space-y-1 pl-4">
                    <li>NIM wajib, unik, 3–30 karakter alfanumerik.</li>
                    <li>Nama lengkap & program studi wajib diisi.</li>
                    <li>Angkatan berupa tahun 4 digit (1990 – {{ date('Y') + 1 }}).</li>
                    <li>Status (opsional): active, inactive, graduated.</li>
                    <li>Baris dengan NIM yang sudah ada akan <strong>diperbarui</strong> (bukan ditolak).</li>
                </ul>
                <a href="{{ route('admin.students.import.template') }}" class="btn-primary mt-3 !px-4 !py-2 text-xs">
                    <x-lucide-download class="h-3.5 w-3.5" /> Unduh Templat Excel (.xlsx)
                </a>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-bold text-ink">Riwayat Import</h2>
            @if ($batches->isEmpty())
                <x-empty-state icon="file-clock" title="Belum ada import" class="mt-4 !px-4 !py-8" />
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($batches as $batch)
                        <li class="rounded-lg border border-line px-4 py-3">
                            <p class="truncate text-sm font-semibold text-ink">{{ $batch->original_filename }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $batch->created_at->format('d M Y H:i') }} oleh {{ $batch->creator->name }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                @if ($batch->status === 'imported')
                                    <x-badge tone="green">{{ $batch->success_count }} berhasil</x-badge>
                                    @if ($batch->failed_count > 0)
                                        <x-badge tone="red">{{ $batch->failed_count }} gagal</x-badge>
                                    @endif
                                @else
                                    <x-badge tone="accent">Pratinjau</x-badge>
                                @endif
                            </div>
                            @if ($batch->failed_count > 0)
                                <a href="{{ route('admin.students.import.errors', $batch) }}" class="btn-ghost mt-2 !px-0 !py-1 text-xs">
                                    Unduh laporan gagal
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">
                    <x-pagination :paginator="$batches" />
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
