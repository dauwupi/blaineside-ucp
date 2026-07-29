<?php
/**
 * GET /api/csrf.php
 * Issues (or returns) this session's CSRF token. The auth pages fetch this on
 * load and send it back as an X-CSRF-Token header on every POST.
 */
require __DIR__ . '/_bootstrap.php';

ok(['token' => csrf_token()]);
