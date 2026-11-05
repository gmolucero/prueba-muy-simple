<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Contrato de arriendo',
            'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
            'signers' => [
                ['rut' => '11111111-1', 'email' => 'a@x.cl'],
                ['rut' => '12345678-5', 'email' => 'b@x.cl'],
            ],
        ], $override);
    }

    public function test_creates_document_with_signers_and_returns_sign_links(): void
    {
        Storage::fake('local');

        $response = $this->postJson('/api/documents', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.signers_count', 2)
            ->assertJsonCount(2, 'sign_links')
            ->assertJsonMissingPath('data.signers.0.token');

        $this->assertDatabaseHas('signers', ['rut' => '11111111-1', 'state' => 'pendiente']);
        $this->assertEqualsWithDelta(
            now()->addDays(7)->timestamp,
            \Carbon\Carbon::parse($response->json('sign_links.0.expires_at'))->timestamp,
            5,
        );
        $this->assertStringContainsString('/sign/', $response->json('sign_links.0.sign_url'));
        $this->assertCount(1, Storage::disk('local')->files('documents'));
    }

    public function test_rejects_document_without_signers(): void
    {
        $this->postJson('/api/documents', $this->payload(['signers' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('signers');
    }

    public function test_rejects_invalid_rut_and_email(): void
    {
        $this->postJson('/api/documents', $this->payload(['signers' => [
            ['rut' => '11111111-2', 'email' => 'no-es-email'],
        ]]))->assertUnprocessable()
            ->assertJsonValidationErrors(['signers.0.rut', 'signers.0.email']);
    }

    public function test_rejects_ruts_with_dots_or_without_hyphen(): void
    {
        foreach (['11.111.111-1', '111111111'] as $rut) {
            $this->postJson('/api/documents', $this->payload(['signers' => [
                ['rut' => $rut, 'email' => 'a@x.cl'],
            ]]))->assertUnprocessable()->assertJsonValidationErrors('signers.0.rut');
        }
    }

    public function test_rejects_duplicated_rut_even_if_k_case_differs(): void
    {
        $this->postJson('/api/documents', $this->payload(['signers' => [
            ['rut' => '10000013-k', 'email' => 'a@x.cl'],
            ['rut' => '10000013-K', 'email' => 'b@x.cl'],
        ]]))->assertUnprocessable()->assertJsonValidationErrors('signers.1.rut');
    }

    #[DataProvider('allowedFiles')]
    public function test_accepts_pdf_images_and_office_files(string $name, string $mime): void
    {
        Storage::fake('local');

        $this->postJson('/api/documents', $this->payload([
            'file' => UploadedFile::fake()->create($name, 50, $mime),
        ]))->assertCreated();
    }

    public static function allowedFiles(): array
    {
        return [
            'pdf' => ['a.pdf', 'application/pdf'],
            'png' => ['a.png', 'image/png'],
            'jpg' => ['a.jpg', 'image/jpeg'],
            'docx' => ['a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['a.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'pptx' => ['a.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'odt' => ['a.odt', 'application/vnd.oasis.opendocument.text'],
        ];
    }

    public function test_rejects_files_over_10mb(): void
    {
        $this->postJson('/api/documents', $this->payload([
            'file' => UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_rejects_disallowed_file_types(): void
    {
        $this->postJson('/api/documents', $this->payload([
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_failed_validation_does_not_store_anything(): void
    {
        Storage::fake('local');

        $this->postJson('/api/documents', $this->payload(['signers' => []]))->assertUnprocessable();

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->files('documents'));
    }

    public function test_lists_documents_with_status_and_without_tokens(): void
    {
        Storage::fake('local');
        $this->postJson('/api/documents', $this->payload())->assertCreated();

        $this->getJson('/api/documents')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'pendiente')
            ->assertJsonPath('data.0.signed_count', 0)
            ->assertJsonCount(2, 'data.0.signers')
            ->assertJsonMissingPath('data.0.signers.0.token');
    }
}
