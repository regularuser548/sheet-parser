<?php

namespace App\Http\Requests\Sheets;

use App\Concerns\ValidatesImportMappings;
use App\Models\ImportMapping;
use App\Models\SheetTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateImportMappingRequest extends FormRequest
{
    use ValidatesImportMappings;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ImportMapping $mapping */
        $mapping = $this->route('mapping');
        /** @var SheetTab $tab */
        $tab = $mapping->tab;

        return $this->mappingRules($tab->id, $mapping);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->validateColumnConfiguration(...)];
    }
}
