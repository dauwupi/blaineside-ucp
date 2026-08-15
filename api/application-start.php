<?php
/**
 * POST /api/application-start.php
 *
 * Draws a set of questions and opens a draft. Idempotent: if a draft is
 * already open it is returned untouched, so a double click cannot produce
 * two drafts or re-roll the scenarios of the one that exists.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('application_start', 10);

$pdo = db();
$acc = current_account($pdo);

if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$me = (int)$acc['id'];
$s  = app_state($pdo, $me);

if ($s['state'] === 'passed')  fail('You have already passed your application.', 409);
if ($s['state'] === 'pending') fail('Your application is already with Support Staff.', 409);

if ($s['state'] === 'draft') {
    ok(['id' => (int)$s['application']['id'], 'created' => false]);
}

try {
    $id = app_start_draft($pdo, $me);
} catch (RuntimeException $e) {
    fail($e->getMessage(), 409);
}

ok(['id' => $id, 'created' => true]);
