<?php
/**
 * POST /api/scratchpad-add.php
 * Body: { account_id, body: "..." }
 *
 * Adds one note to a player's Scratchpad. See api/_scratchpad.php for what
 * the Scratchpad is and the three rules it runs on.
 *
 * Trainee Admin and above — the same bar as opening the account at all. If
 * somebody can read the notes they can add to them; a read-only tier would
 * mean an administrator who spots something has to ask a colleague to write
 * it down, and it would not get written down.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/_scratchpad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('pad_add', 30);

$pdo = db();
$acc = current_account($pdo);

if (!pad_may_view($acc)) {
    json_out(['ok' => false,
              'error' => 'The Scratchpad is for ' . rank_name(BS_ADMIN_MIN_RANK)
                       . ' and above.'], 403);
}
if (!pad_available($pdo)) {
    json_out(['ok' => false,
              'error' => 'The Scratchpad isn\'t set up on this server yet — '
                       . 'docs/migration-scratchpad.sql hasn\'t been run.'], 409);
}

$in   = read_input();
$id   = (int)($in['account_id'] ?? 0);
$body = trim((string)($in['body'] ?? ''));

$st = $pdo->prepare('SELECT id, admin_rank FROM ucp_accounts WHERE id = ? LIMIT 1');
$st->execute([$id]);
$target = $st->fetch();
if (!$target) fail('That account no longer exists.', 404);

/* The same rule that governs opening a staff account governs writing on one.
   Otherwise the Scratchpad becomes the way round it. */
if (!admin_may_view(admin_can_see_staff($pdo, $acc), (int)$target['admin_rank'],
                    (int)$acc['id'] === (int)$target['id'])) {
    json_out(['ok' => false,
              'error' => 'That\'s a staff account. You can\'t write notes on it.'], 403);
}

if ($body === '') {
    fail('Write the note first.', 422);
}
if (mb_strlen($body) > BS_PAD_MAX) {
    fail('That note is too long — keep it under ' . BS_PAD_MAX . ' characters.', 422);
}

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_scratchpad
        (account_id, author_id, author_name, author_rank, body, created_at)
     VALUES (?, ?, ?, ?, ?, ?)'
)->execute([$id, (int)$acc['id'], (string)$acc['username'], (int)$acc['admin_rank'],
            $body, $now]);

ok([
    'note' => [
        'id'         => (int)$pdo->lastInsertId(),
        'by'         => (string)$acc['username'],
        'rank'       => (int)$acc['admin_rank'],
        'rank_name'  => rank_name((int)$acc['admin_rank']),
        'body'       => $body,
        'at'         => $now,
        'can_delete' => true,
    ],
    'message' => 'Note added. The player cannot see it.',
]);
