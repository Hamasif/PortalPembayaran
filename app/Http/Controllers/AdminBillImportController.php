<?php

namespace App\Http\Controllers;

use App\Models\StudentImportBatch;
use App\Services\BillImportService;
use Illuminate\Http\Request;

class AdminBillImportController extends Controller
{
    public function __construct(private readonly BillImportService $importService) {}

    public function index()
    {
        return view('admin.bills.import', [
            'batches' => StudentImportBatch::with('creator')->latest()->paginate(8),
        ]);
    }

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

        return view('admin.bills.import-preview', [
            'batch' => $result['batch'],
            'rows' => $result['rows'],
        ]);
    }

    public function commit(Request $request, StudentImportBatch $batch)
    {
        if ($batch->created_by !== $request->user()->id) {
            abort(403, 'Batch import ini bukan milik sesi Anda.');
        }

        try {
            $this->importService->import($batch, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.bills.import')
            ->with('status_type', 'success')
            ->with('status_message', sprintf(
                'Import tagihan selesai: %d berhasil, %d gagal.',
                $batch->success_count,
                $batch->failed_count
            ));
    }

    public function template()
    {
        return BillImportService::templateResponse();
    }
}
