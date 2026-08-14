<?php
/**
 * GET /api/appeal-handlers.php
 *
 * Everyone who can be given an appeal: Support Staff and above, still active.
 *
 * Exists so the handler control is a list rather than a text box. Typing a
 * name meant getting it exactly right, and getting it wrong meant a 404 in a
 * toast — for a field whose entire set of valid answers is small, known, and
 * already on the server.
 *
 * Senior Admin and above, matching who is allowed to reassign in the first
 * place: there is no reason to hand the staff roster to somebody who cannot
 * act on it.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';

throttle('appeal_handlers', 40);

$pdo = db();
$acc = current_account($pdo);

if (!appeal_may_manage($acc)) {
    json_out([
        'ok'    => false,
        'error' => 'Reassigning an appeal is for ' . rank_name(BS_APPEAL_MANAGE_RANK)
                 . ' and above.',
    ], 403);
}

ok(['authenticated' => true, 'handlers' => appeal_handlers($pdo)]);
