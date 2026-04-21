<?php

namespace App\Http\Resources\Workspace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['operation'] = $this->whenLoaded('operation');
        $data['building'] = $this->whenLoaded('building');
        $data['user'] = $this->whenLoaded('user');

        return $data;
    }
}
