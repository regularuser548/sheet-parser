<?php

namespace App\Http\Controllers\Sheets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sheets\StoreSheetSourceRequest;
use App\Http\Requests\Sheets\UpdateSheetSourceRequest;
use App\Http\Resources\SheetSourceResource;
use App\Models\SheetSource;
use App\Services\Sheets\SheetImporter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SheetSourceController extends Controller
{
    /**
     * List every configured spreadsheet source.
     */
    public function index(): Response
    {
        $sources = SheetSource::query()
            ->withCount('tabs')
            ->withCount(['tabs as enabled_tabs_count' => fn ($query) => $query->where('enabled', true)])
            ->with('latestRun')
            ->orderBy('name')
            ->get();

        return Inertia::render('sheets/sources/index', [
            'sources' => SheetSourceResource::collection($sources),
        ]);
    }

    /**
     * Show the form for creating a source.
     */
    public function create(): Response
    {
        return Inertia::render('sheets/sources/create');
    }

    /**
     * Store a newly created source.
     */
    public function store(StoreSheetSourceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $source = SheetSource::query()->create([
            'name' => $data['name'],
            'spreadsheet_id' => $data['spreadsheet_id'],
            'credentials_path' => $data['credentials_path'] ?? null,
            'enabled' => $request->boolean('enabled', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Spreadsheet source created.']);

        return to_route('sheets.sources.edit', $source);
    }

    /**
     * Show the form for editing a source and its tabs.
     */
    public function edit(SheetSource $source): Response
    {
        $source->load(['tabs' => fn ($query) => $query->withCount('importMappings')->orderBy('name')]);

        return Inertia::render('sheets/sources/edit', [
            'source' => new SheetSourceResource($source),
        ]);
    }

    /**
     * Update the source.
     */
    public function update(UpdateSheetSourceRequest $request, SheetSource $source): RedirectResponse
    {
        $data = $request->validated();
        $source->update([
            'name' => $data['name'],
            'spreadsheet_id' => $data['spreadsheet_id'],
            'credentials_path' => $data['credentials_path'] ?? null,
            'enabled' => $request->boolean('enabled', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Spreadsheet source updated.']);

        return to_route('sheets.sources.edit', $source);
    }

    /**
     * Delete the source and its configuration.
     */
    public function destroy(SheetSource $source): RedirectResponse
    {
        $source->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Spreadsheet source deleted.']);

        return to_route('sheets.sources.index');
    }

    /**
     * Run an import for a single source immediately.
     */
    public function import(SheetSource $source, SheetImporter $importer): RedirectResponse
    {
        $run = $importer->import($source);

        $type = $run->isSuccessful() ? 'success' : ($run->failed_rows > 0 ? 'error' : 'warning');

        Inertia::flash('toast', [
            'type' => $type,
            'message' => sprintf(
                '%s import finished with status %s: %d imported, %d updated, %d failed.',
                $source->name,
                $run->status->label(),
                $run->imported_rows,
                $run->updated_rows,
                $run->failed_rows,
            ),
        ]);

        return back();
    }
}
