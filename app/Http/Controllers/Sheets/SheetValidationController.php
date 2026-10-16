<?php

namespace App\Http\Controllers\Sheets;

use App\Http\Controllers\Controller;
use App\Models\SheetSource;
use App\Services\Sheets\ConfigurationValidator;
use App\Services\Sheets\ValidationReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SheetValidationController extends Controller
{
    /**
     * Validate the import configuration and flash the report for display.
     */
    public function store(Request $request, ConfigurationValidator $validator): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', 'integer', 'exists:sheet_sources,id'],
            'fetch' => ['nullable', 'boolean'],
        ]);

        $fetch = $request->boolean('fetch', true);

        if (isset($validated['source'])) {
            $source = SheetSource::query()->whereKey($validated['source'])->firstOrFail();
            $report = new ValidationReport($validator->validateSource($source, $fetch));
        } else {
            $report = $validator->validateAll($fetch);
        }

        Inertia::flash('validation', $report->toArray());

        Inertia::flash('toast', [
            'type' => $report->hasErrors() ? 'error' : 'success',
            'message' => $report->hasErrors()
                ? 'Validation found '.count($report->errors()).' error(s) and '.count($report->warnings()).' warning(s).'
                : 'Validation passed with '.count($report->warnings()).' warning(s).',
        ]);

        return back();
    }
}
