<?php

namespace App\Http\Requests\Sheets;

use App\Concerns\ValidatesImportMappings;
use App\Models\SheetTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreImportMappingRequest extends FormRequest
{
    use ValidatesImportMappings;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var SheetTab $tab */
        $tab = $this->route('tab');

        return $this->mappingRules($tab->id);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->validateColumnConfiguration(...)];
    }
}
