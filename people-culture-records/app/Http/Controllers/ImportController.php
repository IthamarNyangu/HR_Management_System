<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index(Request $request): View
    {
        $batches = ImportBatch::query()
            ->with('uploadedBy')
            ->when(! ($request->user()->isAdmin() || $request->user()->isHrManager()), fn ($query) => $query->where('uploaded_by', $request->user()->id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('imports.index', compact('batches'));
    }

    public function showBatch(Request $request, ImportBatch $importBatch): View
    {
        $this->authorizeBatchAccess($request, $importBatch);

        $rows = $importBatch->rows()
            ->orderBy('row_number')
            ->paginate(25)
            ->withQueryString();

        return view('imports.batches.show', compact('importBatch', 'rows'));
    }

    private function authorizeBatchAccess(Request $request, ImportBatch $importBatch): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isHrManager() || (int) $importBatch->uploaded_by === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
