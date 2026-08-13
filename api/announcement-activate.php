<?php
/**
 * POST /api/announcement-activate.php
 * Body: { id, on }
 *
 * Puts one announcement up, or takes the current one down.
 *
 * "Only one at a time" is enforced by clearing every other row in the same
 * transaction as setting this one — not by asking the page to be careful.
 * Two managers publishing at the same moment end with exactly one live
 * announcement either way; the later one wins.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_announcements.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('announce_activate', 60);

$pdo = db();
$acc = current_account($pdo);
require_bulletin_manager($acc);

$in = read_input();
$id = (int)($in['id'] ?? 0);
$on = !empty($in['on']);

$st = $pdo->prepare('SELECT id FROM ucp_announcements WHERE id = ? LIMIT 1');
$st->execute([$id]);
if (!$st->fetch()) fail('That announcement no longer exists.', 404);

$pdo->beginTransaction();
try {
    if ($on) {
        $pdo->prepare('UPDATE ucp_announcements SET active = 0 WHERE active = 1')->execute();
        $pdo->prepare('UPDATE ucp_announcements SET active = 1, updated_at = ? WHERE id = ?')
            ->execute([time(), $id]);
    } else {
        $pdo->prepare('UPDATE ucp_announcements SET active = 0, updated_at = ? WHERE id = ?')
            ->execute([time(), $id]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

ok([
    'id'      => $id,
    'active'  => $on,
    'message' => $on ? 'Announcement is live on the dashboard.' : 'Announcement taken down.',
]);
