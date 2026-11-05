<?php

namespace Tests\Unit;

use App\Enums\DocumentStatus;
use App\Enums\SignerState;
use App\Models\Document;
use App\Models\Signer;
use PHPUnit\Framework\TestCase;

/** Replica en el modelo los casos del enunciado de la parte 1. */
class DocumentStatusTest extends TestCase
{
    private function documentWith(SignerState ...$states): Document
    {
        $document = new Document;
        $document->setRelation('signers', collect($states)->map(
            fn (SignerState $state) => new Signer(['state' => $state])
        ));

        return $document;
    }

    public function test_none(): void
    {
        $this->assertSame(DocumentStatus::Pendiente, $this->documentWith(SignerState::Pendiente, SignerState::Pendiente)->status());
    }

    public function test_some(): void
    {
        $this->assertSame(DocumentStatus::FirmadoParcial, $this->documentWith(SignerState::Firmado, SignerState::Pendiente)->status());
    }

    public function test_all(): void
    {
        $this->assertSame(DocumentStatus::Completo, $this->documentWith(SignerState::Firmado, SignerState::Firmado)->status());
    }

    public function test_single(): void
    {
        $this->assertSame(DocumentStatus::Completo, $this->documentWith(SignerState::Firmado)->status());
    }
}
