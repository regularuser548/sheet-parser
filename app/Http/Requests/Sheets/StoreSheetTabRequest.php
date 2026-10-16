<?php

namespace App\Http\Requests\Sheets;

use App\Models\SheetSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSheetTabRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var SheetSource $source */
        $source = $this->route('source');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sheet_tabs', 'name')->where('sheet_source_id', $source->id),
            ],
            'enabled' => ['boolean'],
        ];
    }
}
