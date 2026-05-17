<?php

use Illuminate\Support\Facades\Route;

/*
 * Stateless JSON endpoints: no SPA session/CSRF middleware.
 * Prefix: /api/v1/public (see bootstrap/app.php).
 */

Route::get('/ping', fn (): array => ['ok' => true]);
