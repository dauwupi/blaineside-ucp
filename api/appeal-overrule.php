<?php
/**
 * POST /api/appeal-overrule.php
 * Body: { id, comment: "..." }
 *
 * Overturns a rejected appeal.
 *
 * Staff Management, Management and the Founder — the same people who can
 * open staff accounts in Administrative Search, and for the same reason.
 * This is the power to reverse another administrator's decision, and it
 * belongs with the people whose job is the staff team rather than with rank
 * alone.
 *
 * Only rejected appeals. There is nothing to overturn on an accepted one,
 * and "un-accepting" an appeal would mean re-banning somebody who has been
 * told they are clear — which is a new punishment, not an appeal outcome.
 *
 * The rejection is NOT deleted. The appeal keeps the rejection, its reason
 * and whoever gave it, and gains a record of who overturned it and why. An
 * appeal whose history quietly rewrites itself is worth nothing to the next
 * person who has to judge a similar one.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('appeal_overrule', 15);

$pdo = db();
$acc = current_account($pdo);

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}
if (!appeal_may_overrule($pdo, $acc)) {
    json_out([
        'ok'    => false,
        'error' => 'Overturning a decision is for Management, the Founder, or anyone holding '
                 . team_label('staff_management') . '.',
    ], 403);
}

$in      = read_input();
$id      = (int)($in['id'] ?? 0);
$comment = trim((string)($in['comment'] ?? ''));

/* Whether the punishments actually come off.
 *
 * Overturning the DECISION and lifting the BAN are two different calls, and
 * conflating them was wrong: a rejection can be overturned because it was
 * handled badly — no reason given, the wrong administrator, a reply the
 * appellant never got — while the ban itself was still correct. Forcing an
 * unban in that case would punish the process failure by releasing somebody
 * who should stay banned.
 *
 * Sent explicitly rather than defaulted, so the person pressing it has
 * chosen. */
$lift = !empty($in['lift']);

$st = $pdo->prepare('SELECT * FROM ucp_appeals WHERE id = ? LIMIT 1');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) fail('There is no appeal with that number.', 404);

if ((int)$acc['id'] === (int)$a['account_id']) {
    json_out(['ok' => false, 'error' => 'You can\'t overturn a decision about yourself.'], 403);
}
if ($a['status'] !== 'rejected') {
    json_out(['ok' => false,
              'error' => 'Only a rejected appeal can be overturned. This one was '
                       . $a['status'] . '.'], 409);
}
if ($comment === '') {
    fail('Write why the decision is being overturned. The appellant and the administrator who '
       . 'made it are both shown this.', 422);
}
if (mb_strlen($comment) > BS_APPEAL_COMMENT_MAX) {
    fail('That is too long.', 422);
}

/* The comment first, for the same reason as the verdict: an appeal with a
   reason and no reversal is recoverable, a reversal with no reason is the
   thing this endpoint exists to prevent. Never staff-only — the point of an
   overrule is that the appellant is told the decision changed and why. */
$now = time();
$pdo->prepare(
    'INSERT INTO ucp_appeal_comments
        (appeal_id, author_id, author_name, author_is_staff, staff_only, body, created_at)
     VALUES (?, ?, ?, 1, 0, ?, ?)'
)->execute([$id, (int)$acc['id'], (string)$acc['username'], $comment, $now]);

/* status becomes accepted; concluded_by KEEPS the original rejector. The
   overrule columns say who changed it. Both facts are true and the page
   shows both. reappeal_at is cleared — the wait was a consequence of a
   decision that no longer stands. */
$pdo->prepare(
    'UPDATE ucp_appeals
        SET status = \'accepted\', updated_at = ?, reappeal_at = NULL,
            overruled_at = ?, overruled_by = ?, overruled_by_name = ?
      WHERE id = ? AND status = \'rejected\''
)->execute([$now, $now, (int)$acc['id'], (string)$acc['username'], $id]);

/* And the punishments come off — if that is what was chosen. */
$ps       = appeal_punishments($pdo, $a);
$done     = [];
$unlocked = false;

foreach ($lift ? $ps : [] as $p) {
    punish_lift($pdo, (int)$p['id'], (int)$acc['id'], (string)$acc['username'],
                'Appeal #' . $id . ' overturned');

    if ((string)$p['kind'] === 'user_lock') {
        $pdo->prepare(
            'UPDATE ucp_accounts
                SET status = \'active\', locked_at = NULL, locked_by = NULL,
                    locked_by_name = NULL, lock_reason = NULL
              WHERE id = ? AND status = \'locked\''
        )->execute([(int)$a['account_id']]);
        security_log($pdo, (int)$a['account_id'], 'account_unlocked',
            'Lock removed by ' . $acc['username'] . ' — appeal #' . $id . ' overturned', 'good');
        $unlocked = true;
    } else {
        $done[] = punish_kind_label((string)$p['kind']);
    }
}

$bits = [];
if ($unlocked) $bits[] = 'The user lock has been removed.';
if ($done) {
    $bits[] = 'Marked as lifted here — the ' . implode(' and ', $done) . ' still '
            . (count($done) > 1 ? 'have' : 'has') . ' to be removed where '
            . (count($done) > 1 ? 'they were' : 'it was') . ' issued.';
}
if (!$lift) {
    $bits[] = 'The punishment stays in force — only the handling of the appeal was wrong.';
}
$lifted = $bits ? implode(' ', $bits) : null;

appeal_log_add($pdo, $id, $acc, 'overruled',
    'Overturned the rejection by ' . ($a['concluded_by_name'] ?: 'an administrator')
    . ($lift ? ' and lifted the punishment' : ' — punishment left in force')
    . ($lifted ? ' — ' . $lifted : ''));

ok([
    'id'      => $id,
    'status'  => 'accepted',
    'lifted'  => $lifted,
    'message' => 'Decision overturned.' . ($lifted ? ' ' . $lifted : ''),
]);
