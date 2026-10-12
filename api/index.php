<?php

/**
 * Front controller untuk runtime Vercel PHP (vercel-php).
 *
 * Catatan penting:
 * - Runtime ini DIKELola komunitas; verifikasi versi terbaru di
 *   https://github.com/vercel-community/php sebelum deploy.
 * - Filesystem serverless bersifat sementara: pastikan FILESYSTEM_DISK
 *   memakai penyimpanan cloud (s3) dan APP_ENV=production.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
