<?php

namespace App\Http\Resources;

use App\Models\ColumnMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ColumnMapping
 */
class ColumnMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_column' => $this->source_column,
            'destination_column' => $this->destination_column,
            'data_type' => $this->data_type->value,
            'data_type_label' => $this->data_type->label(),
            'nullable' => $this->nullable,
            'default_value' => $this->default_value,
        ];
    }
}
