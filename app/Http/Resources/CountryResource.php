<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_destination' => $this->is_destination,
            'currency_code' => $this->currency_code,
            'phone_code' => $this->phone_code,
            'flag_emoji' => $this->flag_emoji,
            'is_active' => $this->is_active,
            'display_order' => $this->display_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
