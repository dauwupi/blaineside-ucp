<?php
/**
 * BlaineSide UCP — County Bulletin: shared rules.
 *
 * Who may post, how many can sit on the dashboard, and what one row looks
 * like once it leaves the database. The endpoints are thin; the decisions
 * are here, in one place, so the page and the API can't disagree about them.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

/**
 * Minimum rank that may write bulletins: 8 = Management, 9 = Founder.
 *
 * Reading is a different matter — every signed-in player sees the dashboard
 * carousel. This gates writing, and the management page itself.
 */
const BS_BULLETIN_MIN_RANK = 8;

/** How many bulletins may rotate on the dashboard at once. */
const BS_BULLETIN_MAX_SHOWN = 5;

/** Bulletins per page on the management listing. Matches the page's grid. */
const BS_BULLETIN_PER_PAGE = 6;

/** Biggest image we'll store, as data-URL characters. See migration-bulletins.sql. */
const BS_BULLETIN_MAX_IMAGE = 1258291;   // 1.2 MB

const BS_BULLETIN_TYPES = ['event', 'update', 'notice'];


/** May this rank publish? */
function bulletin_may_manage(int $rank): bool
{
    return $rank >= BS_BULLETIN_MIN_RANK;
}

/**
 * Ends the request with 403 unless the account may manage bulletins.
 *
 * A player who reaches one of these endpoints directly gets the same answer
 * whichever one they hit, and nothing that hints at what they'd need.
 */
function require_bulletin_manager(array $acc): void
{
    if (!bulletin_may_manage((int)$acc['admin_rank'])) {
        json_out([
            'ok'    => false,
            'error' => 'Only Management and Founders can manage bulletins.',
        ], 403);
    }
}

/**
 * One database row as the pages want it.
 *
 * $withImage is false for the management listing: six rows each carrying a
 * quarter-megabyte of base64 makes for a slow, pointless payload when the
 * card only needs a thumbnail. The listing gets `has_image` instead and the
 * editor fetches the full row when it opens.
 */
function bulletin_out(array $r, bool $withImage = true): array
{
    $out = [
        'id'        => (int)$r['id'],
        'type'      => (string)$r['type'],
        'title'     => (string)$r['title'],
        'body'      => (string)$r['body'],
        'link'      => $r['link'] !== null && $r['link'] !== '' ? (string)$r['link'] : null,
        'imgpos'    => (int)$r['image_pos'],
        'shown'     => (bool)$r['on_dashboard'],
        'by'        => (string)$r['author_name'],
        'at'        => (int)$r['created_at'],
        'has_image' => !empty($r['image']),
    ];
    if ($withImage) {
        $out['image'] = !empty($r['image']) ? (string)$r['image'] : null;
    }
    return $out;
}

/**
 * Checks a submitted image and returns it, or ends the request.
 *
 * Only ever a data: URL of an image type the editor produces. Anything else
 * — an http:// address, an SVG, a bare string — is refused rather than
 * stored: this value is written straight into an <img src> on the dashboard,
 * so what goes in the column has to be something that can only ever be an
 * image.
 */
function bulletin_check_image(?string $image): ?string
{
    if ($image === null || $image === '') return null;

    if (strlen($image) > BS_BULLETIN_MAX_IMAGE) {
        json_out(['ok' => false, 'field' => 'image',
                  'error' => 'That image is too large. Try a smaller one.'], 400);
    }
    if (!preg_match('#^data:image/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$#', $image)) {
        json_out(['ok' => false, 'field' => 'image',
                  'error' => 'That image could not be read. Use a PNG, JPG or WebP.'], 400);
    }
    return $image;
}

/**
 * Checks a "Read more" link and returns it, or ends the request.
 *
 * http and https only. A javascript: or data: URL here would be a stored
 * cross-site scripting hole on the dashboard, where the whole slide is
 * clickable — and the person clicking it has every reason to trust it.
 */
function bulletin_check_link(?string $link): ?string
{
    $link = trim((string)$link);
    if ($link === '') return null;

    if (strlen($link) > 500) {
        json_out(['ok' => false, 'field' => 'link', 'error' => 'That link is too long.'], 400);
    }
    $scheme = strtolower((string)parse_url($link, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true) || !parse_url($link, PHP_URL_HOST)) {
        json_out(['ok' => false, 'field' => 'link',
                  'error' => 'Links must start with http:// or https://'], 400);
    }
    return $link;
}
