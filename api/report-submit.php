<?php
/**
 * POST /api/report-submit.php
 * Body: { staff:[id,...], unknown, unknown_note, channel, incident_at,
 *         frequency, witnesses, body, outcome_wanted, evidence:[{url,note}] }
 *
 * There is no title in that list on purpose — it is built from the names,
 * here, by report_title_for(). See the note on that function.
 *
 * Sends a staff report.
 *
 * Every rule in _reports.php is checked again here, not because the page
 * didn't — because the page can be edited by anyone with developer tools
 * open, and the person most motivated to try is the one about to be
 * reported.
 *
 * A report cannot be edited after it is sent. That is stated on the form,
 * and it is why the validation below refuses rather than trims: somebody
 * about to be unable to change a word should be told what is wrong with it
 * while they still can.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('report_submit', 6);

$pdo = db();
$acc = current_account($pdo);

if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$e = report_eligibility($pdo, $acc);
if (!$e['may']) {
    json_out(['ok' => false, 'error' => $e['why'], 'open' => $e['open']], 403);
}

$in       = read_input();
$unknown  = !empty($in['unknown']);
$unote    = trim((string)($in['unknown_note'] ?? ''));
$channel  = strtolower(trim((string)($in['channel'] ?? '')));
$freq     = strtolower(trim((string)($in['frequency'] ?? '')));
$witness  = trim((string)($in['witnesses'] ?? ''));
$body     = trim((string)($in['body'] ?? ''));
$want     = trim((string)($in['outcome_wanted'] ?? ''));
$evidence = is_array($in['evidence'] ?? null) ? $in['evidence'] : [];
$wantIds  = is_array($in['staff'] ?? null) ? $in['staff'] : [];

/* ---- the words ---- */
if (!isset(report_channels()[$channel]))    fail('Say where this happened.', 422);
if (!isset(report_frequencies()[$freq]))    fail('Say whether this was a one-off or is ongoing.', 422);

if (mb_strlen($body) < BS_REPORT_BODY_MIN) {
    fail('Explain what happened in your own words. A line or two isn\'t enough for anyone '
       . 'to act on.', 422);
}
if (mb_strlen($body) > BS_REPORT_BODY_MAX) {
    fail('That is longer than a report can be. Keep it under '
       . number_format(BS_REPORT_BODY_MAX) . ' characters.', 422);
}
if ($want === '') {
    fail('Say what you want to happen. A report with no asked-for outcome is a complaint, '
       . 'and Staff Management have to guess what would settle it.', 422);
}
if (mb_strlen($want) > BS_REPORT_WANT_MAX) {
    fail('Keep the outcome you want under ' . number_format(BS_REPORT_WANT_MAX)
       . ' characters.', 422);
}

/* ---- who it is about ----
 *
 * Resolved against the roster rather than taken as given. An id that isn't
 * staff, isn't active, or is the person filing is dropped — and if nothing
 * survives, the report is refused rather than filed against nobody, which
 * is a report no one can be allocated and no one can answer.
 */
$roster = [];
foreach (report_staff_options($pdo) as $s) $roster[$s['id']] = $s;

$named = [];
foreach ($wantIds as $raw) {
    $sid = (int)$raw;
    if (!isset($roster[$sid]) || isset($named[$sid])) continue;
    if (count($named) >= BS_REPORT_STAFF_MAX) break;
    $named[$sid] = $roster[$sid];
}
/* Nobody named is allowed, but only when it is said out loud.
 *
 * "I don't know who it was" is a real and common situation — an unnamed
 * administrator in a crowded scene, a Discord moderator whose handle
 * nobody caught. Refusing those reports loses exactly the ones where the
 * reporter had least power. But a report against nobody, with nothing to
 * go on, cannot be worked either, so the price of the option is saying how
 * Staff Management might establish who it was. */
if (!$named && !$unknown) {
    fail('Name at least one member of staff. If you don\'t know who it was, tick the unknown '
       . 'option instead and tell us what you do know.', 422);
}
if ($unknown && mb_strlen($unote) < BS_REPORT_UNKNOWN_MIN) {
    fail('Tell us how we might work out who it was — roughly when, where, what they were doing, '
       . 'anything anyone said. Without something to go on there is nothing to look into.', 422);
}
if (mb_strlen($unote) > BS_REPORT_BODY_MAX) {
    fail('That is too long. Put the detail in the report itself.', 422);
}
if (!$unknown) $unote = '';

/* Built, not typed — see report_title_for(). */
$title = report_title_for(array_map(function ($s) { return $s['name']; }, $named), $unknown);

/* ---- when ----
 *
 * A time in the future is a typo every time, and a report timestamped next
 * Tuesday is one a handler cannot line up against any log. A one-off needs
 * a time; a continuous pattern does not, and demanding one produces a
 * fiction.
 */
$at = isset($in['incident_at']) && $in['incident_at'] !== '' ? (int)$in['incident_at'] : null;
if ($at !== null && $at > time() + 300) {
    fail('That date is in the future. Server time is UTC — check the clock at the top of '
       . 'the page.', 422);
}
if ($at !== null && $at < time() - (86400 * 365 * 3)) $at = null;
if ($at === null && $freq === 'once') {
    fail('Give the date and time of the incident, in server time.', 422);
}

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_reports
        (account_id, title, channel, incident_at, frequency, witnesses, body,
         outcome_wanted, unknown, unknown_note, status, comments_enabled, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\', 1, ?, ?)'
)->execute([(int)$acc['id'], $title, $channel, $at, $freq,
            $witness !== '' ? mb_substr($witness, 0, 255) : null,
            $body, $want, $unknown ? 1 : 0, $unote !== '' ? $unote : null, $now, $now]);

$id = (int)$pdo->lastInsertId();

foreach ($named as $s) {
    $pdo->prepare('INSERT INTO ucp_report_staff (report_id, account_id, name) VALUES (?, ?, ?)')
        ->execute([$id, (int)$s['id'], (string)$s['name']]);
}

/* Evidence. Links only — the UCP has no file store. Anything that isn't a
   plausible http(s) URL is dropped rather than refusing the whole report:
   losing one bad link is a smaller harm than losing the report. */
$kept = 0;
foreach ($evidence as $ev) {
    if ($kept >= BS_REPORT_EVIDENCE_MAX) break;
    $url  = trim((string)($ev['url'] ?? ''));
    $note = trim((string)($ev['note'] ?? ''));
    if ($url === '') continue;
    if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
    if (!filter_var($url, FILTER_VALIDATE_URL)) continue;
    if (mb_strlen($url) > 500) continue;

    $pdo->prepare(
        'INSERT INTO ucp_report_evidence (report_id, url, note, created_at) VALUES (?, ?, ?, ?)'
    )->execute([$id, $url, $note !== '' ? mb_substr($note, 0, 190) : null, $now]);
    $kept++;
}

report_log_add($pdo, $id, $acc, 'submitted',
    'Report submitted against ' . ($named
        ? implode(', ', array_map(function ($s) { return $s['name']; }, $named))
          . ($unknown ? ' and an unknown staff member' : '')
        : 'an unknown staff member') . '.');

/* Staff Management hear about it, minus anybody the report names.
 *
 * A report arrives unallocated, so there is no one person to tell — and an
 * unallocated report that nobody is told about is the exact failure the
 * 24-48 hour promise on the form is meant to prevent. The subjects are cut
 * from the list here as well as from the queue: a notification saying a
 * report exists is most of what the report says. */
$subjects = array_keys($named);
foreach (report_handlers($pdo) as $h) {
    if (in_array((int)$h['id'], $subjects, true)) continue;
    notify($pdo, (int)$h['id'], 'report', 'submitted',
        'New staff report — ' . $title,
        ['body' => 'Waiting to be triaged and allocated.',
         'url'  => '/dashboard/reports?id=' . $id,
         'actor_id' => (int)$acc['id'],
         'dedupe'   => 'report:' . $id . ':submitted']);
}

ok([
    'id'      => $id,
    'message' => 'Your report has been sent to Staff Management. You can follow it from this '
               . 'page.',
]);
