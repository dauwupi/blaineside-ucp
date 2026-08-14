<?php
/**
 * POST /api/appeal-verdict.php
 * Body: { id, verdict: "accepted"|"rejected", comment: "...", staff_only?: bool }
 *
 * Concludes an appeal.
 *
 * Two things this endpoint insists on, both because they were asked for and
 * because both are the difference between a decision and a shrug:
 *
 *   1. Pending is not a verdict. It is where every appeal starts and there
 *      is no way back to it, so it isn't an option here — the only values
 *      accepted are 'accepted' and 'rejected'.
 *
 *   2. A comment is mandatory either way. A rejection with no reason is the
 *      single most common cause of the same person appealing again, and an
 *      acceptance with no reason leaves the next administrator unable to
 *      tell whether the ban was wrong or the player was forgiven.
 *
 * Accepting lifts the punishment where the UCP can lift it. Today that means
 * user locks — the account goes back to active and the lock is cleared. Game,
 * forum and Discord bans are lifted wherever they were issued; the appeal
 * records the decision and says so plainly rather than pretending.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('appeal_verdict', 20);

$pdo = db();
$acc = current_account($pdo);

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}

$in        = read_input();
$id        = (int)($in['id'] ?? 0);
$verdict   = strtolower(trim((string)($in['verdict'] ?? '')));
$comment   = trim((string)($in['comment'] ?? ''));
$staffOnly = !empty($in['staff_only']);
$wait      = (int)($in['wait'] ?? 0);      // days, rejections only

if (!in_array($verdict, ['accepted', 'rejected'], true)) {
    fail('Choose whether the appeal is accepted or rejected.', 422);
}

/* A rejection carries a wait.
 *
 * Checked against the ladder rather than accepted as a number: a wait is a
 * consequence, and a consequence somebody can type a number into is not a
 * rule. It also stops "0 days", which would make a rejection meaningless,
 * and "3650 days", which would make it a ban by another name. */
$waits = appeals_has_waits($pdo);
if ($verdict === 'rejected' && $waits && !in_array($wait, BS_APPEAL_WAITS, true)) {
    fail('Choose how long they wait before appealing again — '
       . implode(', ', BS_APPEAL_WAITS) . ' days.', 422);
}

$st = $pdo->prepare('SELECT * FROM ucp_appeals WHERE id = ? LIMIT 1');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) fail('There is no appeal with that number.', 404);

$ps    = appeal_punishments($pdo, $a);
$block = appeal_conclude_block($pdo, $acc, $a, $ps ?: null);
if ($block !== null) {
    json_out(['ok' => false, 'error' => $block], 403);
}

if ($comment === '') {
    fail('Write the reason for this decision. The appellant is shown it, so write it to them.',
         422);
}
if (mb_strlen($comment) > BS_APPEAL_COMMENT_MAX) {
    fail('That is too long for a verdict comment.', 422);
}

/* The comment goes on FIRST.
 *
 * If the update below fails, an appeal with a reason and no verdict is a
 * recoverable mess; a verdict with no reason is the exact thing this
 * endpoint exists to prevent. A staff-only verdict comment is allowed but
 * unusual — it means the appellant is told the outcome and not the reason,
 * which the page warns about. */
$now = time();
$pdo->prepare(
    'INSERT INTO ucp_appeal_comments
        (appeal_id, author_id, author_name, author_is_staff, staff_only, body, created_at)
     VALUES (?, ?, ?, 1, ?, ?, ?)'
)->execute([$id, (int)$acc['id'], (string)$acc['username'],
            $staffOnly ? 1 : 0, $comment, $now]);

/* comments_enabled goes to 0 with the verdict.
 *
 * A decided appeal is finished. Leaving it open invites the appellant to
 * argue with the outcome in a thread nobody is reading any more, and the
 * reply that never comes reads as being ignored. Staff can still write here
 * — appeal-comment.php checks the status, not this flag, for them. */
$reappeal = ($verdict === 'rejected' && $waits) ? $now + $wait * 86400 : null;

/* reappeal_at only exists after docs/migration-appeal-cooldown.sql. One
   migration behind, the verdict still lands and simply carries no wait. */
$pdo->prepare(
    'UPDATE ucp_appeals
        SET status = ?, concluded_at = ?, concluded_by = ?, concluded_by_name = ?,
            updated_at = ?, comments_enabled = 0'
        . ($waits ? ', reappeal_at = ' . ($reappeal === null ? 'NULL' : (int)$reappeal) : '') . ',
            handler_id = COALESCE(handler_id, ?), handler_name = COALESCE(handler_name, ?)
      WHERE id = ? AND status = \'pending\''
)->execute([$verdict, $now, (int)$acc['id'], (string)$acc['username'], $now,
            (int)$acc['id'], (string)$acc['username'], $id]);

/* Accepting lifts EVERY punishment the appeal covers.
 *
 * One appeal, one decision — an appeal against a game ban and a forum ban
 * that accepted only one of them would be a verdict the appellant cannot
 * read off the page. If a handler wants to lift one and keep the other,
 * that is two appeals, and the reason to say so is the same reason this is
 * one: it has to be legible to the person it happens to. */
$lifted = null;
if ($verdict === 'accepted' && $ps) {
    $done = [];
    $unlocked = false;

    foreach ($ps as $p) {
        punish_lift($pdo, (int)$p['id'], (int)$acc['id'], (string)$acc['username'],
                    'Appeal #' . $id . ' accepted');

        if ((string)$p['kind'] === 'user_lock') {
            /* The UCP owns this one, so accepting really does end it.
             * Mirrors api/member-lock.php's unlock path. */
            $pdo->prepare(
                'UPDATE ucp_accounts
                    SET status = \'active\', locked_at = NULL, locked_by = NULL,
                        locked_by_name = NULL, lock_reason = NULL
                  WHERE id = ? AND status = \'locked\''
            )->execute([(int)$a['account_id']]);
            security_log($pdo, (int)$a['account_id'], 'account_unlocked',
                'Lock removed by ' . $acc['username'] . ' on appeal #' . $id, 'good');
            $unlocked = true;
        } else {
            /* Enforced somewhere the UCP cannot reach yet. The record says
             * accepted; it does not claim to have unbanned anyone. */
            $done[] = punish_kind_label((string)$p['kind']);
        }
    }

    $bits = [];
    if ($unlocked) $bits[] = 'The user lock has been removed.';
    if ($done) {
        $bits[] = 'Marked as lifted here — the ' . implode(' and ', $done)
                . ' still ' . (count($done) > 1 ? 'have' : 'has')
                . ' to be removed where ' . (count($done) > 1 ? 'they were' : 'it was')
                . ' issued.';
    }
    $lifted = $bits ? implode(' ', $bits) : null;
}

appeal_log_add($pdo, $id, $acc, 'verdict',
    'Concluded as ' . $verdict
    . ($verdict === 'rejected' && $waits ? ' — can appeal again in ' . $wait . ' days' : '')
    . ($lifted ? ' — ' . $lifted : ''));

ok([
    'id'       => $id,
    'status'   => $verdict,
    'lifted'   => $lifted,
    'reappeal' => $reappeal,
    'message'  => 'Appeal ' . $verdict . '.'
                . ($verdict === 'rejected' && $waits
                    ? ' They can appeal again in ' . $wait . ' days.' : '')
                . ($lifted ? ' ' . $lifted : ''),
]);
