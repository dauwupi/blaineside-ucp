<?php
/**
 * GET /api/application-mine.php
 *
 * Everything the player's own Application page needs, and everything the
 * dashboard notice needs, in one call: which of the five states they are
 * in, the attempt they are working on or waiting on, and every attempt
 * they have already had decided — with the feedback attached.
 *
 * It never creates anything. Starting a draft is a POST the player makes
 * on purpose, because drawing the random questions is a one-time event and
 * a GET that did it would re-roll them on every visit.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

throttle('application_mine', 90);

$pdo = db();
$acc = current_account($pdo);

if (!applications_available($pdo)) {
    json_out(['ok' => true, 'available' => false,
              'why' => applications_missing_reason(), 'state' => 'none'], 200);
}

$me = (int)$acc['id'];

/* Recording the address here rather than in login.php is deliberate: it
   costs one upsert on a page the signed-in player loads anyway, and it
   also catches sessions that were opened before this feature existed. */
app_touch_ip($pdo, $me, client_ip());

$s   = app_state($pdo, $me);
$cur = $s['application'];

$out = [
    'available' => true,
    'state'     => $s['state'],
    'attempts'  => $s['attempts'],
    'current'   => null,
    'answers'   => [],
    /* The attempt in `current` is left out of `history` ONLY while it is
       still theirs to write or still waiting. A DECIDED attempt appears in
       both, because the page shows it as the latest outcome and as a row in
       the list, and a list that silently omits its newest entry is worse
       than one duplicate. */
    'history'   => app_history($pdo, $me,
        ($cur && in_array($cur['status'], ['draft', 'pending'], true)) ? (int)$cur['id'] : null),
    /* The queue as it stands, for the figures on the page. Null when they
       cannot be counted, and the page then shows no figures at all. */
    'queue'     => app_queue_stats($pdo),
];

if ($cur) {
    $out['current'] = app_row_out($cur);
    /* The player is shown the questions only while the attempt is theirs to
       write. Once it is decided it lives in `history`, which carries the
       feedback — the part they actually need. */
    if (in_array($cur['status'], ['draft', 'pending'], true)) {
        $out['answers'] = app_answers($pdo, (int)$cur['id']);
    }
}

ok($out);
