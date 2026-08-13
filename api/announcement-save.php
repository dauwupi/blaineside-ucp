<?php
/**
 * POST /api/announcement-save.php
 * Body: { id?, type, lead, body?, link?, dismissable? }
 *
 * Writing an announcement does not put it up. Publishing is a separate,
 * deliberate act — see announcement-activate.php — so nobody takes the
 * dashboard over by pressing Save on a draft.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_announcements.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('announce_save', 30);

$pdo = db();
$acc = current_account($pdo);
require_bulletin_manager($acc);

$in   = read_input();
$id   = isset($in['id']) && $in['id'] !== null ? (int)$in['id'] : null;
$type = strtolower(trim((string)($in['type'] ?? '')));
$lead = trim((string)($in['lead'] ?? ''));
$body = trim((string)($in['body'] ?? ''));

if (!in_array($type, BS_ANNOUNCE_TYPES, true)) {
    json_out(['ok' => false, 'field' => 'type', 'error' => 'Pick an announcement type.'], 400);
}
if ($lead === '') {
    json_out(['ok' => false, 'field' => 'lead', 'error' => 'Write the headline.'], 400);
}
if (mb_strlen($lead) > 120) {
    json_out(['ok' => false, 'field' => 'lead', 'error' => 'Headlines are 120 characters at most.'], 400);
}
if (mb_strlen($body) > 240) {
    json_out(['ok' => false, 'field' => 'body', 'error' => 'The detail is 240 characters at most.'], 400);
}

$link        = bulletin_check_link($in['link'] ?? null);
$dismissable = array_key_exists('dismissable', $in) ? (!empty($in['dismissable'])) : true;

if ($id !== null) {
    $st = $pdo->prepare('SELECT id FROM ucp_announcements WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    if (!$st->fetch()) fail('That announcement no longer exists.', 404);

    $pdo->prepare(
        'UPDATE ucp_announcements
            SET type = ?, headline = ?, body = ?, link = ?, dismissable = ?, updated_at = ?
          WHERE id = ?'
    )->execute([$type, $lead, ($body === '' ? null : $body), $link, $dismissable ? 1 : 0, time(), $id]);

    ok(['id' => $id, 'message' => 'Announcement updated.']);
}

$pdo->prepare(
    'INSERT INTO ucp_announcements
        (type, headline, body, link, dismissable, active, author_id, author_name, created_at)
     VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)'
)->execute([$type, $lead, ($body === '' ? null : $body), $link, $dismissable ? 1 : 0,
            (int)$acc['id'], (string)$acc['username'], time()]);

ok(['id' => (int)$pdo->lastInsertId(), 'message' => 'Announcement saved.']);
