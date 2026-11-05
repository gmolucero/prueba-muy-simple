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
        $signer = $this->findOrFail($token);

        // Un enlace vencido deja de servir, salvo para ver que ya se firmó.
        if (! $signer->isSigned() && $signer->isExpired()) {
            return $this->expired();
        }

        return $this->payload($signer);
    }

    private function expired(): JsonResponse
    {
        return response()->json(['message' => 'El enlace de firma expiró.'], 410);
    }

    public function sign(string $token): JsonResponse
    {
        $signer = $this->findOrFail($token);

        if ($signer->isSigned()) {
            return response()->json(['message' => 'Este firmante ya firmó.'], 409);
        }

        if ($signer->isExpired()) {
            return $this->expired();
        }

        if (! $signer->markAsSigned(now())) {
            // Perdió una carrera: firmó (o expiró) entre la lectura y el UPDATE.
            return $signer->fresh()->isSigned()
                ? response()->json(['message' => 'Este firmante ya firmó.'], 409)
                : $this->expired();
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
