<?php

namespace App\Jobs;

use App\Models\SheetSource;
use App\Services\Sheets\SheetImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSheetImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly SheetSource $source) {}

    /**
     * Execute the job.
     */
    public function handle(SheetImporter $importer): void
    {
        $importer->import($this->source);
    }
}
