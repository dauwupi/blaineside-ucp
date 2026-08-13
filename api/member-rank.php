<?php
/**
 * POST /api/member-rank.php
 * Body: { id, rank }
 *
 * Promotes or demotes one account. Every rule in _groups.php is checked here,
 * on every request — the page's dropdown only decides what is easy to do, not
 * what is possible.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_groups.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('member_rank', 30);

$pdo = db();
$acc = current_account($pdo);
require_group_manager($acc);

$in   = read_input();
$id   = (int)($in['id'] ?? 0);
$rank = (int)($in['rank'] ?? -1);

if (!array_key_exists($rank, RANKS)) {
    json_out(['ok' => false, 'error' => 'That is not a group.'], 400);
}

$st = $pdo->prepare('SELECT id, username, admin_rank FROM ucp_accounts WHERE id = ? LIMIT 1');
$st->execute([$id]);
$target = $st->fetch();
if (!$target) fail('That account no longer exists.', 404);

// ---- May this actor touch this account at all? ----
$block = groups_block_reason($acc, $target);
if ($block !== null) {
    json_out(['ok' => false, 'error' => $block], 403);
}

// ---- May they hand out this particular group? ----
if (!in_array($rank, groups_assignable((int)$acc['admin_rank']), true)) {
    json_out([
        'ok'    => false,
        'error' => 'Only a Founder can put someone in Management or make another Founder.',
    ], 403);
}

$was = (int)$target['admin_rank'];
if ($was === $rank) {
    json_out(['ok' => false, 'error' => $target['username'] . ' is already ' . rank_name($rank) . '.'], 400);
}

// ---- Never leave the UCP without a Founder ----
// Demoting the last one would lock everybody out of the only group that can
// hand the rank back — a database job to undo.
if ($was === 9 && $rank < 9) {
    $founders = (int)$pdo->query('SELECT COUNT(*) FROM ucp_accounts WHERE admin_rank = 9')->fetchColumn();
    if ($founders <= 1) {
        json_out([
            'ok'    => false,
            'error' => 'That is the only Founder. Promote someone else first.',
        ], 409);
    }
}

$pdo->prepare('UPDATE ucp_accounts SET admin_rank = ? WHERE id = ?')->execute([$rank, $id]);

// Written to the TARGET's security log: it is their account that changed, and
// it is their log that should answer "when did this happen, and who did it".
security_log(
    $pdo, $id, 'group_changed',
    rank_name($was) . ' → ' . rank_name($rank) . ' by ' . $acc['username'],
    $rank > $was ? 'good' : 'warn'
);

ok([
    'id'      => $id,
    'rank'    => $rank,
    'role'    => rank_name($rank),
    'message' => $target['username'] . ' is now ' . rank_name($rank) . '.',
]);
