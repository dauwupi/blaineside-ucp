<?php
/**
 * GET  /api/store-promo.php   the promotion currently running, if any
 * POST /api/store-promo.php   start, change or stop it — Founder only
 *
 * A promotion is one row. It applies to every pack, never to one, which is
 * why there is no pack column here: a discount that quietly applied to some
 * packs and not others would be impossible to explain on the page.
 *
 * Two kinds, and only two:
 *
 *   off    a percentage off the price. Packs cost less.
 *   bonus  a percentage of extra credits. Packs give more.
 *
 * Nothing here changes a purchase already made. The store writes the price
 * paid and the promotion applied onto the receipt at the time; this row is
 * what the shopfront reads, not a ledger.
 *
 * GET is open to anyone signed in — the offer is on a public page and the
 * prices already reflect it, so hiding the object it came from would only
 * make the two disagree.
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_store.php';

/** Only the Founder may touch this. Not Management — this is real money. */
const BS_PROMO_RANK = 9;

const BS_PROMO_KINDS = ['off', 'bonus'];

/** A promotion beyond this is almost certainly a typo, so it is refused. */
const BS_PROMO_MAX = 90;

function promo_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_credit_promo LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}

/**
 * The promotion the store should apply right now, or null.
 *
 * Expiry is decided here rather than by a cron: a row whose end time has
 * passed simply stops being returned. Nothing has to run on time for the
 * offer to stop, which is the failure mode that matters.
 */
function promo_current(PDO $pdo): ?array
{
    if (!promo_available($pdo)) return null;

    $st = $pdo->query(
        "SELECT id, name, kind, value, ends_at, active
           FROM ucp_credit_promo
          WHERE active = 1 AND ends_at > NOW()
       ORDER BY id DESC LIMIT 1"
    );
    $row = $st->fetch();
    if (!$row) return null;

    $ends = strtotime($row['ends_at'] . ' UTC');
    return [
        'id'    => (int)$row['id'],
        'name'  => (string)$row['name'],
        'kind'  => (string)$row['kind'],
        'value' => (int)$row['value'],
        'ends'  => $ends,
        'left'  => max(0, $ends - time()),
    ];
}

$pdo = db();
$acc = current_account($pdo);
$mayEdit = rank_at_least((int)$acc['admin_rank'], BS_PROMO_RANK);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    throttle('store_promo', 60);
    ok([
        'available' => promo_available($pdo),
        'may_edit'  => $mayEdit,
        'promo'     => promo_current($pdo),
    ]);
}

require_csrf();
throttle('store_promo_save', 12);

if (!$mayEdit) fail('Only the Founder can change a promotion.', 403);
if (!promo_available($pdo)) fail('The promotion table hasn\'t been created yet.', 400);

$in = read_input();

/* Stopping is its own action rather than a value you have to blank out. */
if (!empty($in['stop'])) {
    $pdo->exec('UPDATE ucp_credit_promo SET active = 0 WHERE active = 1');
    ok(['promo' => null]);
}

$name  = trim((string)($in['name'] ?? ''));
$kind  = (string)($in['kind'] ?? '');
$value = (int)($in['value'] ?? 0);
$ends  = trim((string)($in['ends'] ?? ''));

if (mb_strlen($name) < 3 || mb_strlen($name) > 60) {
    fail('Give the promotion a name between 3 and 60 characters.');
}
if (!in_array($kind, BS_PROMO_KINDS, true)) {
    fail('A promotion either discounts the price or adds bonus credits.');
}
if ($value < 1 || $value > BS_PROMO_MAX) {
    fail('The amount must be between 1% and ' . BS_PROMO_MAX . '%.');
}

$when = strtotime($ends . ' UTC');
if (!$when) fail('That end time could not be read. Use YYYY-MM-DD HH:MM.');
if ($when <= time()) fail('The end time has already passed.');

/* One at a time. Two overlapping offers would make the price on the page
   depend on which row was written last, which is not a thing to leave to
   chance where money is concerned. */
$pdo->exec('UPDATE ucp_credit_promo SET active = 0 WHERE active = 1');

$st = $pdo->prepare(
    'INSERT INTO ucp_credit_promo (name, kind, value, ends_at, active, created_by, created_at)
     VALUES (?, ?, ?, ?, 1, ?, NOW())'
);
$st->execute([$name, $kind, $value, gmdate('Y-m-d H:i:s', $when), (int)$acc['id']]);

ok(['promo' => promo_current($pdo)]);
