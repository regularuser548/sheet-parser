<?php

namespace App\Http\Resources;

use App\Models\ImportRun;
use App\Models\SheetSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SheetSource
 */
class SheetSourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'spreadsheet_id' => $this->spreadsheet_id,
            'credentials_path' => $this->credentials_path,
            'enabled' => $this->enabled,
            'tabs_count' => $this->whenCounted('tabs'),
            'enabled_tabs_count' => $this->whenCounted('enabled_tabs_count'),
            'tabs' => SheetTabResource::collection($this->whenLoaded('tabs')),
            'latest_run' => $this->whenLoaded(
                'latestRun',
                fn (): ?ImportRunResource => $this->latestRun instanceof ImportRun
                    ? new ImportRunResource($this->latestRun)
                    : null,
            ),
        ];
    }
}
