<?php
/**
 * GET /api/stats.php
 *
 * Community numbers for the dashboard's stat strip.
 *
 * `users` and `applications` are real. The other three tiles up there
 * (characters, vehicles, properties) have no tables behind them yet, so this
 * endpoint doesn't pretend otherwise — it reports what exists and says
 * nothing about the rest. The dashboard leaves those tiles on their designed
 * placeholders until there is something true to put in them.
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

/* Applications that were actually SENT. Drafts are somebody halfway
   through a form and are not an application anybody has received, so they
   would inflate this the moment the page was opened.

   Guarded: one migration behind, the key is simply absent and the tile
   keeps its placeholder rather than the endpoint failing and taking the
   registered-user count down with it. */
$applications = null;
try {
    $st = $pdo->query("SELECT COUNT(*) FROM ucp_applications WHERE status <> 'draft'");
    $applications = (int)$st->fetchColumn();
} catch (Throwable $e) {
}

$out = ['users' => $users];
if ($applications !== null) $out['applications'] = $applications;

ok($out);
