<?php
/**
 * BlaineSide UCP — Administrative Search: shared rules and the field registry.
 *
 * Who may look accounts up, what may be searched, and how a lookup is
 * recorded. The endpoints are thin; the decisions live here so the page and
 * the API can't disagree about them.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

/**
 * Minimum rank that may use Administrative Search: 3 = Trainee Admin.
 *
 * Everything from Trainee Admin up. Support Staff (1) and Development Team
 * (2) are staff but are not administrators, and this tool reads a player's
 * Discord, linked accounts and — once it exists — their full punishment
 * record.
 */
const BS_ADMIN_MIN_RANK = 3;

/** Shortest query we will run. One letter matches most of the database. */
const BS_ADMIN_MIN_QUERY = 2;

/** Results per page. */
const BS_ADMIN_PER_PAGE = 12;

/**
 * How long before the same admin viewing the same account is logged again.
 *
 * Without this, a refresh or a back-button writes another line and the log
 * stops being readable. An hour is long enough to collapse one sitting into
 * one entry, short enough that coming back tomorrow is recorded.
 */
const BS_ADMIN_VIEW_COOLDOWN = 3600;


/** May this rank use Administrative Search? */
function admin_may_search(int $rank): bool
{
    return $rank >= BS_ADMIN_MIN_RANK;
}

/**
 * Ends the request with 403 unless the account may search.
 *
 * Every endpoint here gives the same answer, and none of them hints at what
 * rank would be enough.
 */
function require_admin_searcher(array $acc): void
{
    if (!admin_may_search((int)$acc['admin_rank'])) {
        json_out([
            'ok'    => false,
            'error' => 'Administrative Search is for Trainee Admin and above.',
        ], 403);
    }
}


/* =====================================================================
   THE FIELD REGISTRY

   One entry per way of searching. This is the extension point: the page
   builds its picker from whatever this returns, so adding a search means
   adding an entry here and a case in admin_search_run() — no page edits.

   `available` is the honest bit. Characters, properties, businesses and
   vehicles are designed but have no tables behind them, and a search box
   that silently returns nothing for them is worse than one that says so:
   an admin would conclude the player has no characters rather than that
   the feature doesn't exist. Unavailable fields are still LISTED, so the
   ladder of what is coming is visible, and they refuse politely.
   ===================================================================== */

/**
 * Every searchable field, in the order the picker shows them.
 *
 * @return array<int,array{key:string,label:string,group:string,placeholder:string,hint:string,available:bool,why:?string}>
 */
function admin_search_fields(): array
{
    $forumReady = function_exists('ips_endpoint') && ips_endpoint('core/members') !== null;

    return [
        [
            'key'         => 'ucp',
            'label'       => 'UCP name',
            'group'       => 'Account',
            'placeholder' => 'Part of a UCP name…',
            'hint'        => 'The name they sign in with. Partial matches are fine.',
            'available'   => true,
            'why'         => null,
        ],
        [
            'key'         => 'character',
            'label'       => 'Character name',
            'group'       => 'Account',
            'placeholder' => 'Firstname Lastname…',
            'hint'        => 'The name a character goes by in the city.',
            'available'   => false,
            'why'         => 'Characters aren\'t in the UCP yet — there is nothing to search '
                           . 'until the game server is linked.',
        ],
        [
            'key'         => 'forum',
            'label'       => 'Forum name',
            'group'       => 'Linked accounts',
            'placeholder' => 'Part of a forum display name…',
            'hint'        => 'Asks the forum who matches, then finds the UCP account behind it.',
            'available'   => $forumReady,
            'why'         => $forumReady ? null
                           : 'The forum API isn\'t configured on this server, so forum names '
                           . 'can\'t be looked up.',
        ],
        [
            'key'         => 'discord',
            'label'       => 'Discord name',
            'group'       => 'Linked accounts',
            'placeholder' => 'Part of a Discord username…',
            'hint'        => 'Searches both the confirmed Discord account and what they typed at sign-up.',
            'available'   => true,
            'why'         => null,
        ],
    ];
}

/** One field by key, or null. */
function admin_search_field(string $key): ?array
{
    foreach (admin_search_fields() as $f) {
        if ($f['key'] === $key) return $f;
    }
    return null;
}


/* =====================================================================
   RUNNING A SEARCH
   ===================================================================== */

/**
 * The columns every result row is built from. One list, so a new search
 * can't accidentally return a different shape from the others.
 */
function admin_search_columns(): string
{
    return 'id, username, admin_rank, status, created_at, last_login,
            forum_member_id, discord, discord_username, totp_enabled';
}

/**
 * Turns one account row into a result.
 *
 * `matched_on` is what the row shows underneath the name. When you searched
 * Discord and got back a UCP name you don't recognise, the thing you need to
 * see is the Discord handle that matched — not the name.
 */
function admin_result_out(array $r, string $field, ?string $matched): array
{
    return [
        'id'         => (int)$r['id'],
        'name'       => (string)$r['username'],
        'rank'       => (int)$r['admin_rank'],
        'role'       => rank_name((int)$r['admin_rank']),
        'status'     => (string)$r['status'],
        'created_at' => $r['created_at'],
        'last_login' => $r['last_login'],
        'twofa'      => !empty($r['totp_enabled']),
        'forum'      => $r['forum_member_id'] !== null,
        'field'      => $field,
        'matched_on' => $matched,
    ];
}

/**
 * Runs one search and returns [rows, total, note].
 *
 * `note` is a line the page shows above the results when there is something
 * about the search itself worth saying — a forum lookup that couldn't reach
 * the forum, for instance. Silence there would read as "no such player".
 *
 * @return array{0:array,1:int,2:?string}
 */
function admin_search_run(PDO $pdo, string $field, string $q, int $page): array
{
    $per    = BS_ADMIN_PER_PAGE;
    $offset = max(0, ($page - 1) * $per);
    // `_` and `%` are wildcards, and UCP names are full of underscores
    // (mgr_one), so they have to be escaped. The escape character is a pipe,
    // not the usual backslash: '\\' is one character to MySQL and two to
    // SQLite, so an ESCAPE clause written with it works on one and errors on
    // the other. A pipe means the same thing to both.
    $like   = '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], mb_strtolower($q)) . '%';
    $cols   = admin_search_columns();

    switch ($field) {

        // -------------------------------------------------------------
        case 'ucp':
            $where = "username_lower LIKE ? ESCAPE '|'";
            $args  = [$like];
            $total = admin_count($pdo, $where, $args);
            $rows  = admin_page($pdo, $cols, $where, $args, $per, $offset);
            return [array_map(function ($r) use ($field) {
                return admin_result_out($r, $field, null);
            }, $rows), $total, null];

        // -------------------------------------------------------------
        case 'discord':
            // Two columns, deliberately: `discord_username` is what Discord
            // itself confirmed when they linked, `discord` is whatever they
            // typed into the sign-up form. An admin chasing a handle from a
            // report has no idea which of the two it came from.
            $where = "(LOWER(discord_username) LIKE ? ESCAPE '|'"
                    . " OR LOWER(discord) LIKE ? ESCAPE '|')";
            $args  = [$like, $like];
            $total = admin_count($pdo, $where, $args);
            $rows  = admin_page($pdo, $cols, $where, $args, $per, $offset);
            return [array_map(function ($r) use ($field) {
                $m = $r['discord_username'] ?: ($r['discord'] ?: null);
                return admin_result_out($r, $field, $m !== null ? (string)$m : null);
            }, $rows), $total, null];

        // -------------------------------------------------------------
        case 'forum':
            return admin_search_forum($pdo, $q, $page, $per, $cols);
    }

    return [[], 0, null];
}

/** COUNT(*) for a where clause. */
function admin_count(PDO $pdo, string $where, array $args): int
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM ucp_accounts WHERE $where");
    $st->execute($args);
    return (int)$st->fetchColumn();
}

/** One page of accounts for a where clause, newest sign-in first. */
function admin_page(PDO $pdo, string $cols, string $where, array $args, int $per, int $offset): array
{
    $st = $pdo->prepare(
        "SELECT $cols FROM ucp_accounts
          WHERE $where
          ORDER BY username_lower ASC
          LIMIT $per OFFSET $offset"
    );
    $st->execute($args);
    return $st->fetchAll();
}


/**
 * Forum-name search: ask the forum, then match what it says back to us.
 *
 * The forum owns those names, so this is the one search that leaves the
 * server. Two things are worth knowing about it:
 *
 * 1. Every name IPS returns is checked against the query here as well.
 *    If a future IPS release stops honouring the `name` filter it would
 *    hand back the whole member list, and without this check that would
 *    silently turn into "every account matches".
 *
 * 2. A forum that is down produces a note, not an empty result. "No
 *    matches" and "couldn't ask" look identical on screen and mean
 *    completely different things to whoever is running the search.
 *
 * @return array{0:array,1:int,2:?string}
 */
function admin_search_forum(PDO $pdo, string $q, int $page, int $per, string $cols): array
{
    $url = ips_endpoint('core/members', ['name' => $q, 'perPage' => 50]);
    if ($url === null || !function_exists('curl_init')) {
        return [[], 0, 'The forum API isn\'t reachable from here, so forum names can\'t be searched right now.'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_USERPWD        => ips_userpwd(),
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !is_string($body)) {
        return [[], 0, 'The forum didn\'t answer, so these results would be incomplete. Try again shortly.'];
    }

    $data = json_decode($body, true);
    $list = is_array($data) && isset($data['results']) && is_array($data['results'])
          ? $data['results'] : [];

    // Verify the match ourselves — see note 1 above.
    $needle = mb_strtolower($q);
    $byId   = [];
    foreach ($list as $m) {
        $mid  = (int)($m['id'] ?? 0);
        $name = (string)($m['name'] ?? '');
        if ($mid > 0 && $name !== '' && mb_strpos(mb_strtolower($name), $needle) !== false) {
            $byId[$mid] = $name;
        }
    }
    if (!$byId) return [[], 0, null];

    $ids = array_keys($byId);
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $st = $pdo->prepare("SELECT COUNT(*) FROM ucp_accounts WHERE forum_member_id IN ($in)");
    $st->execute($ids);
    $total = (int)$st->fetchColumn();

    $offset = max(0, ($page - 1) * $per);
    $st = $pdo->prepare(
        "SELECT $cols FROM ucp_accounts
          WHERE forum_member_id IN ($in)
          ORDER BY username_lower ASC
          LIMIT $per OFFSET $offset"
    );
    $st->execute($ids);

    $rows = array_map(function ($r) use ($byId) {
        $mid = (int)$r['forum_member_id'];
        return admin_result_out($r, 'forum', $byId[$mid] ?? null);
    }, $st->fetchAll());

    // Names the forum knows that the UCP doesn't. Worth saying: it usually
    // means a forum account that never finished creating a UCP.
    $note = null;
    if ($total === 0 && $byId) {
        $note = 'The forum has ' . count($byId) . ' ' . (count($byId) === 1 ? 'member' : 'members')
              . ' matching that name, but none of them has a UCP account.';
    }

    return [$rows, $total, $note];
}


/* =====================================================================
   RECORDING A LOOKUP
   ===================================================================== */

/**
 * Writes "looked at this account" to the SEARCHING admin's security log.
 *
 * On the admin's own log rather than the player's, on purpose. The player's
 * activity log is a list of things that happened to their account, and being
 * read by staff isn't one of them — putting it there would also tell anyone
 * under investigation that they are being looked at.
 *
 * The line names the account and its id, so "who has been reading this
 * player's record" is answerable by searching the logs for that id.
 *
 * Silently does nothing when the log table hasn't been migrated yet: an
 * audit trail is not a reason to refuse an admin the page.
 */
function admin_log_view(PDO $pdo, array $actor, array $target): void
{
    if (!function_exists('security_log') || !sessions_available($pdo)) return;

    $aid = (int)$actor['id'];
    $tid = (int)$target['id'];
    if ($aid === $tid) return;   // reading your own profile isn't a lookup

    // One line per sitting — see BS_ADMIN_VIEW_COOLDOWN.
    try {
        $st = $pdo->prepare(
            "SELECT created_at FROM ucp_security_log
              WHERE account_id = ? AND event = 'admin_view' AND detail LIKE ?
              ORDER BY created_at DESC LIMIT 1"
        );
        $st->execute([$aid, '%(#' . $tid . ')%']);
        $last = $st->fetchColumn();
        if ($last !== false && (int)$last > time() - BS_ADMIN_VIEW_COOLDOWN) return;
    } catch (Throwable $e) {
        // If the check fails, log anyway — a duplicate line beats a missing one.
    }

    security_log(
        $pdo, $aid, 'admin_view',
        'Viewed the account of ' . $target['username'] . ' (#' . $tid . ')',
        'info'
    );
}
