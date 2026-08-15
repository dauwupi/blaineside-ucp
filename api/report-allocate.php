<?php
/**
 * POST /api/report-allocate.php
 * Body: { id, handler: "name"|"", category?: "misconduct", comment?: "...",
 *         staff_only?: bool, comments_enabled?: bool }
 *
 * Allocation and triage. Everything that happens to a report BEFORE it is
 * concluded is here, because in practice they happen together: somebody
 * reads the report, decides what kind of report it is, gives it to a
 * person, and writes the line the reporter has been waiting for.
 *
 * Every field is optional and only what is sent is changed, so the page can
 * offer them as three separate controls without three endpoints that each
 * have to re-derive who may act.
 *
 * The opening comment is the reason this isn't just an UPDATE. A report
 * that is allocated in silence looks, to the person who sent it, exactly
 * like a report nobody has read.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('report_allocate', 30);

$pdo = db();
$acc = current_account($pdo);

if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$in = read_input();
$id = (int)($in['id'] ?? 0);

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) fail('There is no staff report with that number.', 404);

if (!report_may_panel($pdo, $acc)) {
    json_out(['ok' => false, 'error' => report_panel_reason()], 403);
}
/* A Staff Management holder cannot act on a report naming them; Management
   and Founders can, and the page warns them rather than stopping them. */
if (report_subject_blind($pdo, $acc) && report_is_subject($pdo, $id, (int)$acc['id'])) {
    json_out(['ok' => false, 'error' => 'This report names you, and the Staff Report Panel is '
            . 'held through a sub-group. Management will handle it.'], 403);
}
if ((string)$r['status'] !== 'pending') {
    fail('This report has been concluded. Nothing about it can be reassigned or re-triaged.',
         409);
}

$now     = time();
$changes = [];
$sets    = [];
$args    = [];

/* ---- category (the 24-48 hour triage) ---- */
if (array_key_exists('category', $in)) {
    $cat = trim((string)$in['category']);
    if ($cat !== '' && !isset(report_categories()[$cat])) {
        fail('That is not one of the report categories.', 422);
    }
    $old = (string)($r['category'] ?? '');
    if ($cat !== $old) {
        $sets[] = 'category = ?';
        $args[] = $cat !== '' ? $cat : null;
        $changes[] = $cat === ''
            ? 'Category cleared.'
            : 'Categorised as ' . report_category_label($cat) . '.';
    }
}

/* ---- handler ----
 *
 * Sent as a name, not an id, because the control is a list of names and a
 * name is what a person can check. Resolved against report_handlers() —
 * the set of people who can actually open the panel — so a report is never
 * handed to somebody who will be refused at the door. Anyone with panel
 * access may allocate, including to themselves.
 */
if (array_key_exists('handler', $in)) {
    $want = trim((string)$in['handler']);
    $hid = null; $hname = null;

    if ($want !== '') {
        foreach (report_handlers($pdo) as $h) {
            if (strcasecmp($h['name'], $want) === 0) { $hid = $h['id']; $hname = $h['name']; break; }
        }
        if ($hid === null) {
            fail('That person can\'t be given a staff report. Allocation is limited to '
               . 'Management, Founders and Staff Management.', 422);
        }
        if (report_is_subject($pdo, $id, $hid)) {
            fail('This report names ' . $hname . '. It cannot be allocated to them.', 422);
        }
    }

    if ((int)($r['handler_id'] ?? 0) !== (int)$hid) {
        $sets[] = 'handler_id = ?';   $args[] = $hid;
        $sets[] = 'handler_name = ?'; $args[] = $hname;
        // First allocation stamps the time; later reassignments don't, so
        // the opening comment stays the one written when it was picked up.
        if ($hid !== null && $r['allocated_at'] === null) {
            $sets[] = 'allocated_at = ?'; $args[] = $now;
        }
        $changes[] = $hname === null
            ? 'Allocation removed.'
            : 'Allocated to ' . $hname . '.';
    }
}

/* ---- comments open or closed to the reporter ---- */
if (array_key_exists('comments_enabled', $in)) {
    $on = !empty($in['comments_enabled']);
    if ($on !== !empty($r['comments_enabled'])) {
        $sets[] = 'comments_enabled = ?'; $args[] = $on ? 1 : 0;
        $changes[] = $on ? 'Comments opened to the reporter.'
                         : 'Comments closed to the reporter.';
    }
}

/* ---- the opening comment ---- */
$comment   = trim((string)($in['comment'] ?? ''));
$staffOnly = !empty($in['staff_only']);
if ($comment !== '' && mb_strlen($comment) > BS_REPORT_COMMENT_MAX) {
    fail('That comment is too long. Keep it under '
       . number_format(BS_REPORT_COMMENT_MAX) . ' characters.', 422);
}

if (!$sets && $comment === '') {
    fail('Nothing to change.', 422);
}

if ($sets) {
    $sets[] = 'updated_at = ?'; $args[] = $now;
    $args[] = $id;
    $pdo->prepare('UPDATE ucp_reports SET ' . implode(', ', $sets) . ' WHERE id = ?')
        ->execute($args);
}

if ($comment !== '') {
    /* Written at $now, which is also allocated_at when this call is what
       allocated the report — that match is how report_comments() marks it
       as the opening comment. On a report that was already allocated the
       two differ, so the same words are an ordinary staff reply and read
       as one. */
    $at = $now;
    $pdo->prepare(
        'INSERT INTO ucp_report_comments
            (report_id, author_id, author_name, author_is_staff, staff_only, body, created_at)
         VALUES (?, ?, ?, 1, ?, ?, ?)'
    )->execute([$id, (int)$acc['id'], (string)$acc['username'], $staffOnly ? 1 : 0,
                $comment, $at]);
    $changes[] = $staffOnly ? 'Left a staff-only note.' : 'Wrote to the reporter.';
}

report_log_add($pdo, $id, $acc, 'handled', implode(' ', $changes));

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);

ok(['message' => 'Saved.', 'report' => report_out($pdo, $st->fetch(), $acc)]);
