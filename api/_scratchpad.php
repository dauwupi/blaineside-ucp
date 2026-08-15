<?php
/**
 * BlaineSide UCP — the Scratchpad.
 *
 * Staff notes on a player's account. Everything that is true about somebody
 * but is not a punishment: a word had in voice instead of a warning, a
 * pattern one administrator noticed, another name the same person plays
 * under, why a refund was declined.
 *
 * Three rules, and they are the whole design:
 *
 *   The player never sees it. Not on their own profile, not in an export,
 *   not in an appeal. api/profile.php does not read this file at all — the
 *   notes are only ever assembled in api/admin-account.php, which is behind
 *   the Administrative Search gate.
 *
 *   It is not part of the record. Nothing here counts towards the summary,
 *   nothing here can be appealed, and nothing here follows the account when
 *   staff decide what to do. It is context for the person deciding, not
 *   evidence against the person being decided about.
 *
 *   Notes cannot be edited. A note is what somebody thought at the time it
 *   was written, and a note that can be quietly reworded later is worth
 *   nothing to the next person who reads it. Add a follow-up instead.
 */

require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_admin.php';

/** Longest a note can be. Long enough for a paragraph, short of an essay. */
const BS_PAD_MAX = 1000;

/** How many notes come back with the account. */
const BS_PAD_LIMIT = 200;

/**
 * Who may remove a note.
 *
 * The person who wrote it, because they may have written it about the wrong
 * account or thought better of it, and Management and above, because
 * somebody has to be able to remove a note that should never have been
 * written and it cannot be the person who wrote it.
 */
const BS_PAD_ADMIN_RANK = 8;

/** Has docs/migration-scratchpad.sql been run? */
function pad_available(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try { $pdo->query('SELECT 1 FROM ucp_scratchpad LIMIT 1'); $ok = true; }
    catch (Throwable $e) { $ok = false; }
    return $ok;
}

function pad_may_view(array $acc): bool
{
    return admin_may_search((int)$acc['admin_rank']);
}

function pad_may_delete(array $acc, array $note): bool
{
    if ((int)($acc['admin_rank'] ?? 0) >= BS_PAD_ADMIN_RANK) return true;
    return $note['author_id'] !== null && (int)$note['author_id'] === (int)$acc['id'];
}

/**
 * Every note on one account, newest first.
 *
 * `author_rank` is stored on the row rather than looked up, so a note keeps
 * the rank its author held when they wrote it. A note from a Lead Admin
 * two years ago should not turn into a note from a Member because that
 * person has since left the team — the note was written with that authority
 * and the page should say so.
 */
function pad_list(PDO $pdo, int $accountId, array $viewer): array
{
    if (!pad_available($pdo)) return [];

    $st = $pdo->prepare(
        'SELECT * FROM ucp_scratchpad WHERE account_id = ?
          ORDER BY created_at DESC, id DESC LIMIT ' . BS_PAD_LIMIT
    );
    $st->execute([$accountId]);

    $out = [];
    foreach ($st->fetchAll() as $n) {
        $out[] = [
            'id'         => (int)$n['id'],
            'by'         => $n['author_name'] ?: 'Unknown',
            'rank'       => (int)$n['author_rank'],
            'rank_name'  => rank_name((int)$n['author_rank']),
            'body'       => (string)$n['body'],
            'at'         => (int)$n['created_at'],
            'can_delete' => pad_may_delete($viewer, $n),
        ];
    }
    return $out;
}

function pad_by_id(PDO $pdo, int $id): ?array
{
    if (!pad_available($pdo)) return null;
    $st = $pdo->prepare('SELECT * FROM ucp_scratchpad WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $n = $st->fetch();
    return $n ?: null;
}
