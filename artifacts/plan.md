# Implementation Plan

## 1. Project Stack

- Laravel application using Laravel Sail for local development.
- MySQL for application and imported data.
- React with TypeScript and Inertia.js for the administration UI.
- Laravel Breeze React starter kit for authentication and initial frontend structure.
- Tailwind CSS for styling.
- Redis and Laravel queues through Sail for larger imports and background processing.

## 2. Project Bootstrap

- Create the Laravel application with Sail and MySQL support.
- Configure `.env.example` with Sail-compatible settings, including `DB_HOST=mysql`.
- Add Google Sheets API dependencies.
- Add Docker/Sail configuration for Laravel, MySQL, Redis, and Mailpit where needed.
- Use a version-compatible Laravel starter-kit installation process for React, TypeScript, and Inertia.

All development commands should be documented through Sail, for example:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan sheets:import
./vendor/bin/sail test
```

## 3. Database Design

Create migrations and models for:

- `sheet_sources`: spreadsheet ID, name, enabled status, and credentials/configuration reference.
- `sheet_tabs`: source reference, tab name, and enabled status.
- `import_mappings`: source/tab reference, destination table, and enabled status.
- `column_mappings`: source header, destination column, data type, nullable flag, and default value.
- `import_runs`: source, timestamps, status, imported/failed row counts, and error details.
- Import-row tracking data or source-row hashes for idempotent imports.

The mapping structure must allow one sheet tab to write to multiple destination tables with different schemas.

## 4. Google Sheets Integration

Implement a Google Sheets service that:

- Authenticates with a Google service account.
- Reads configured spreadsheets and tabs.
- Treats the first row as column headers.
- Supports columns in arbitrary positions.
- Ignores unconfigured source columns.
- Detects and reports missing configured headers.
- Normalizes headers consistently for matching.
- Converts values according to configured destination types.
- Retries transient API failures and reports permanent failures clearly.

Handle missing spreadsheets, missing tabs, invalid credentials, permission errors, and malformed sheet data.

## 5. Import Pipeline

Create an import service that:

1. Loads enabled spreadsheet sources and mappings.
2. Fetches configured tab data.
3. Resolves source headers to destination columns.
4. Groups mappings by destination table.
5. Ensures destination tables and columns are available.
6. Inserts or updates rows in the destination tables.
7. Prevents duplicate data on repeated daily runs.
8. Records counts, statuses, validation failures, and exceptions.

Each source should be processed independently so one failed spreadsheet does not stop other sources from importing.

## 6. Dynamic Destination Schema

Implement a schema-management service that:

- Checks whether configured destination tables exist.
- Adds missing mapped columns automatically.
- Uses the configured data type when creating a column.
- Validates table and column identifiers against safe database identifier rules.
- Rejects unsafe or invalid schema changes.
- Logs runtime schema changes.

Use standard migrations for application tables. Restrict runtime schema alteration to explicitly configured destination tables and mapped columns.

## 7. Artisan Commands, Scheduling, and Queues

Add commands such as:

- `sheets:import`
- `sheets:import {source?}`
- `sheets:validate`

Support manual imports, single-source imports, dry-run validation, verbose output, and failed-run reporting.

Configure Laravel’s scheduler for daily imports. Use queued jobs for large spreadsheets and document the Sail queue worker and scheduler processes.

## 8. React/Inertia Administration UI

Build authenticated React/TypeScript pages for:

- Spreadsheet source management.
- Sheet tab configuration.
- Destination table configuration.
- Source-to-destination column mapping management.
- Import history and error details.
- Manual import execution.
- Configuration validation.

Use typed Inertia page props and shared TypeScript interfaces. Use Inertia forms for mutations, Laravel Form Requests for server-side validation, and reusable React components for tables, forms, status badges, mapping editors, and confirmation dialogs.

Add loading, success, validation-error, and failure states for all relevant actions. Protect management pages with authentication and authorization.

## 9. Validation and Security

Validate:

- Spreadsheet IDs.
- Tab names.
- Destination table and column names.
- Supported data types.
- Required mapping fields.

Keep Google credentials outside source control. Document service-account spreadsheet sharing. Sanitize dynamic identifiers and never interpolate unvalidated user input into SQL or schema operations.

## 10. Testing

Add automated tests covering:

- Header matching with different column orders.
- Additional source columns.
- Missing configured columns.
- One sheet writing to multiple destination tables.
- Different source and destination column names.
- Automatic addition of missing destination columns.
- Data type conversion.
- Duplicate prevention and idempotent reruns.
- Partial source failures.
- Mocked Google Sheets API responses.
- Artisan commands, queues, and scheduler behavior.
- Authorization and UI validation.

Tests should use mocked Google API responses and a test database rather than real credentials or production spreadsheets.

## 11. Documentation

Create `README.md` covering:

- Requirements and Sail installation.
- Starting and stopping the Docker environment.
- MySQL configuration.
- Google Cloud project creation.
- Enabling the Google Sheets API.
- Creating a service account and downloading credentials.
- Sharing spreadsheets with the service-account email.
- Environment configuration.
- Running migrations and seeders.
- Creating mappings through the UI.
- Running imports manually.
- Configuring the daily scheduler and queue worker.
- Viewing logs and failed imports.
- Spreadsheet format and mapping examples.
- Security considerations.

## 12. Ubuntu Setup Script

Add an idempotent setup script that:

- Verifies or installs Docker and Docker Compose.
- Bootstraps the Laravel/Sail project where necessary.
- Creates and configures `.env`.
- Starts Sail services.
- Runs migrations and seed data.
- Installs/builds frontend assets.
- Configures the scheduler through cron or a systemd timer.
- Starts or documents the queue worker.
- Prints remaining Google credentials and configuration steps.

The script should fail safely when required tools or credentials are missing and should not overwrite existing user configuration without confirmation.

## 13. Final Verification

- Run formatting, static analysis, and the full test suite through Sail.
- Run migrations against a clean MySQL database.
- Execute a mocked import.
- Verify automatic destination-column creation.
- Verify repeated imports do not duplicate data.
- Test the administration UI workflow.
- Verify scheduler and queue-worker instructions.
- Review the README against every requirement in `task.md`.
