<?php
/**
 * GET /api/game-bulletins.php
 *
 * Server-to-server only: the FiveM server calls this to fetch the County
 * Bulletin shown on its login screen. Authenticated by the same shared
 * secret as game-verify.php, never by a session — there is no browser here
 * and no cookie to carry.
 *
 * Why this exists rather than the game calling bulletins.php: that endpoint
 * requires a signed-in account, because the website's dashboard set is read
 * by a logged-in player. The game server is not a player and has no session,
 * so it needs its own door. It also has no route to the database directly —
 * see the note in game-verify.php.
 *
 * Returns exactly the dashboard set: the same up-to-five rows, in the same
 * order, that the UCP dashboard rotates. One place decides what is on the
 * bulletin, so the website and the game cannot disagree about it.
 *
 * Deliberately: no CSRF (no session to ride on), no image payload by default
 * (a 1.2MB data URL per row would be pushed to every joining player — the
 * small thumbnail is enough for a card).
 */
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_bulletins.php';

// ---- Shared secret -------------------------------------------------------
$expected = (string)($CONFIG['game']['internal_secret'] ?? '');
$given    = (string)($_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '');

if ($expected === '' || !hash_equals($expected, $given)) {
    fail('Not authorised.', 401);
}

$pdo = db();

// Same query as bulletins.php?scope=dashboard - on_dashboard rows, newest
// first, capped at BS_BULLETIN_MAX_SHOWN.
$st = $pdo->prepare(
    'SELECT * FROM ucp_bulletins
      WHERE on_dashboard = 1
      ORDER BY created_at DESC, id DESC
      LIMIT ' . BS_BULLETIN_MAX_SHOWN
);
$st->execute();
$rows = $st->fetchAll();

// Rows written before thumbnails existed get one made now, once, and stored
// so this heals itself rather than needing a re-upload.
foreach ($rows as $i => $r) {
    if (empty($r['thumb']) && !empty($r['image'])) {
        $thumb = bulletin_make_thumb((string)$r['image']);
        if ($thumb !== null) {
            $pdo->prepare('UPDATE ucp_bulletins SET thumb = ? WHERE id = ?')
                ->execute([$thumb, (int)$r['id']]);
            $rows[$i]['thumb'] = $thumb;
        }
    }
}

/**
 * Shaped for the login page's card, not for the website.
 *
 * The page wants a short date chip, a headline, a one-line blurb and a type;
 * it does not want the raw body with its formatting, or the author, or the
 * unix timestamp. Trimming here keeps the payload small and means the game
 * client holds no formatting logic.
 */
$out = [];
foreach ($rows as $r) {
    $body = trim((string)$r['body']);
    // Collapse whitespace so a multi-paragraph post still gives one clean
    // line; the card clamps it to two lines visually.
    $body = preg_replace('/\s+/u', ' ', $body) ?? $body;

    $out[] = [
        'id'    => (int)$r['id'],
        'type'  => (string)$r['type'],        // event | update | notice
        'title' => (string)$r['title'],
        'body'  => $body,
        'link'  => $r['link'] !== null && $r['link'] !== '' ? (string)$r['link'] : null,
        // "Aug 23" - formatted here so every client shows the same thing
        // regardless of its own locale.
        'date'  => gmdate('M j', (int)$r['created_at']),
        'thumb' => !empty($r['thumb']) ? (string)$r['thumb'] : null,
    ];
}

ok(['bulletins' => $out]);
