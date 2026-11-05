<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['title', 'file_path'];

    public function signers(): HasMany
    {
        return $this->hasMany(Signer::class);
    }

    /**
     * El estado se deriva de los firmantes (misma regla que la parte 1);
     * no se persiste para que no pueda quedar desincronizado.
     */
    public function status(): DocumentStatus
    {
        $total = $this->signers->count();
        $signed = $this->signers->filter(fn (Signer $s) => $s->isSigned())->count();

        return match (true) {
            $signed === 0 => DocumentStatus::Pendiente,
            $signed === $total => DocumentStatus::Completo,
            default => DocumentStatus::FirmadoParcial,
        };
    }
}
