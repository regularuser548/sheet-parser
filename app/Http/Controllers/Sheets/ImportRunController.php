<?php

namespace App\Http\Controllers\Sheets;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImportRunResource;
use App\Http\Resources\SheetSourceResource;
use App\Models\ImportRun;
use App\Models\SheetSource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportRunController extends Controller
{
    /**
     * Show the import history for every source, newest first.
     */
    public function index(Request $request): Response
    {
        $sourceId = $request->integer('source') ?: null;

        $runs = ImportRun::query()
            ->with('source')
            ->when($sourceId, fn ($query) => $query->where('sheet_source_id', $sourceId))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('sheets/runs/index', [
            'runs' => ImportRunResource::collection($runs),
            'sources' => SheetSourceResource::collection(
                SheetSource::query()->orderBy('name')->get(['id', 'name']),
            ),
            'filters' => ['source' => $sourceId],
        ]);
    }
}
