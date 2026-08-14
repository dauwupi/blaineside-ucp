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

/* Which punishments this is against — plural.
 *
 * Somebody banned in game AND on the forums writes ONE appeal and ticks two
 * boxes. Making them write the same account of the same evening twice, and
 * wait for two verdicts on it, would be absurd; so every in-force,
 * appealable punishment on a ticked platform is attached.
 *
 * Chosen by the server rather than the player: they tick a platform, and it
 * matches rows that are actually in force. A ticked platform with nothing
 * on file is NOT an error — a player may well believe they were also banned
 * on Discord — so it is recorded in `platforms` and shown to the handler as
 * a claim with nothing behind it. Only ticking nothing that matches at all
 * is refused, because then there is no punishment to appeal. */
$active  = punish_active_for($pdo, (int)$acc['id']);
$matched = [];
foreach ($active as $p) {
    if (empty($p['appealable'])) continue;
    if (!in_array(punish_platform_of((string)$p['kind']), $platforms, true)) continue;
    $matched[] = $p;
}
usort($matched, function ($a, $b) { return (int)$a['issued_at'] <=> (int)$b['issued_at']; });

if (!$matched) {
    fail('Nothing on your account matches where you say you were banned. Tick the platform the '
       . 'punishment is actually on.', 422);
}
$match = $matched[0];   // the oldest, kept on the appeal row itself

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_appeals
        (account_id, punishment_id, platforms, character_id, body, status,
         comments_enabled, created_at, updated_at)
     VALUES (?, ?, ?, NULL, ?, \'pending\', 1, ?, ?)'
)->execute([(int)$acc['id'], (int)$match['id'], implode(',', $platforms), $body, $now, $now]);

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

appeal_log_add($pdo, $id, $acc, 'submitted',
    'Appeal submitted for ' . implode(' and ', array_map(function ($p) {
        return punish_kind_label((string)$p['kind']);
    }, $matched)));

ok([
    'id'      => $id,
    'message' => 'Your appeal has been sent. You will be able to follow it from this page.',
]);
