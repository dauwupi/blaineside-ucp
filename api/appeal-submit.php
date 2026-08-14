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

/* Which punishment this is against.
 *
 * Chosen here rather than by the player: they tick a platform, and the
 * server matches it to a row that is actually in force. If they tick two,
 * the appeal attaches to the oldest still-active punishment among them —
 * the one that has been standing longest is the one being appealed, and
 * the others are visible on the record anyway. */
$active = punish_active_for($pdo, (int)$acc['id']);
$match  = null;
foreach ($active as $p) {
    if (empty($p['appealable'])) continue;
    if (!in_array(punish_platform_of((string)$p['kind']), $platforms, true)) continue;
    if ($match === null || (int)$p['issued_at'] < (int)$match['issued_at']) $match = $p;
}
if ($match === null) {
    /* They ticked a platform they aren't punished on. Not a silent
       fallback: it is usually a misread of the question, and attaching the
       appeal to the wrong punishment wastes the handler's time. */
    fail('Nothing on your account matches where you say you were banned. Tick the platform the '
       . 'punishment is actually on.', 422);
}

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_appeals
        (account_id, punishment_id, platforms, character_id, body, status,
         comments_enabled, created_at, updated_at)
     VALUES (?, ?, ?, NULL, ?, \'pending\', 1, ?, ?)'
)->execute([(int)$acc['id'], (int)$match['id'], implode(',', $platforms), $body, $now, $now]);

$id = (int)$pdo->lastInsertId();

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

appeal_log_add($pdo, $id, $acc, 'submitted',
    'Appeal submitted for ' . punish_kind_label((string)$match['kind']));

ok([
    'id'      => $id,
    'message' => 'Your appeal has been sent. You will be able to follow it from this page.',
]);
