# Mini flujo de firma de documentos

API en **Laravel 13** + front en **Angular 22**. Se sube un documento (PDF, imagen u ofimática), se asignan N firmantes (email + RUT) y cada uno firma desde un enlace único con token. El documento pasa de `pendiente` → `firmado_parcial` → `completo`.

## Cómo correrlo

Requisitos: Docker y Docker Compose. No necesitas PHP, Composer ni Node en tu máquina.

```bash
export GID=$(id -g)        # para que los archivos generados sean tuyos (UID ya existe en bash)
docker compose up --build
```

La primera vez tarda unos minutos (instala dependencias dentro de los contenedores).

| Servicio | URL |
|---|---|
| Front (Angular) | http://localhost:4200 |
| API (Laravel) | http://localhost:8000/api |

El front proxea `/api` hacia la API, por eso no hay CORS que configurar en desarrollo.

### Tests

```bash
docker compose run --rm api php artisan test                      # backend (44 tests)
docker compose run --rm -e CI=true web npx ng test --watch=false  # frontend (7 tests)
```

## Flujo de uso

1. En http://localhost:4200/new sube un documento y agrega firmantes. Al crear, la pantalla muestra los **enlaces de firma** (no se envían correos; ver decisiones).
2. Abre cada enlace `/sign/<token>` y pulsa «Firmar documento».
3. En http://localhost:4200 verás el estado actualizado.

### Con curl

```bash
# Crear documento (devuelve sign_links con los tokens)
curl -H 'Accept: application/json' -F title=Contrato -F file=@contrato.pdf \
  -F 'signers[0][rut]=11111111-1' -F 'signers[0][email]=a@x.cl' \
  -F 'signers[1][rut]=12345678-5'   -F 'signers[1][email]=b@x.cl' \
  http://localhost:8000/api/documents

# Webhook: marcar un firmante como firmado (firma HMAC-SHA256 del cuerpo)
BODY='{"token":"<TOKEN>"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "cambia-esto-en-local" | awk '{print $2}')
curl -X POST -H "X-Signature: $SIG" -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "$BODY" http://localhost:8000/api/webhooks/signer-signed
```

## Endpoints

| Método | Ruta | Descripción | Errores |
|---|---|---|---|
| POST | `/api/documents` | Multipart: `title`, `file` (PDF, imagen u ofimática ≤ 10 MB), `signers[i][rut]`, `signers[i][email]`. 201 con documento y `sign_links`. | 422 |
| GET | `/api/documents` | Lista paginada con `status`, `signed_count`, `signers_count` y firmantes. | |
| GET | `/api/sign/{token}` | Datos del documento y del firmante. | 404 |
| POST | `/api/sign/{token}` | Firma al dueño del token. | 404, 409 si ya firmó |
| POST | `/api/webhooks/signer-signed` | Body `{"token": "..."}` + header `X-Signature`. Idempotente. | 401, 404, 422 |

Todos los errores son JSON con `message` (y `errors` en 422).

## Decisiones

- **Estado derivado, no guardado.** `Document::status()` se calcula a partir de los firmantes (misma regla que el ejercicio de la parte 1), así no puede desincronizarse. A cambio hay que cargar los firmantes (`with('signers')` evita N+1). Con muchos firmantes por documento se podría usar un `withCount`.
- **Firma atómica e idempotente.** `Signer::markAsSigned()` hace un `UPDATE ... WHERE state = 'pendiente'`: dos peticiones simultáneas con el mismo token no pueden firmar ambas, y la fecha de la primera firma nunca se pisa. La API de firma responde 409; el webhook responde 200 con `changed: false`, porque reintentar un callback no debe ser un error.
- **Webhook protegido con HMAC-SHA256** sobre el cuerpo crudo, comparado con `hash_equals`. Si no hay secreto configurado se rechaza todo (fail closed). El secreto está en `WEBHOOK_SECRET` (`backend/.env`).
- **Token** aleatorio de 64 caracteres, con índice único, oculto en las respuestas salvo en la creación del documento. Throttle de 30/min en la firma.
- **RUT chileno** validado con módulo 11 y con un único formato permitido: sin puntos y con guión (`12345678-5`). Los RUT con puntos o sin guión se rechazan con 422. La `k` se pasa a mayúscula para que `10000013-k` y `10000013-K` cuenten como el mismo firmante. Hay validación en el cliente solo por UX; la válida es la del servidor.
- **Tipos de archivo:** PDF, imágenes (jpg, png, webp) y ofimática (doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp), máx. 10 MB. `mimes` valida por el contenido real del archivo, no solo por la extensión. El Dockerfile sube `upload_max_filesize` a 10 MB (PHP trae 2 MB por defecto). No se convierte ni se previsualiza el archivo.
- **Archivos** en el disco `local` (privado, no servido públicamente). Si falla la transacción se borra el archivo para no dejar huérfanos.
- **SQLite** para desarrollo, demo y tests, por simplicidad (cero servicios extra). En producción usaría MariaDB/PostgreSQL; el código no depende de SQLite.
- **Sin autenticación, sin correo real ni colas**, a propósito: es una PoC acotada en tiempo. Los enlaces de firma se muestran al crear el documento.
- **Docker mínimo:** `php artisan serve` y `ng serve`, adecuado para desarrollo, no para producción.

## Qué quedó fuera / siguientes pasos

- Autenticación de quien crea documentos y permisos para listarlos.
- Envío real de correos con el enlace (cola + `Mailable`), y reenvío.
- Expiración de tokens y guardar solo su hash en base de datos.
- Descarga/visualización del documento en la página de firma y firma electrónica con validez legal.
- Almacenamiento en S3, imagen Docker de producción (nginx + php-fpm, build de Angular), CI.
- Tests end-to-end del front (Playwright).

## Estructura

```
backend/    Laravel (app/Models, app/Http/Controllers/Api, app/Support/Rut.php, tests/)
frontend/   Angular standalone (src/app/documents, src/app/sign, src/app/api.service.ts)
docker-compose.yml
```
