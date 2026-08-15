<?php
/**
 * BlaineSide UCP — notifications.
 *
 * A notification is a row addressed to one person. Nothing here decides
 * WHO should hear about something — the endpoint that did the thing knows
 * that, and says so. This file is only the writing, the reading, and the
 * one rule worth centralising: a person is never notified about their own
 * action. Being told "you replied" is noise, and it is the fastest way to
 * teach somebody that the bell is not worth opening.
 *
 * Degrades to silence when docs/migration-notifications.sql hasn't been
 * run: notify() returns false, every caller ignores it, and the feature is
 * simply absent rather than fatal. Nothing in the UCP should fail to save
 * because a notification could not be written.
 *
 * Include AFTER _bootstrap.php.
 */

declare(strict_types=1);

/** How many a person keeps. Older ones are trimmed as new ones arrive. */
const BS_NOTE_KEEP = 60;

/** A burst inside this window collapses onto one row. */
const BS_NOTE_DEDUPE_WINDOW = 21600;      // 6 hours

function notes_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_notifications LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}

/**
 * Write one notification.
 *
 * $opt: body, url, actor_name, dedupe, actor_id.
 *
 * Returns false when it wasn't written — not migrated, no recipient, or the
 * recipient is the person who caused it. Callers do not check: a
 * notification that fails is not a reason to fail the thing it was about.
 */
function notify(PDO $pdo, int $accountId, string $area, string $kind,
                string $title, array $opt = []): bool
{
    if ($accountId <= 0 || !notes_available($pdo)) return false;

    /* Never tell somebody what they just did. */
    if (!empty($opt['actor_id']) && (int)$opt['actor_id'] === $accountId) return false;

    $now    = time();
    $dedupe = isset($opt['dedupe']) && $opt['dedupe'] !== '' ? (string)$opt['dedupe'] : null;

    try {
        /* Collapse a burst. Five comments on one appeal before anybody
           opens it is one unread notification, updated in place, not five
           — the count is meant to say "there is something here", and a
           badge reading 14 for one conversation says the opposite. */
        if ($dedupe !== null) {
            $st = $pdo->prepare(
                'SELECT id FROM ucp_notifications
                  WHERE account_id = ? AND dedupe = ? AND read_at IS NULL AND created_at > ?
                  ORDER BY id DESC LIMIT 1'
            );
            $st->execute([$accountId, $dedupe, $now - BS_NOTE_DEDUPE_WINDOW]);
            $hit = $st->fetchColumn();
            if ($hit) {
                $pdo->prepare(
                    'UPDATE ucp_notifications
                        SET title = ?, body = ?, url = ?, actor_name = ?, kind = ?,
                            created_at = ?
                      WHERE id = ?'
                )->execute([
                    mb_substr($title, 0, 190),
                    isset($opt['body']) ? mb_substr((string)$opt['body'], 0, 255) : null,
                    $opt['url'] ?? null, $opt['actor_name'] ?? null, $kind, $now, (int)$hit,
                ]);
                return true;
            }
        }

        $pdo->prepare(
            'INSERT INTO ucp_notifications
                (account_id, area, kind, title, body, url, actor_name, dedupe, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $accountId, $area, $kind, mb_substr($title, 0, 190),
            isset($opt['body']) ? mb_substr((string)$opt['body'], 0, 255) : null,
            $opt['url'] ?? null, $opt['actor_name'] ?? null, $dedupe, $now,
        ]);

        notes_trim($pdo, $accountId);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** The same notification to several people, minus the actor. */
function notify_all(PDO $pdo, array $accountIds, string $area, string $kind,
                    string $title, array $opt = []): void
{
    $seen = [];
    foreach ($accountIds as $id) {
        $id = (int)$id;
        if ($id <= 0 || isset($seen[$id])) continue;
        $seen[$id] = true;
        notify($pdo, $id, $area, $kind, $title, $opt);
    }
}

/** Keep the newest BS_NOTE_KEEP. Nobody scrolls to notification 200. */
function notes_trim(PDO $pdo, int $accountId): void
{
    try {
        $st = $pdo->prepare(
            'SELECT id FROM ucp_notifications WHERE account_id = ?
              ORDER BY created_at DESC, id DESC LIMIT 1 OFFSET ' . (int)BS_NOTE_KEEP
        );
        $st->execute([$accountId]);
        $cut = $st->fetchColumn();
        if ($cut) {
            $pdo->prepare('DELETE FROM ucp_notifications WHERE account_id = ? AND id <= ?')
                ->execute([$accountId, (int)$cut]);
        }
    } catch (Throwable $e) { /* trimming is housekeeping, never load-bearing */ }
}

/** Newest first, unread included. */
function notes_list(PDO $pdo, int $accountId, int $limit = 20): array
{
    if (!notes_available($pdo)) return [];
    try {
        $st = $pdo->prepare(
            'SELECT * FROM ucp_notifications WHERE account_id = ?
              ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit
        );
        $st->execute([$accountId]);
        return array_map(function ($n) {
            return [
                'id'     => (int)$n['id'],
                'area'   => (string)$n['area'],
                'kind'   => (string)$n['kind'],
                'title'  => (string)$n['title'],
                'body'   => $n['body'] !== null && $n['body'] !== '' ? (string)$n['body'] : null,
                'url'    => $n['url'] ?: null,
                'actor'  => $n['actor_name'] ?: null,
                'at'     => (int)$n['created_at'],
                'read'   => $n['read_at'] !== null,
            ];
        }, $st->fetchAll());
    } catch (Throwable $e) {
        return [];
    }
}

function notes_unread(PDO $pdo, int $accountId): int
{
    if (!notes_available($pdo)) return 0;
    try {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM ucp_notifications WHERE account_id = ? AND read_at IS NULL'
        );
        $st->execute([$accountId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
