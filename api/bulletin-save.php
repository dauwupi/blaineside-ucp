<?php
/**
 * POST /api/bulletin-save.php
 * Body: { id?, type, title, body, link?, image?, imgpos? }
 *
 * Creates a bulletin, or updates the one identified by `id`.
 *
 * A new bulletin is never put on the dashboard by this endpoint, even if the
 * form thinks it should be: showing is a separate, explicit action with its
 * own limit (see bulletin-toggle.php). Editing an existing one leaves its
 * dashboard state exactly as it was.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_bulletins.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('bulletin_save', 30);

$pdo = db();
$acc = current_account($pdo);
require_bulletin_manager($acc);

$in    = read_input();
$id    = isset($in['id']) && $in['id'] !== null ? (int)$in['id'] : null;
$type  = strtolower(trim((string)($in['type'] ?? '')));
$title = trim((string)($in['title'] ?? ''));
$body  = trim((string)($in['body'] ?? ''));

if (!in_array($type, BS_BULLETIN_TYPES, true)) {
    json_out(['ok' => false, 'field' => 'type', 'error' => 'Pick a bulletin type.'], 400);
}
if ($title === '') {
    json_out(['ok' => false, 'field' => 'title', 'error' => 'Give it a title.'], 400);
}
if (mb_strlen($title) > 70) {
    json_out(['ok' => false, 'field' => 'title', 'error' => 'Titles are 70 characters at most.'], 400);
}
if ($body === '') {
    json_out(['ok' => false, 'field' => 'body', 'error' => 'Write a short description.'], 400);
}
if (mb_strlen($body) > 240) {
    json_out(['ok' => false, 'field' => 'body', 'error' => 'Descriptions are 240 characters at most.'], 400);
}

$link   = bulletin_check_link($in['link'] ?? null);
$image  = bulletin_check_image(isset($in['image']) ? (string)$in['image'] : null);
$imgpos = max(0, min(100, (int)($in['imgpos'] ?? 50)));

// The card thumbnail, made in the browser next to the banner. If the editor
// didn't send one — an older page, or a browser that couldn't — make it here
// so the listing still has something to draw.
$thumb = isset($in['thumb'])
    ? bulletin_check_image((string)$in['thumb'], BS_BULLETIN_MAX_THUMB)
    : ($image !== null ? bulletin_make_thumb($image) : null);

if ($id !== null) {
    $st = $pdo->prepare('SELECT id, image, thumb FROM ucp_bulletins WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) fail('That bulletin no longer exists.', 404);

    // The listing doesn't carry image data, so an editor opened from there
    // and saved without touching the picture must not wipe it. Only an
    // explicit null — the Remove button — clears it.
    $untouched = !array_key_exists('image', $in);
    $keepImage = $untouched ? (string)$existing['image'] : $image;
    $keepThumb = $untouched ? (string)$existing['thumb'] : $thumb;

    $pdo->prepare(
        'UPDATE ucp_bulletins
            SET type = ?, title = ?, body = ?, link = ?, image = ?, thumb = ?,
                image_pos = ?, updated_at = ?
          WHERE id = ?'
    )->execute([$type, $title, $body, $link, $keepImage, $keepThumb, $imgpos, time(), $id]);

    ok(['id' => $id, 'message' => 'Bulletin updated.']);
}

$pdo->prepare(
    'INSERT INTO ucp_bulletins
        (type, title, body, link, image, thumb, image_pos, on_dashboard, author_id, author_name, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
)->execute([$type, $title, $body, $link, $image, $thumb, $imgpos,
            (int)$acc['id'], (string)$acc['username'], time()]);

ok([
    'id'      => (int)$pdo->lastInsertId(),
    'message' => 'Bulletin published.',
]);
