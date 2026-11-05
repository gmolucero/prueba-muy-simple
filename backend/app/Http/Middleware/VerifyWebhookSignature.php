<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Valida el header X-Signature = HMAC-SHA256 del cuerpo crudo con el secreto compartido. */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.webhook.secret');
        $given = (string) $request->header('X-Signature', '');

        // Sin secreto configurado se rechaza todo (fail closed).
        $valid = $secret !== ''
            && hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $given);

        if (! $valid) {
            return response()->json(['message' => 'Firma del webhook inválida.'], 401);
        }

        return $next($request);
    }
}
