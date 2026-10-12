<x-layouts.admin title="Pengaturan Domain Email">
    <div>
        <h1 class="text-xl font-bold text-ink">Pengaturan Domain Email</h1>
        <p class="mt-1 text-sm leading-relaxed text-muted">
            Hanya email dengan domain berikut yang dapat login. Daftar ini <strong>wajib diganti</strong>
            dengan domain resmi institusi Anda. Perubahan berlaku langsung di backend.
        </p>
    </div>

    <div class="mt-5 grid gap-6 lg:grid-cols-2">
        <div class="card p-6">
            <h2 class="text-sm font-bold text-ink">Tambah Domain</h2>
            <form method="POST" action="{{ route('admin.settings.domains.store') }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                @csrf
                <input type="text" name="domain" placeholder="student.universitas.ac.id" class="input flex-1" required>
                <button type="submit" class="btn-primary shrink-0">
                    <x-lucide-plus class="h-4 w-4" /> Tambah
                </button>
            </form>
            <p class="mt-3 text-xs text-muted">Contoh sekaligus pengecualian: gunakan subdomain mahasiswa dan domain utama (mis. student.universitas.ac.id dan universitas.ac.id).</p>
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-bold text-ink">Domain Terdaftar</h2>
            @if ($domains->isEmpty())
                <p class="mt-3 text-xs text-muted">Belum ada domain di tabel — sistem memakai fallback konfigurasi:
                    <span class="font-mono">{{ implode(', ', $configDomains) }}</span>
                </p>
            @else
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($domains as $domain)
                        <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <p class="font-mono text-sm font-medium text-ink">{{ $domain->domain }}</p>
                                <p class="text-[11px] text-muted">ditambahkan {{ $domain->created_at->format('d M Y') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-badge :tone="$domain->is_active ? 'green' : 'slate'">{{ $domain->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                <form method="POST" action="{{ route('admin.settings.domains.toggle', $domain) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !px-3 !py-1.5 text-xs">
                                        {{ $domain->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.settings.domains.destroy', $domain) }}"
                                      data-confirm="Hapus domain {{ $domain->domain }}? Pengguna dengan domain ini tidak akan bisa login lagi.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger !px-3 !py-1.5 text-xs" aria-label="Hapus domain">
                                        <x-lucide-trash-2 class="h-3.5 w-3.5" />
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.admin>
