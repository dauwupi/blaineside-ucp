<?php
/**
 * GET /api/report-options.php
 *
 * Everything the report form needs before it can be drawn: whether this
 * person may send one, and the list of staff they can name.
 *
 * One call rather than three. The form cannot usefully render without any
 * of it — a staff picker with no names is a text box, and a form that
 * doesn't yet know the person has hit the open-reports cap invites them to
 * write eight hundred words and then refuses.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';

throttle('report_options', 40);

$pdo = db();
$acc = current_account($pdo);

$e = report_eligibility($pdo, $acc);

ok([
    'authenticated' => true,
    'available'   => reports_available($pdo),
    'may'         => $e['may'],
    'why'         => $e['why'],
    'open'        => $e['open'],
    'open_max'    => BS_REPORT_OPEN_MAX,
    /* Everyone from Support Staff to Founder. Nobody is un-reportable —
       a Founder appears in this list like anyone else, and so does the
       person asking: submitting is open to all, and it is READING a report
       that the conflict rules govern. */
    'staff'       => reports_available($pdo) ? report_staff_options($pdo) : [],
    'channels'    => array_map(function ($k, $l) { return ['key' => $k, 'label' => $l]; },
                     array_keys(report_channels()), array_values(report_channels())),
    'frequencies' => array_map(function ($k, $l) { return ['key' => $k, 'label' => $l]; },
                     array_keys(report_frequencies()), array_values(report_frequencies())),
    'limits'      => [
        'title_max'    => BS_REPORT_TITLE_MAX,
        'body_min'     => BS_REPORT_BODY_MIN,
        'body_max'     => BS_REPORT_BODY_MAX,
        'want_max'     => BS_REPORT_WANT_MAX,
        'evidence_max' => BS_REPORT_EVIDENCE_MAX,
        'staff_max'    => BS_REPORT_STAFF_MAX,
    ],
]);
