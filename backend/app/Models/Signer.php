<?php

namespace App\Models;

use App\Enums\SignerState;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signer extends Model
{
    protected $fillable = ['document_id', 'rut', 'email', 'token', 'state', 'signed_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'state' => SignerState::class,
            'signed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function isSigned(): bool
    {
        return $this->state === SignerState::Firmado;
    }

    /**
     * Marca como firmado. Devuelve true si cambió algo, false si ya estaba firmado.
     * El UPDATE condicional hace la operación atómica: dos peticiones simultáneas
     * con el mismo token no pueden "firmar" las dos.
     */
    public function markAsSigned(CarbonInterface $at): bool
    {
        $changed = static::query()
            ->whereKey($this->getKey())
            ->where('state', SignerState::Pendiente->value)
            ->update(['state' => SignerState::Firmado->value, 'signed_at' => $at]);

        if ($changed === 0) {
            return false;
        }

        $this->refresh();

        return true;
    }
}
