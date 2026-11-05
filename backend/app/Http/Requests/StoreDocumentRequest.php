<?php

namespace App\Http\Requests;

use App\Rules\ChileanRut;
use App\Support\Rut;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza los RUT (sin puntos, con guion) para que `distinct` compare el mismo formato. */
    protected function prepareForValidation(): void
    {
        $signers = $this->input('signers');

        if (! is_array($signers)) {
            return;
        }

        foreach ($signers as $i => $signer) {
            if (is_array($signer) && is_string($signer['rut'] ?? null)) {
                $signers[$i]['rut'] = Rut::normalize($signer['rut']) ?? $signer['rut'];
            }
        }

        $this->merge(['signers' => $signers]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'signers' => ['required', 'array', 'min:1', 'max:20'],
            'signers.*.rut' => ['required', 'string', new ChileanRut, 'distinct'],
            'signers.*.email' => ['required', 'email:rfc', 'max:255'],
        ];
    }
}
