<?php
/**
 * GET /api/stats.php
 *
 * Community numbers for the dashboard's stat strip.
 *
 * Only `users` is real. The other four tiles up there (characters, vehicles,
 * properties, applications) have no tables behind them yet, so this endpoint
 * doesn't pretend otherwise — it reports what exists and says nothing about
 * the rest. The dashboard leaves those tiles on their designed placeholders
 * until there is something true to put in them.
 *
 * No sign-in required: it is a single aggregate count, the same number any
 * community puts on its front page, and nothing about it identifies anyone.
 * Throttled anyway, so it can't be used to hammer the database.
 */
require __DIR__ . '/_bootstrap.php';

throttle('stats', 60);

$pdo = db();

// Accounts that never confirmed their email aren't registered players, they
// are abandoned forms — counting them would inflate the number every time a
// bot filled one in. Suspended accounts DO count: the person is still real
// and still on the books.
$st = $pdo->query("SELECT COUNT(*) FROM ucp_accounts WHERE status <> 'pending'");
$users = (int)$st->fetchColumn();

ok([
    'users' => $users,
]);
