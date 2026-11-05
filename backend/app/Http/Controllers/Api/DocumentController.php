<?php

namespace App\Http\Controllers\Api;

use App\Enums\SignerState;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index()
    {
        return DocumentResource::collection(
            Document::with('signers')->latest('id')->paginate(15)
        );
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('documents'); // disco local (privado)

        try {
            $expiresAt = now()->addDays(config('signing.token_ttl_days'));

            $document = DB::transaction(function () use ($request, $path, $expiresAt) {
                $document = Document::create([
                    'title' => $request->validated('title'),
                    'file_path' => $path,
                ]);

                foreach ($request->validated('signers') as $signer) {
                    $document->signers()->create([
                        'rut' => $signer['rut'],
                        'email' => $signer['email'],
                        'token' => Str::random(64),
                        'token_expires_at' => $expiresAt,
                        'state' => SignerState::Pendiente,
                    ]);
                }

                return $document->load('signers');
            });
        } catch (\Throwable $e) {
            Storage::delete($path); // no dejar archivos huérfanos si falla la BD
            throw $e;
        }

        // Los tokens solo se entregan aquí, en la creación (no hay envío real de correo en esta PoC).
        $links = $document->signers->map(fn ($s) => [
            'rut' => $s->rut,
            'email' => $s->email,
            'expires_at' => $s->token_expires_at->toIso8601String(),
            'sign_url' => rtrim(config('app.frontend_url'), '/').'/sign/'.$s->makeVisible('token')->token,
        ])->all();

        return DocumentResource::make($document)
            ->additional(['sign_links' => $links])
            ->response()
            ->setStatusCode(201);
    }
}
