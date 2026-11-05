<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Document */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status()->value,
            'signed_count' => $this->signers->filter->isSigned()->count(),
            'signers_count' => $this->signers->count(),
            'signers' => SignerResource::collection($this->signers),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
