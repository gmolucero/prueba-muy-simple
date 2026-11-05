<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'secreto-de-test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.secret' => self::SECRET]);

        $document = Document::create(['title' => 'Doc', 'file_path' => 'documents/x.pdf']);
        $document->signers()->create(['rut' => '11111111-1', 'email' => 'a@x.cl', 'token' => str_repeat('a', 64)]);
        $document->signers()->create(['rut' => '12345678-5', 'email' => 'b@x.cl', 'token' => str_repeat('b', 64)]);
    }

    private function send(array $body, ?string $signature = null)
    {
        $json = json_encode($body);
        $signature ??= hash_hmac('sha256', $json, self::SECRET);

        return $this->call('POST', '/api/webhooks/signer-signed', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
        ], $json);
    }

    public function test_valid_signature_marks_signer_as_signed(): void
    {
        $this->send(['token' => str_repeat('a', 64)])
            ->assertOk()
            ->assertJson(['changed' => true, 'document_status' => 'firmado_parcial']);

        $this->assertDatabaseHas('signers', ['rut' => '11111111-1', 'state' => 'firmado']);
    }

    public function test_repeated_webhook_is_idempotent(): void
    {
        $this->send(['token' => str_repeat('a', 64)])->assertOk();

        $this->send(['token' => str_repeat('a', 64)])
            ->assertOk()
            ->assertJson(['changed' => false, 'document_status' => 'firmado_parcial']);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->send(['token' => str_repeat('a', 64)], 'firma-falsa')->assertUnauthorized();

        $this->assertDatabaseMissing('signers', ['state' => 'firmado']);
    }

    public function test_missing_signature_is_rejected(): void
    {
        $this->postJson('/api/webhooks/signer-signed', ['token' => str_repeat('a', 64)])->assertUnauthorized();
    }

    public function test_rejects_everything_when_secret_is_not_configured(): void
    {
        config(['services.webhook.secret' => '']);

        $this->send(['token' => str_repeat('a', 64)], hash_hmac('sha256', json_encode(['token' => str_repeat('a', 64)]), ''))
            ->assertUnauthorized();
    }

    public function test_unknown_token_returns_404(): void
    {
        $this->send(['token' => str_repeat('z', 64)])->assertNotFound();
    }

    public function test_invalid_body_returns_422(): void
    {
        $this->send(['token' => 'corto'])->assertUnprocessable();
    }
}
