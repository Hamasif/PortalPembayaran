<?php

namespace App\Http\Controllers;

use App\Models\StudentImportBatch;
use App\Services\StudentImportService;
use App\Support\Csv;
use Illuminate\Http\Request;

class AdminStudentImportController extends Controller
{
    public function __construct(private readonly StudentImportService $importService) {}

    public function index(Request $request)
    {
        return view('admin.students.import', [
            'batches' => StudentImportBatch::with('creator')->latest()->paginate(8),
        ]);
    }

    /**
     * Tahap 1: unggah berkas & tampilkan preview validasi.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:'.(int) config('portal.import_max_size_kb', 5120)],
        ], [
            'file.mimes' => 'Format berkas harus CSV, XLS, atau XLSX.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        try {
            $result = $this->importService->preview($request->file('file'), $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return view('admin.students.import-preview', [
            'batch' => $result['batch'],
            'rows' => $result['rows'],
        ]);
    }

    /**
     * Tahap 2: konfirmasi — jalankan import dalam database transaction.
     */
    public function commit(Request $request, StudentImportBatch $batch)
    {
        if ($batch->created_by !== $request->user()->id) {
            abort(403, 'Batch import ini bukan milik sesi Anda.');
        }

        try {
            $this->importService->import($batch, $request->user());
        } catch (\InvalidArgumentException|\LogicException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.students.import')
            ->with('status_type', 'success')
            ->with('status_message', sprintf(
                'Import selesai: %d baris berhasil, %d baris gagal.',
                $batch->success_count,
                $batch->failed_count
            ));
    }

    public function template()
    {
        return StudentImportService::templateResponse();
    }

    public function errorsCsv(StudentImportBatch $batch)
    {
        $rows = collect($batch->errors ?? [])->map(fn (array $error) => [
            $error['row'],
            $error['nim'],
            implode(' | ', $error['errors']),
        ]);

        return Csv::streamDownload(
            'laporan-import-'.$batch->id.'.csv',
            ['baris', 'nim', 'kesalahan'],
            $rows
        );
    }
}
