<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'email' => $this->email ?? null,
            'created_at' => isset($this->created_at)
                ? (method_exists($this->created_at, 'toIso8601String') ? $this->created_at->toIso8601String() : (string) $this->created_at)
                : null,
        ];
    }
}
