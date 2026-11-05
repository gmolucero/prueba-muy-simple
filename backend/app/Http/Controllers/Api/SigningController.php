<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SignerResource;
use App\Models\Signer;
use Illuminate\Http\JsonResponse;

class SigningController extends Controller
{
    public function show(string $token): JsonResponse
    {
        return $this->payload($this->findOrFail($token));
    }

    public function sign(string $token): JsonResponse
    {
        $signer = $this->findOrFail($token);

        if (! $signer->markAsSigned(now())) {
            return response()->json(['message' => 'Este firmante ya firmó.'], 409);
        }

        return $this->payload($signer);
    }

    private function findOrFail(string $token): Signer
    {
        return Signer::with('document.signers')->where('token', $token)->first()
            ?? abort(404, 'Enlace de firma no válido.');
    }

    private function payload(Signer $signer): JsonResponse
    {
        $document = $signer->document->load('signers');

        return response()->json(['data' => [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'status' => $document->status()->value,
            ],
            'signer' => SignerResource::make($signer)->resolve(),
        ]]);
    }
}
