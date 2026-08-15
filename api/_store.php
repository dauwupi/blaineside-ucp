<?php
/**
 * Credit Store — the shared rules.
 *
 * Only Purchase Support is wired to anything. The credit tiers, the shop
 * and the history are a designed shopfront with no payment provider behind
 * them, and the page says so rather than pretending: a Buy button that
 * silently does nothing is worse than one that admits it.
 *
 * Support tickets ARE real, and they have one rule worth stating plainly:
 * a ticket is private to the player who opened it and to Management. Not
 * Support Staff, not Administrators — money questions are Management's,
 * and every query in here is written so there is no way to widen that by
 * accident.
 */

/** Rank that can read and answer everybody's tickets. */
const BS_STORE_STAFF_RANK = 8;

const STORE_TICKET_STATUSES = ['open', 'answered', 'closed'];

/** How many tickets a page of the list holds. */
const BS_STORE_PER_PAGE = 10;

/** A subject shorter than this is not a subject. */
const BS_STORE_SUBJECT_MIN = 6;
const BS_STORE_BODY_MIN    = 20;

/** Open tickets one account may hold at once, so nobody floods the queue. */
const BS_STORE_OPEN_MAX = 5;


function store_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_store_tickets LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}

function store_missing_reason(): string
{
    return 'Purchase support isn\'t switched on yet.';
}

/** Management and Founders. */
function store_is_staff(array $acc): bool
{
    return (int)$acc['admin_rank'] >= BS_STORE_STAFF_RANK;
}

/**
 * May this account read this ticket?
 *
 * The author, or Management. Deliberately not "anyone who can see the
 * store" — a ticket routinely contains an email address, a payment
 * reference and somebody's frustration, and none of that is staff-wide.
 */
function store_may_read(array $acc, array $ticket): bool
{
    return (int)$ticket['account_id'] === (int)$acc['id'] || store_is_staff($acc);
}

function store_ticket_out(array $t, ?array $acc = null): array
{
    return [
        'id'        => (int)$t['id'],
        'subject'   => $t['subject'],
        'order_ref' => $t['order_ref'],
        'status'    => $t['status'],
        'replies'   => (int)$t['replies'],
        'last'      => $t['last_reply_at'] !== null ? [
            'at'    => (int)$t['last_reply_at'],
            'by'    => $t['last_reply_by'],
            'staff' => (bool)$t['last_reply_staff'],
        ] : null,
        'closed'    => $t['closed_at'] !== null ? [
            'at' => (int)$t['closed_at'], 'by' => $t['closed_by_name'],
        ] : null,
        'created_at' => (int)$t['created_at'],
        'updated_at' => (int)$t['updated_at'],
        'mine'       => $acc ? (int)$t['account_id'] === (int)$acc['id'] : null,
        'player'     => isset($t['username'])
            ? ['id' => (int)$t['account_id'], 'name' => $t['username']] : null,
    ];
}

function store_messages(PDO $pdo, int $ticketId): array
{
    $st = $pdo->prepare(
        'SELECT author_id, author_name, author_rank, author_is_staff, body, created_at
           FROM ucp_store_ticket_messages WHERE ticket_id = ? ORDER BY id'
    );
    $st->execute([$ticketId]);
    $out = [];
    foreach ($st->fetchAll() as $m) {
        $out[] = [
            'author' => $m['author_name'],
            'id'     => $m['author_id'] !== null ? (int)$m['author_id'] : null,
            'staff'  => (bool)$m['author_is_staff'],
            'role'   => (bool)$m['author_is_staff'] ? rank_name((int)$m['author_rank']) : null,
            'body'   => $m['body'],
            'at'     => (int)$m['created_at'],
        ];
    }
    return $out;
}

/**
 * Add a message and move the ticket's own state with it.
 *
 * The status is derived from WHO replied rather than set by hand: a staff
 * reply answers a ticket, a player reply reopens it. Nobody has to
 * remember to change a dropdown, and the list can be trusted.
 */
function store_add_message(PDO $pdo, int $ticketId, array $acc, string $body, bool $staff): void
{
    $now = time();
    $pdo->prepare(
        'INSERT INTO ucp_store_ticket_messages
           (ticket_id, author_id, author_name, author_rank, author_is_staff, body, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $ticketId, (int)$acc['id'], $acc['username'], (int)$acc['admin_rank'],
        $staff ? 1 : 0, $body, $now,
    ]);

    $pdo->prepare(
        'UPDATE ucp_store_tickets
            SET replies = replies + 1, last_reply_at = ?, last_reply_by = ?,
                last_reply_staff = ?, status = ?, updated_at = ?
          WHERE id = ? AND status <> ?'
    )->execute([
        $now, $acc['username'], $staff ? 1 : 0,
        $staff ? 'answered' : 'open', $now, $ticketId, 'closed',
    ]);
}
