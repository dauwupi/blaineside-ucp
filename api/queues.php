<?php
/**
 * GET /api/queues.php
 *
 * What the signed-in account may open under Reports, Appeals & Refunds, and
 * which of those queues actually exist yet.
 *
 * The sidebar works this out for itself from the cached rank so the first
 * paint isn't blank — this is the answer that decides it. A page reached by
 * typing its URL asks here before drawing anything, so the gate does not
 * depend on the menu having hidden the link.
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_queues.php';

throttle('queues', 60);

$pdo = db();
$acc = current_account($pdo);

ok([
    'authenticated' => true,
    'rank'   => (int)$acc['admin_rank'],
    'role'   => rank_name((int)$acc['admin_rank']),
    'teams'  => array_map(function ($k) { return ['key' => $k, 'label' => team_label($k)]; },
                          teams_for($pdo, (int)$acc['id'])),
    'queues' => array_values(queues_for($pdo, $acc)),
]);
