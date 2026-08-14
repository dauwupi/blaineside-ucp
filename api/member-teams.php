<?php
/**
 * POST /api/member-teams.php
 * Body: { id, teams: ["staff_management", ...] }
 *
 * Sets exactly which sub-groups one administrator holds. The list replaces
 * whatever they had — send the full list, not a change.
 *
 * Same gate as changing a group, on purpose: whoever can decide that someone
 * is a Lead Admin can decide what they look after. The extra rule is the
 * band — sub-groups only exist for Trainee Admin through Lead Admin.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_groups.php';
require_once __DIR__ . '/_teams.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('member_teams', 30);

$pdo = db();
$acc = current_account($pdo);
require_group_manager($acc);

if (!teams_available($pdo)) {
    json_out([
        'ok'    => false,
        'error' => 'Sub-groups aren\'t set up on this server yet — docs/migration-subgroups.sql '
                 . 'hasn\'t been run.',
    ], 409);
}

$in    = read_input();
$id    = (int)($in['id'] ?? 0);
$teams = is_array($in['teams'] ?? null) ? $in['teams'] : [];

$st = $pdo->prepare('SELECT id, username, admin_rank FROM ucp_accounts WHERE id = ? LIMIT 1');
$st->execute([$id]);
$target = $st->fetch();
if (!$target) fail('That account no longer exists.', 404);

// ---- May this actor touch this account at all? ----
// The same question as a group change, and the same answer: a Manager can't
// reach another Manager, and nobody edits themselves.
$block = groups_block_reason($acc, $target);
if ($block !== null) {
    json_out(['ok' => false, 'error' => $block], 403);
}

// ---- Is this account in the band at all? ----
$rank = (int)$target['admin_rank'];
if (!team_eligible($rank)) {
    json_out(['ok' => false, 'error' => team_ineligible_reason($rank)], 409);
}

// ---- Anything unrecognised is refused rather than dropped ----
// Silently ignoring a bad key would report success for a change that didn't
// happen, and the person would go away believing it had.
foreach ($teams as $k) {
    if (!in_array((string)$k, teams_keys(), true)) {
        json_out(['ok' => false, 'error' => 'That isn\'t a sub-group.'], 400);
    }
}

list($added, $removed) = teams_set($pdo, $id, array_map('strval', $teams), $acc);

if (!$added && !$removed) {
    ok([
        'id'      => $id,
        'teams'   => teams_for($pdo, $id),
        'message' => 'No change — ' . $target['username'] . ' already had exactly that.',
    ]);
}

// Written to the TARGET's log: it is their account whose permissions moved,
// and it is their log that should answer "since when, and who did it".
$bits = [];
if ($added)   $bits[] = 'gained ' . implode(', ', $added);
if ($removed) $bits[] = 'lost ' . implode(', ', $removed);

security_log(
    $pdo, $id, 'subgroups_changed',
    ucfirst(implode('; ', $bits)) . ' — by ' . $acc['username'],
    $added ? 'good' : 'warn'
);

ok([
    'id'      => $id,
    'teams'   => teams_for($pdo, $id),
    'message' => $target['username'] . ' ' . implode(' and ', $bits) . '.',
]);
