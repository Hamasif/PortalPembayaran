<x-layouts.admin title="Ubah Tagihan">
    <a href="{{ route('admin.bills.show', $bill) }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke detail tagihan
    </a>

    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <h1 class="text-lg font-bold text-ink">Ubah Tagihan</h1>
            <p class="mt-1 font-mono text-xs text-muted">{{ $bill->bill_number }}</p>

            <form method="POST" action="{{ route('admin.bills.update', $bill) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <x-field name="amount" label="Nominal (Rp)" required>
                    <input type="number" id="amount" name="amount" min="1" step="1" value="{{ old('amount', $bill->amount) }}" class="input" required>
                </x-field>

                <x-field name="due_date" label="Tanggal Jatuh Tempo" required>
                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $bill->due_date->toDateString()) }}" class="input" required>
                </x-field>

                <x-field name="description" label="Keterangan">
                    <input type="text" id="description" name="description" maxlength="255" value="{{ old('description', $bill->description) }}" class="input">
                </x-field>

                <x-alert type="info">
                    Perubahan dicatat pada audit log. Tagihan yang sudah lunas tidak dapat diubah.
                </x-alert>

                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('admin.bills.show', $bill) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
