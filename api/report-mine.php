<?php
/**
 * GET /api/report-mine.php
 *
 * Every staff report this account has sent, newest first.
 *
 * No gate beyond being signed in, and no pagination: a person with more
 * than forty staff reports has a problem that a second page will not fix.
 *
 * Reports this person is the SUBJECT of never appear here. They are not
 * theirs, and rule 2 of _reports.php says they cannot read them — a row in
 * their own list saying "a report exists about you" would be the same leak
 * in a smaller box.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';

throttle('report_mine', 60);

$pdo = db();
$acc = current_account($pdo);

if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$st = $pdo->prepare(
    'SELECT id, title, status, category, outcome, created_at, updated_at, concluded_at
       FROM ucp_reports WHERE account_id = ? ORDER BY created_at DESC LIMIT 40'
);
$st->execute([(int)$acc['id']]);

$rows = array_map(function ($r) use ($pdo) {
    $subs = report_subjects($pdo, (int)$r['id']);
    return [
        'id'       => (int)$r['id'],
        'title'    => (string)$r['title'],
        'status'   => (string)$r['status'],
        'category' => report_category_label($r['category']),
        'outcome'  => report_outcome_label($r['outcome']),
        // The names, not the ids: this is the column the reporter scans to
        // find the one they mean.
        'staff'    => array_map(function ($s) { return $s['name']; }, $subs),
        'created'  => (int)$r['created_at'],
        'updated'  => (int)$r['updated_at'],
        'concluded'=> $r['concluded_at'] !== null ? (int)$r['concluded_at'] : null,
    ];
}, $st->fetchAll());

$e = report_eligibility($pdo, $acc);

ok(['authenticated' => true, 'reports' => $rows,
    'may' => $e['may'], 'why' => $e['why'], 'open' => $e['open']]);
