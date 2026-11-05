<?php

return [
    // Días de vigencia del enlace de firma desde que se crea el documento.
    'token_ttl_days' => (int) env('SIGN_TOKEN_TTL_DAYS', 7),
];
