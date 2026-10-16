<?php

namespace App\Console\Commands;

use App\Enums\ImportRunStatus;
use App\Jobs\RunSheetImportJob;
use App\Models\ImportRun;
use App\Models\SheetSource;
use App\Services\Sheets\ConfigurationValidator;
use App\Services\Sheets\SheetImporter;
use App\Services\Sheets\ValidationReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('sheets:import {source? : Spreadsheet ID or configured source name} {--queue : Dispatch one queued job per source instead of importing now} {--dry-run : Validate the configuration without writing any data}')]
#[Description('Import configured Google Sheets into their destination database tables')]
class ImportSheetsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SheetImporter $importer, ConfigurationValidator $validator): int
    {
        $source = null;
        $identifier = $this->argument('source');

        if (is_string($identifier) && $identifier !== '') {
            $source = SheetSource::findByIdentifier($identifier);

            if (! $source instanceof SheetSource) {
                $this->components->error("No configured source matches [$identifier].");

                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            return $this->dryRun($validator, $source);
        }

        if ($this->option('queue')) {
            return $this->queue($source);
        }

        return $this->runImport($importer, $source);
    }

    private function dryRun(ConfigurationValidator $validator, ?SheetSource $source): int
    {
        $report = $source instanceof SheetSource
            ? new ValidationReport($validator->validateSource($source))
            : $validator->validateAll();

        $this->renderIssues($report);
        $this->components->info($report->hasErrors()
            ? 'Dry run finished with configuration errors. No data was written.'
            : 'Dry run finished. No data was written.');

        return $report->hasErrors() ? self::FAILURE : self::SUCCESS;
    }

    private function queue(?SheetSource $source): int
    {
        $sources = $source instanceof SheetSource
            ? collect([$source])
            : SheetSource::query()->where('enabled', true)->orderBy('id')->get();

        if ($sources->isEmpty()) {
            $this->components->warn('No enabled sources were found to queue.');

            return self::SUCCESS;
        }

        $sources->each(fn (SheetSource $queued): mixed => RunSheetImportJob::dispatch($queued));

        $this->components->info("Queued {$sources->count()} import job(s). Run a queue worker to process them.");

        return self::SUCCESS;
    }

    private function runImport(SheetImporter $importer, ?SheetSource $source): int
    {
        $runs = $importer->importAll($source);

        if ($runs->isEmpty()) {
            $this->components->warn('No enabled sources were found to import.');

            return self::SUCCESS;
        }

        $this->table(
            ['Source', 'Status', 'Imported', 'Updated', 'Failed'],
            $runs->map(fn (ImportRun $run): array => [
                $run->source->name,
                $run->status->label(),
                $run->imported_rows,
                $run->updated_rows,
                $run->failed_rows,
            ]),
        );

        $this->reportFailures($runs);

        return $runs->contains(fn (ImportRun $run): bool => $run->status === ImportRunStatus::Failed)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  Collection<int, ImportRun>  $runs
     */
    private function reportFailures(Collection $runs): void
    {
        $runs->filter(fn (ImportRun $run): bool => $run->status !== ImportRunStatus::Success)
            ->each(function (ImportRun $run): void {
                $this->components->warn("{$run->source->name}: {$run->message}");

                foreach ($run->issues() as $issue) {
                    $this->line("  [{$issue['level']}] {$issue['subject']}: {$issue['message']}");
                }
            });
    }

    private function renderIssues(ValidationReport $report): void
    {
        if ($report->issues === []) {
            $this->components->info('No configuration issues were found.');

            return;
        }

        $this->table(
            ['Level', 'Subject', 'Message'],
            array_map(fn ($issue): array => [$issue->level, $issue->subject, $issue->message], $report->issues),
        );
    }
}
