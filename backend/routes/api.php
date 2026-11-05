<?php

use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SigningController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::get('/documents', [DocumentController::class, 'index']);
Route::post('/documents', [DocumentController::class, 'store']);

Route::get('/sign/{token}', [SigningController::class, 'show']);
Route::post('/sign/{token}', [SigningController::class, 'sign'])->middleware('throttle:30,1');

Route::post('/webhooks/signer-signed', [WebhookController::class, 'signerSigned'])
    ->middleware(VerifyWebhookSignature::class);
