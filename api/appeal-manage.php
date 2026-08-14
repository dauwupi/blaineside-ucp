<?php
/**
 * POST /api/appeal-manage.php
 * Body: { id, handler?: "name"|null, comments?: true|false }
 *
 * The two settings on the staff panel that aren't the verdict: who is
 * handling the appeal, and whether it is open to replies.
 *
 * One endpoint rather than two because they are one panel and one save. The
 * body says which of them is being changed by which keys it carries; sending
 * neither is refused rather than silently doing nothing.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('appeal_manage', 30);

$pdo = db();
$acc = current_account($pdo);

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}
/* Reassigning an appeal, and closing it to replies, are both ways to reach
   over the head of whoever is handling it. Senior Admin and above. */
if (!appeal_may_manage($acc)) {
    json_out(['ok' => false,
              'error' => 'Reassigning an appeal is for ' . rank_name(BS_APPEAL_MANAGE_RANK)
                       . ' and above.'], 403);
}

$in = read_input();
$id = (int)($in['id'] ?? 0);

$st = $pdo->prepare('SELECT * FROM ucp_appeals WHERE id = ? LIMIT 1');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) fail('There is no appeal with that number.', 404);

if ((int)$acc['id'] === (int)$a['account_id']) {
    json_out(['ok' => false, 'error' => 'You can\'t manage your own appeal.'], 403);
}

$did = [];

/* ---- handler ----------------------------------------------------------
   Given as a UCP name rather than an id, because that is what the person
   picking knows. Empty string or null clears it, which is how an appeal
   goes back into "Not assigned". */
if (array_key_exists('handler', $in)) {
    $name = trim((string)($in['handler'] ?? ''));

    if ($name === '') {
        $pdo->prepare('UPDATE ucp_appeals SET handler_id = NULL, handler_name = NULL,
                              updated_at = ? WHERE id = ?')->execute([time(), $id]);
        appeal_log_add($pdo, $id, $acc, 'handler', 'Unassigned the appeal.');
        $did[] = 'Handler cleared.';
    } else {
        $h = $pdo->prepare(
            'SELECT id, username, admin_rank FROM ucp_accounts
              WHERE username_lower = ? AND status = \'active\' LIMIT 1'
        );
        $h->execute([mb_strtolower($name)]);
        $t = $h->fetch();

        if (!$t) fail('There is no active account with that UCP name.', 404);

        /* A handler has to be able to do the job. Assigning an appeal to a
           player would leave it sitting in a queue they cannot open, looking
           handled when nobody is handling it. */
        if ((int)$t['admin_rank'] < BS_APPEAL_STAFF_RANK) {
            fail($t['username'] . ' isn\'t staff, so they can\'t be given an appeal.', 422);
        }
        if ((int)$t['id'] === (int)$a['account_id']) {
            fail('Somebody can\'t handle their own appeal.', 422);
        }

        $pdo->prepare('UPDATE ucp_appeals SET handler_id = ?, handler_name = ?, updated_at = ?
                        WHERE id = ?')
            ->execute([(int)$t['id'], (string)$t['username'], time(), $id]);
        appeal_log_add($pdo, $id, $acc, 'handler', 'Handler set to ' . $t['username'] . '.');
        $did[] = 'Handler set to ' . $t['username'] . '.';
    }
}

/* ---- comments open / closed ------------------------------------------ */
if (array_key_exists('comments', $in)) {
    $on = !empty($in['comments']);
    $pdo->prepare('UPDATE ucp_appeals SET comments_enabled = ?, updated_at = ? WHERE id = ?')
        ->execute([$on ? 1 : 0, time(), $id]);
    appeal_log_add($pdo, $id, $acc, 'comments',
        $on ? 'Opened the appeal to replies.' : 'Closed the appeal to replies.');
    $did[] = $on ? 'Comments enabled.' : 'Comments disabled.';
}

if (!$did) fail('Nothing to change.', 422);

ok(['id' => $id, 'message' => implode(' ', $did)]);
