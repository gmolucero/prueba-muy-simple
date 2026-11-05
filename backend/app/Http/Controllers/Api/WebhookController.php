<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Signer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /** Callback externo: marca al firmante dueño del token. Idempotente. */
    public function signerSigned(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);

        $signer = Signer::where('token', $data['token'])->first()
            ?? abort(404, 'Enlace de firma no válido.');

        $changed = $signer->markAsSigned(now());

        // Repetir el webhook no es un error: responde 200 con changed=false.
        return response()->json([
            'changed' => $changed,
            'document_status' => $signer->document->load('signers')->status()->value,
        ]);
    }
}
