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

    /** Pasa la K a mayúscula para que `distinct` trate 10000013-k y 10000013-K como el mismo RUT. */
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

    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo debe ser PDF, imagen (jpg, png, webp) o de ofimática (doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp).',
            'file.max' => 'El archivo no puede superar los 10 MB.',
            // PHP corta las subidas que exceden upload_max_filesize antes de llegar a las reglas.
            'file.uploaded' => 'No se pudo subir el archivo (máximo 10 MB).',
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // PDF, imágenes y ofimática (Microsoft Office / OpenDocument). `mimes` valida por el
            // contenido real del archivo, no solo por la extensión del nombre. Máx. 10 MB.
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp', 'max:10240'],
            'signers' => ['required', 'array', 'min:1', 'max:20'],
            'signers.*.rut' => ['required', 'string', new ChileanRut, 'distinct'],
            'signers.*.email' => ['required', 'email:rfc', 'max:255'],
        ];
    }
}
