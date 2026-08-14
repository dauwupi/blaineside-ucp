<?php
/**
 * GET /api/appeal-eligibility.php
 *
 * What this account may appeal, and whether they may appeal at all.
 *
 * Asked before the conditions page is drawn, so somebody with nothing to
 * appeal is told that instead of reading four screens of rules and then
 * being refused at the end.
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';

throttle('appeal_elig', 60);

$pdo = db();
/* A locked account must be able to ask this: the lock screen puts an appeal
   link on the one page they can reach, and it needs to know whether they
   already have one open before deciding what that link says. */
$acc = current_account_or_locked($pdo);

$e = appeal_eligibility($pdo, $acc);

ok([
    'authenticated' => true,
    'may'         => $e['may'],
    'why'         => $e['why'],
    'open'        => $e['open'],
    'cooldown'    => $e['cooldown'],
    'punishments' => $e['punishments'],
    'platforms'   => array_map(function ($k, $v) { return ['key' => $k, 'label' => $v]; },
                               array_keys(punish_platforms()), array_values(punish_platforms())),
    'features'    => ['characters' => false],
    'min_words'   => BS_APPEAL_BODY_MIN,
]);
