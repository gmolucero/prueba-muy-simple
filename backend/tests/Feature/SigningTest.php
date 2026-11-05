<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Signer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigningTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Document, 1: Signer, 2: Signer} */
    private function documentWithTwoSigners(): array
    {
        $document = Document::create(['title' => 'Doc', 'file_path' => 'documents/x.pdf']);
        $a = $document->signers()->create(['rut' => '11111111-1', 'email' => 'a@x.cl', 'token' => str_repeat('a', 64)]);
        $b = $document->signers()->create(['rut' => '12345678-5', 'email' => 'b@x.cl', 'token' => str_repeat('b', 64)]);

        return [$document, $a, $b];
    }

    public function test_show_returns_document_and_signer(): void
    {
        $this->documentWithTwoSigners();

        $this->getJson('/api/sign/'.str_repeat('a', 64))
            ->assertOk()
            ->assertJsonPath('data.document.title', 'Doc')
            ->assertJsonPath('data.signer.state', 'pendiente');
    }

    public function test_signing_moves_document_from_pendiente_to_parcial_to_completo(): void
    {
        [$document] = $this->documentWithTwoSigners();

        $this->postJson('/api/sign/'.str_repeat('a', 64))
            ->assertOk()
            ->assertJsonPath('data.signer.state', 'firmado')
            ->assertJsonPath('data.document.status', 'firmado_parcial');

        $this->postJson('/api/sign/'.str_repeat('b', 64))
            ->assertOk()
            ->assertJsonPath('data.document.status', 'completo');

        $this->assertSame('completo', $document->fresh('signers')->status()->value);
    }

    public function test_unknown_token_returns_404(): void
    {
        $this->getJson('/api/sign/'.str_repeat('z', 64))
            ->assertNotFound()
            ->assertJsonPath('message', 'Enlace de firma no válido.');

        $this->postJson('/api/sign/'.str_repeat('z', 64))->assertNotFound();
    }

    public function test_signing_twice_returns_409_and_keeps_first_date(): void
    {
        $this->documentWithTwoSigners();
        $token = str_repeat('a', 64);

        $this->postJson("/api/sign/$token")->assertOk();
        $firstDate = Signer::where('token', $token)->value('signed_at');

        $this->travel(1)->day();
        $this->postJson("/api/sign/$token")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Este firmante ya firmó.');

        $this->assertEquals($firstDate, Signer::where('token', $token)->value('signed_at'));
    }

    public function test_mark_as_signed_is_idempotent_at_model_level(): void
    {
        [, $a] = $this->documentWithTwoSigners();

        $this->assertTrue($a->markAsSigned(now()));
        $this->assertFalse($a->markAsSigned(now()));
    }
}
