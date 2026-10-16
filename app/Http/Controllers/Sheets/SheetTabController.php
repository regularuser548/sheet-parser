<?php

namespace App\Http\Controllers\Sheets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sheets\StoreSheetTabRequest;
use App\Http\Requests\Sheets\UpdateSheetTabRequest;
use App\Models\SheetSource;
use App\Models\SheetTab;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SheetTabController extends Controller
{
    /**
     * Add a tab to a source.
     */
    public function store(StoreSheetTabRequest $request, SheetSource $source): RedirectResponse
    {
        $source->tabs()->create([
            'name' => $request->validated('name'),
            'enabled' => $request->boolean('enabled', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tab added.']);

        return to_route('sheets.sources.edit', $source);
    }

    /**
     * Update a tab.
     */
    public function update(UpdateSheetTabRequest $request, SheetTab $tab): RedirectResponse
    {
        $tab->update([
            'name' => $request->validated('name'),
            'enabled' => $request->boolean('enabled', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tab updated.']);

        return to_route('sheets.sources.edit', $tab->sheet_source_id);
    }

    /**
     * Delete a tab and its mappings.
     */
    public function destroy(SheetTab $tab): RedirectResponse
    {
        $sourceId = $tab->sheet_source_id;
        $tab->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tab deleted.']);

        return to_route('sheets.sources.edit', $sourceId);
    }
}
