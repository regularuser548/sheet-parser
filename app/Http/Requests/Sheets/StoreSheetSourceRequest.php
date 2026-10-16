<?php

namespace App\Http\Requests\Sheets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSheetSourceRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'spreadsheet_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_-]{10,}$/',
                Rule::unique('sheet_sources', 'spreadsheet_id'),
            ],
            'credentials_path' => ['nullable', 'string', 'max:2048'],
            'enabled' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'spreadsheet_id.regex' => 'The spreadsheet ID must be the ID from the Google Sheets URL (at least 10 letters, numbers, dashes, or underscores).',
        ];
    }
}
