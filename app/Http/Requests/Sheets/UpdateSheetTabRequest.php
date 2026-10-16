<?php

namespace App\Http\Requests\Sheets;

use App\Models\SheetTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSheetTabRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var SheetTab $tab */
        $tab = $this->route('tab');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sheet_tabs', 'name')
                    ->where('sheet_source_id', $tab->sheet_source_id)
                    ->ignore($tab),
            ],
            'enabled' => ['boolean'],
        ];
    }
}
