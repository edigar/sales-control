<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller' => new SellerResource($this->whenLoaded('seller')),
            'amount' => $this->amount,
            'commission' => $this->commission ?? null,
            'date' => isset($this->date)
                ? (method_exists($this->date, 'toIso8601String') ? $this->date->toIso8601String() : (string) $this->date)
                : null,
        ];
    }
}
