<?php

namespace App\Http\Resources;

use App\Models\ImportMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImportMapping
 */
class ImportMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_name' => $this->table_name,
            'enabled' => $this->enabled,
            'columns' => ColumnMappingResource::collection($this->whenLoaded('columnMappings')),
        ];
    }
}
