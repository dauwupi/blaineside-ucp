<?php
/**
 * POST /api/appeal-submit.php
 * Body: { platforms: ["game","discord"], body: "...", evidence: [{url, note}] }
 *
 * Submits an appeal. Every rule in _appeals.php is checked again here, not
 * because the page didn't — because the page can be edited by anyone with
 * developer tools open, and a locked-out player is exactly the person with
 * a motive to try.
 *
 * An appeal cannot be edited after it is sent. That is stated on the form,
 * and it is why the validation below refuses rather than trims: somebody
 * who is about to be unable to change a word should be told what is wrong
 * with it while they still can.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('appeal_submit', 6);

$pdo = db();
$acc = current_account_or_locked($pdo);

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}

$e = appeal_eligibility($pdo, $acc);
if (!$e['may']) {
    json_out(['ok' => false, 'error' => $e['why'] ?: 'You can\'t appeal right now.',
              'open' => $e['open']], 403);
}

$in        = read_input();
$platforms = appeal_platforms_in($in['platforms'] ?? []);
$body      = trim((string)($in['body'] ?? ''));
$evidence  = is_array($in['evidence'] ?? null) ? $in['evidence'] : [];

if (!$platforms) {
    fail('Tick where you were banned — Game, Discord or Forums.', 422);
}
if (mb_strlen($body) < BS_APPEAL_BODY_MIN) {
    fail('Explain what happened in your own words. A line or two isn\'t enough for anyone to '
       . 'act on.', 422);
}
if (mb_strlen($body) > BS_APPEAL_BODY_MAX) {
    fail('That is longer than an appeal can be. Keep it under '
       . number_format(BS_APPEAL_BODY_MAX) . ' characters.', 422);
}

/* Which punishments this is against — plural, and possibly none.
 *
 * The three platforms are not equivalent, because the UCP only knows about
 * one of them:
 *
 *   Game     the UCP records in-game bans and user locks, so it can check.
 *            An appeal here MUST match something in force, or the handler is
 *            deciding an appeal against a ban nobody can find.
 *   Forums   the ban lives on the forum software.
 *   Discord  the ban lives in Discord.
 *
 * For the last two there is nothing here to check against, and refusing the
 * appeal because the UCP has no row told a genuinely banned player that
 * nothing was wrong. Those go through with no punishment attached: the
 * appeal itself is the record, and the handler confirms it where it lives.
 *
 * Somebody banned in game AND on the forums still writes ONE appeal and
 * ticks two boxes — every matching in-force punishment is attached.
 */
$active   = punish_active_for($pdo, (int)$acc['id']);
$matched  = [];
$gameSeen = false;                     // any in-game punishment at all, appealable or not
foreach ($active as $p) {
    if (punish_platform_of((string)$p['kind']) === 'game') $gameSeen = true;
    if (empty($p['appealable'])) continue;
    if (!in_array(punish_platform_of((string)$p['kind']), $platforms, true)) continue;
    $matched[] = $p;
}
usort($matched, function ($a, $b) { return (int)$a['issued_at'] <=> (int)$b['issued_at']; });

if (in_array('game', $platforms, true)) {
    $gameMatch = false;
    foreach ($matched as $p) {
        if (punish_platform_of((string)$p['kind']) === 'game') { $gameMatch = true; break; }
    }
    if (!$gameMatch) {
        /* Two different failures, and telling them apart is the difference
         * between "you ticked the wrong box" and "this is never open to
         * appeal". */
        fail($gameSeen
            ? 'The in-game punishment on your account was issued for an egregious violation and '
            . 'is not open to appeal.'
            : 'There is no in-game ban or user lock on your account. If you were banned on the '
            . 'forums or on Discord, tick those instead.', 422);
    }
}

// The oldest match goes on the appeal row itself; null when nothing matched.
$match = $matched ? $matched[0] : null;

/* Assigned on arrival to whoever issued it.
 *
 * An appeal that lands in a pile marked "not assigned" waits for somebody to
 * volunteer. Giving it to the administrator who issued the punishment gives
 * it an owner from the first second, and that administrator is the one who
 * already knows what happened. A Senior Admin reassigns it if a second pair
 * of eyes is wanted — see BS_APPEAL_MANAGE_RANK.
 *
 * Only if they are still staff and still active. An appeal handed to an
 * account that can't open it is worse than an unassigned one, because it
 * looks handled. */
$handlerId = null; $handlerName = null;
if ($match && !empty($match['issued_by'])) {
    $h = $pdo->prepare(
        'SELECT id, username, admin_rank FROM ucp_accounts
          WHERE id = ? AND status = \'active\' LIMIT 1'
    );
    $h->execute([(int)$match['issued_by']]);
    $row = $h->fetch();
    if ($row && (int)$row['admin_rank'] >= BS_APPEAL_STAFF_RANK
             && (int)$row['id'] !== (int)$acc['id']) {
        $handlerId   = (int)$row['id'];
        $handlerName = (string)$row['username'];
    }
}

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_appeals
        (account_id, punishment_id, platforms, character_id, body, status,
         handler_id, handler_name, comments_enabled, created_at, updated_at)
     VALUES (?, ?, ?, NULL, ?, \'pending\', ?, ?, 1, ?, ?)'
)->execute([(int)$acc['id'], $match ? (int)$match['id'] : null,
            implode(',', $platforms), $body,
            $handlerId, $handlerName, $now, $now]);

$id = (int)$pdo->lastInsertId();

/* The full set. ucp_appeals.punishment_id holds the first for anything that
   wants a single id; this is what the appeal is actually against. */
foreach ($matched as $p) {
    $pdo->prepare('INSERT INTO ucp_appeal_punishments (appeal_id, punishment_id) VALUES (?, ?)')
        ->execute([$id, (int)$p['id']]);
}

/* Evidence. Links only — the UCP has no file store. Anything that isn't a
   plausible http(s) URL is dropped rather than refusing the whole appeal:
   losing one bad link is a smaller harm than losing the appeal. */
$kept = 0;
foreach ($evidence as $ev) {
    if ($kept >= BS_APPEAL_EVIDENCE_MAX) break;
    $url  = trim((string)($ev['url'] ?? ''));
    $note = trim((string)($ev['note'] ?? ''));
    if ($url === '') continue;
    if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
    if (!filter_var($url, FILTER_VALIDATE_URL)) continue;
    if (mb_strlen($url) > 500) continue;

    $pdo->prepare(
        'INSERT INTO ucp_appeal_evidence (appeal_id, url, note, created_at) VALUES (?, ?, ?, ?)'
    )->execute([$id, $url, $note !== '' ? mb_substr($note, 0, 190) : null, $now]);
    $kept++;
}

if ($handlerName !== null) {
    appeal_log_add($pdo, $id, $acc, 'handler',
        'Assigned to ' . $handlerName . ', who issued the punishment.');
}

appeal_log_add($pdo, $id, $acc, 'submitted',
    'Appeal submitted for ' . ($matched
        ? implode(' and ', array_map(function ($p) {
              return punish_kind_label((string)$p['kind']);
          }, $matched))
        : implode(' and ', array_map(function ($k) {
              $l = punish_platforms(); return $l[$k] ?? $k;
          }, $platforms)) . ' — nothing recorded in the UCP, so nothing is attached'));

/* Tell the handler it landed.
 *
 * An appeal assigned to the administrator who issued the punishment is only
 * useful if that administrator finds out — otherwise "assigned on arrival"
 * means it sits with one named person and nobody looks at it. */
if ($handlerId !== null) {
    notify($pdo, $handlerId, 'appeal', 'submitted',
        'New ban appeal from ' . $acc['username'],
        ['body'  => $match ? 'Against the ' . punish_kind_label((string)$match['kind'])
                             . ' you issued.'
                           : 'Nothing is recorded in the UCP for it.',
         'url'   => '/dashboard/appeals?id=' . $id,
         'actor_name' => (string)$acc['username'],
         'actor_id'   => (int)$acc['id'],
         'dedupe'     => 'appeal:' . $id . ':submitted']);
}

ok([
    'id'      => $id,
    'message' => 'Your appeal has been sent. You will be able to follow it from this page.',
]);
