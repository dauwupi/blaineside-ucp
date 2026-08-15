<?php
/**
 * GET /api/application-history.php?id=123
 *
 * Every application one account has had decided, for the card on their
 * read-only record. Staff only.
 *
 * It is a separate endpoint rather than another block bolted onto
 * api/profile.php because the record page should not get slower for
 * everybody the day applications are switched on: the card asks for
 * itself, and when the table is missing the card simply does not draw.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

throttle('application_history', 60);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_panel($acc)) {
    json_out(['ok' => false, 'error' => app_panel_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => true, 'available' => false, 'rows' => []], 200);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) fail('Which account?', 422);

$rows = app_history($pdo, $id);

/* The current state matters as much as the list: "waiting" and "never
   applied" look identical in a table of decided attempts, and they are
   very different facts about somebody an administrator is looking at. */
$state = app_state($pdo, $id);

ok([
    'available' => true,
    'rows'      => $rows,
    'state'     => $state['state'],
    'attempts'  => $state['attempts'],
]);
