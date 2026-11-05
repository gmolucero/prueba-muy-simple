<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Signer */
class SignerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rut' => $this->rut,
            'email' => $this->email,
            'state' => $this->state->value,
            'signed_at' => $this->signed_at?->toIso8601String(),
        ];
    }
}
