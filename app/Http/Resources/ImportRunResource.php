<?php

namespace App\Http\Resources;

use App\Models\ImportRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImportRun
 */
class ImportRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_classes' => $this->status->badgeClasses(),
            'imported_rows' => $this->imported_rows,
            'updated_rows' => $this->updated_rows,
            'failed_rows' => $this->failed_rows,
            'message' => $this->message,
            'issues' => $this->issues(),
            'mappings' => $this->details['mappings'] ?? [],
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'source' => $this->whenLoaded('source', fn () => [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'spreadsheet_id' => $this->source->spreadsheet_id,
            ]),
        ];
    }
}
