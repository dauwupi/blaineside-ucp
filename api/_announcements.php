<?php
/**
 * BlaineSide UCP — dashboard announcements: shared rules.
 *
 * Same rank gate as the bulletin board (Management and above), so this file
 * leans on _bulletins.php for that rather than growing a second answer to
 * the same question.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/_bulletins.php';

const BS_ANNOUNCE_TYPES = ['notice', 'maintenance', 'warning', 'critical', 'success'];

/** Announcements per page in the management listing. */
const BS_ANNOUNCE_PER_PAGE = 8;

/** One row, as the pages want it. */
function announcement_out(array $r): array
{
    return [
        'id'          => (int)$r['id'],
        'type'        => (string)$r['type'],
        'lead'        => (string)$r['lead'],
        'body'        => $r['body'] !== null && $r['body'] !== '' ? (string)$r['body'] : null,
        'link'        => $r['link'] !== null && $r['link'] !== '' ? (string)$r['link'] : null,
        'dismissable' => (bool)$r['dismissable'],
        'active'      => (bool)$r['active'],
        'by'          => (string)$r['author_name'],
        'at'          => (int)$r['created_at'],
        // Bumped whenever the row changes, so a dismissed strip comes back
        // if its wording is edited — the player hasn't read THIS one.
        'rev'         => (int)($r['updated_at'] ?? $r['created_at']),
    ];
}
