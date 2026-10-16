<?php

namespace App\Http\Resources;

use App\Models\SheetTab;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SheetTab
 */
class SheetTabResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sheet_source_id' => $this->sheet_source_id,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'import_mappings_count' => $this->whenCounted('importMappings'),
            'import_mappings' => ImportMappingResource::collection($this->whenLoaded('importMappings')),
            'source' => $this->whenLoaded('source', fn (): array => [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'spreadsheet_id' => $this->source->spreadsheet_id,
            ]),
        ];
    }
}
