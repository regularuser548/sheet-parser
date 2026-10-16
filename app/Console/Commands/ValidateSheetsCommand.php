<?php

namespace App\Console\Commands;

use App\Models\SheetSource;
use App\Services\Sheets\ConfigurationValidator;
use App\Services\Sheets\ValidationReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sheets:validate {source? : Spreadsheet ID or configured source name} {--offline : Validate the configuration without reading the spreadsheets}')]
#[Description('Validate the Google Sheets import configuration without importing data')]
class ValidateSheetsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ConfigurationValidator $validator): int
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

        $fetch = ! $this->option('offline');

        $report = $source instanceof SheetSource
            ? new ValidationReport($validator->validateSource($source, $fetch))
            : $validator->validateAll($fetch);

        if ($report->issues !== []) {
            $this->table(
                ['Level', 'Subject', 'Message'],
                array_map(fn ($issue): array => [$issue->level, $issue->subject, $issue->message], $report->issues),
            );
        }

        $this->components->info(sprintf(
            'Validation finished with %d error(s) and %d warning(s).',
            count($report->errors()),
            count($report->warnings()),
        ));

        return $report->hasErrors() ? self::FAILURE : self::SUCCESS;
    }
}
