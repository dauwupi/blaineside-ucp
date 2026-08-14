<?php
/**
 * BlaineSide UCP — Administrative Search: rules, the field registry, and the
 * query builder.
 *
 * Who may look accounts up, what may be searched, how the criteria combine,
 * and how a lookup is recorded. The endpoints are thin; the decisions live
 * here so the page and the API can't disagree about them.
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

/**
 * Rank from which an administrator may look at STAFF accounts: 8 = Management.
 *
 * Everyone below that can look up players and nobody else. An admin reading
 * another admin's account — their linked Discord, their sign-in history and,
 * once it exists, their punishment record — is a different thing from an
 * admin reading a player's, and it is Staff Management's business.
 *
 * "Staff" means any group above Member, so Support Staff and Development Team
 * are covered by it too even though neither can use this tool.
 *
 * Looking at your OWN account is never blocked, whatever your rank.
 */
const BS_ADMIN_STAFF_RANK = 8;

/** Results per page. */
const BS_ADMIN_PER_PAGE = 15;

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


/**
 * May this actor open an account in that group?
 *
 * $canSeeStaff is worked out once per request by admin_can_see_staff() —
 * rank OR the Staff Management sub-group. Passing the answer rather than the
 * actor means every call site is looking at the same decision, and there is
 * one place to change it.
 */
function admin_may_view(bool $canSeeStaff, int $targetRank, bool $self): bool
{
    if ($self) return true;
    if ($canSeeStaff) return true;
    return $targetRank < 1;      // players only
}

/**
 * Who may look at staff accounts: Management and Founders by rank, and any
 * administrator holding the Staff Management sub-group.
 *
 * That second half is the whole reason sub-groups exist. An Admin Lvl 2 on
 * the staff team needs to open staff accounts to do the job; promoting them
 * to Management to allow it would hand over group management, bulletins and
 * announcements as well.
 */
function admin_can_see_staff(PDO $pdo, array $acc): bool
{
    if ((int)$acc['admin_rank'] >= BS_ADMIN_STAFF_RANK) return true;
    return function_exists('has_team') && has_team($pdo, (int)$acc['id'], 'staff_management');
}

/**
 * The one sentence somebody sees when they try. Deliberately says what to do
 * next: "no" without a route forward just generates a message to whoever is
 * nearest, which is usually the wrong person.
 */
function admin_view_block_reason(): string
{
    return 'You\'re trying to open another staff member\'s account. Staff accounts are '
         . 'only visible to Staff Management — please contact them with any queries.';
}


/* =====================================================================
   THE REGISTRY

   Three lookups, each a form of many fields that combine with AND — fill
   in two boxes and you get the accounts matching both. That is the shape
   this has to be: a single "search by…" dropdown works for four options
   and falls apart at thirty, and it can't express "cash over 50k AND in
   this faction" at all.

   Every field carries `available`. Most of what an admin will eventually
   want to search — characters, phone numbers, balances, playing hours,
   factions, properties, vehicles — has no table behind it yet, because it
   lives on a game server that isn't connected. Those fields are still
   LISTED and still labelled, so the shape of the finished tool is visible
   and nobody has to guess what is coming; they are just disabled, and the
   server ignores them even if a request arrives carrying one.

   That last part matters more than it looks. A search box that silently
   returns nothing reads as "this player has no characters", not as "this
   feature does not exist" — which is exactly the wrong thing for someone
   to conclude while they are deciding whether to ban somebody.

   Field types: text, number, select, date.
   ===================================================================== */

function admin_search_tabs(): array
{
    $forumReady = function_exists('ips_endpoint') && ips_endpoint('core/members') !== null;

    $SOON_GAME = 'Waiting on the game server link — there is no data behind this yet.';

    // Group options for the ladder, high to low.
    $groups = [['', 'Any group']];
    for ($i = 9; $i >= 0; $i--) $groups[] = [(string)$i, rank_name($i)];

    return [
        // =============================================================
        [
            'key'   => 'user',
            'label' => 'User Lookup',
            'available' => true,
            'why'   => null,
            'fields' => [
                ['key'=>'ucp',      'label'=>'UCP name',        'type'=>'text',   'icon'=>'user',    'available'=>true],
                ['key'=>'id',       'label'=>'UCP account ID',  'type'=>'number', 'icon'=>'hash',    'available'=>true],
                ['key'=>'email',    'label'=>'Email address',   'type'=>'text',   'icon'=>'mail',    'available'=>true],
                ['key'=>'discord',  'label'=>'Discord username','type'=>'text',   'icon'=>'discord', 'available'=>true],
                ['key'=>'forum',    'label'=>'Forum name',      'type'=>'text',   'icon'=>'chat',
                 'available'=>$forumReady,
                 'why'=>'The forum API isn\'t configured on this server.'],
                ['key'=>'group',    'label'=>'Group',           'type'=>'select', 'icon'=>'shield',
                 'available'=>true, 'options'=>$groups],
                ['key'=>'status',   'label'=>'Account status',  'type'=>'select', 'icon'=>'flag', 'available'=>true,
                 'options'=>[['','Any status'],['active','Active'],['suspended','Suspended'],['pending','Pending email']]],
                ['key'=>'twofa',    'label'=>'Two-step',        'type'=>'select', 'icon'=>'lock', 'available'=>true,
                 'options'=>[['','Either'],['1','On'],['0','Off']]],
                ['key'=>'joined_after',  'label'=>'Registered after',  'type'=>'date', 'icon'=>'cal', 'available'=>true],
                ['key'=>'joined_before', 'label'=>'Registered before', 'type'=>'date', 'icon'=>'cal', 'available'=>true],
                ['key'=>'seen_after',    'label'=>'Last seen after',   'type'=>'date', 'icon'=>'clock','available'=>true],
                ['key'=>'seen_before',   'label'=>'Last seen before',  'type'=>'date', 'icon'=>'clock','available'=>true],

                // --- designed, no data yet ---
                ['key'=>'firstname', 'label'=>'First name',              'type'=>'text',  'icon'=>'card',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'lastname',  'label'=>'Last name',               'type'=>'text',  'icon'=>'card',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'charid',    'label'=>'Character ID',            'type'=>'number','icon'=>'user',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'phone',     'label'=>'Phone number',            'type'=>'text',  'icon'=>'phone', 'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'cash_min',  'label'=>'Cash is more than',       'type'=>'number','icon'=>'cash',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'cash_max',  'label'=>'Cash is less than',       'type'=>'number','icon'=>'cash',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'bank_min',  'label'=>'Bank is more than',       'type'=>'number','icon'=>'bank',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'bank_max',  'label'=>'Bank is less than',       'type'=>'number','icon'=>'bank',  'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'hours_min', 'label'=>'Playing hours more than', 'type'=>'number','icon'=>'clock', 'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'hours_max', 'label'=>'Playing hours less than', 'type'=>'number','icon'=>'clock', 'available'=>false,'why'=>$SOON_GAME],
                ['key'=>'faction',   'label'=>'Faction name or ID',      'type'=>'text',  'icon'=>'flag',  'available'=>false,'why'=>$SOON_GAME],
            ],
        ],

        // =============================================================
        [
            'key'   => 'property',
            'label' => 'Property / Shop Lookup',
            'available' => false,
            'why'   => 'Properties and businesses aren\'t in the UCP yet. The fields below are '
                     . 'the ones this lookup will have once the game server is linked.',
            'fields' => [
                ['key'=>'owner',      'label'=>'Owner name',            'type'=>'text',  'icon'=>'card',  'available'=>false],
                ['key'=>'name',       'label'=>'Property / business',   'type'=>'text',  'icon'=>'house', 'available'=>false],
                ['key'=>'type',       'label'=>'Property type',         'type'=>'text',  'icon'=>'sort',  'available'=>false],
                ['key'=>'pid',        'label'=>'Property ID',           'type'=>'number','icon'=>'hash',  'available'=>false],
                ['key'=>'cash_min',   'label'=>'Cashbox more than',     'type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'cash_max',   'label'=>'Cashbox less than',     'type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'price_min',  'label'=>'Market price more than','type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'price_max',  'label'=>'Market price less than','type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'rent_min',   'label'=>'Rent more than',        'type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'rent_max',   'label'=>'Rent less than',        'type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'entry_min',  'label'=>'Entrance fee more than','type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'entry_max',  'label'=>'Entrance fee less than','type'=>'number','icon'=>'cash',  'available'=>false],
                ['key'=>'faction',    'label'=>'Faction name or ID',    'type'=>'text',  'icon'=>'flag',  'available'=>false],
                ['key'=>'interior',   'label'=>'Interior ID',           'type'=>'number','icon'=>'hash',  'available'=>false],
            ],
        ],

        // =============================================================
        [
            'key'   => 'vehicle',
            'label' => 'Vehicle Lookup',
            'available' => false,
            'why'   => 'Vehicles aren\'t in the UCP yet. The fields below are the ones this '
                     . 'lookup will have once the game server is linked.',
            'fields' => [
                ['key'=>'owner',   'label'=>'Owner name',         'type'=>'text',  'icon'=>'card',  'available'=>false],
                ['key'=>'vid',     'label'=>'Vehicle ID',         'type'=>'number','icon'=>'hash',  'available'=>false],
                ['key'=>'plate',   'label'=>'Vehicle plate',      'type'=>'text',  'icon'=>'plate', 'available'=>false],
                ['key'=>'model',   'label'=>'Vehicle model',      'type'=>'text',  'icon'=>'car',   'available'=>false],
                ['key'=>'faction', 'label'=>'Faction name or ID', 'type'=>'text',  'icon'=>'flag',  'available'=>false],
            ],
        ],
    ];
}

/** One tab by key, or null. */
function admin_search_tab(string $key): ?array
{
    foreach (admin_search_tabs() as $t) if ($t['key'] === $key) return $t;
    return null;
}


/* =====================================================================
   BUILDING THE QUERY
   ===================================================================== */

/**
 * Escapes LIKE wildcards and wraps in %…%.
 *
 * `_` and `%` are wildcards, and UCP names are full of underscores
 * (mgr_one), so they have to be escaped or a search for "mgr_one" quietly
 * matches "mgrXone" too. The escape character is a pipe rather than the
 * usual backslash: '\\' is one character to MySQL and two to SQLite, so an
 * ESCAPE clause written with a backslash works on one and errors on the
 * other. A pipe means the same thing to both.
 */
function admin_like(string $q): string
{
    return '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], mb_strtolower(trim($q))) . '%';
}

/** Same escaping, but anchored at the start — "starts with" rather than "contains". */
function admin_like_prefix(string $q): string
{
    return str_replace(['|', '%', '_'], ['||', '|%', '|_'], mb_strtolower(trim($q))) . '%';
}

/** A yyyy-mm-dd from the form, or null. */
function admin_date(?string $v): ?string
{
    $v = trim((string)$v);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
}

/**
 * Turns the submitted criteria into a WHERE clause.
 *
 * Everything ANDs. Only fields the registry marks `available` are read —
 * a request carrying cash_min is ignored rather than erroring, because the
 * column it would filter on does not exist.
 *
 * @return array{0:string,1:array,2:array,3:?string}  where, args, used, note
 */
function admin_build_user_query(PDO $pdo, array $in): array
{
    $where = [];
    $args  = [];
    $used  = [];
    $note  = null;

    /* Staff are NOT filtered out here.
     *
     * They were, briefly, and it was the wrong call: somebody searching a
     * name needs to know the account exists, otherwise they go on looking
     * for it or conclude it was deleted. What they can't do is open it — so
     * the row comes back stripped by admin_result_out() and the profile
     * endpoint refuses. Existence is the answer; the contents aren't. */

    $has = function (string $k) use ($in): ?string {
        $v = isset($in[$k]) ? trim((string)$in[$k]) : '';
        return $v === '' ? null : $v;
    };

    if (($v = $has('ucp')) !== null) {
        $where[] = "username_lower LIKE ? ESCAPE '|'";
        $args[]  = admin_like($v);
        $used[]  = 'ucp';
    }
    if (($v = $has('id')) !== null && ctype_digit($v)) {
        $where[] = 'id = ?';
        $args[]  = (int)$v;
        $used[]  = 'id';
    }
    if (($v = $has('email')) !== null) {
        $where[] = "email_lower LIKE ? ESCAPE '|'";
        $args[]  = admin_like($v);
        $used[]  = 'email';
    }
    if (($v = $has('discord')) !== null) {
        // Two columns, deliberately: `discord_username` is what Discord
        // itself confirmed when they linked, `discord` is whatever they
        // typed into the sign-up form. An admin chasing a handle from a
        // report has no idea which of the two it came from.
        $where[] = "(LOWER(discord_username) LIKE ? ESCAPE '|' OR LOWER(discord) LIKE ? ESCAPE '|')";
        $args[]  = admin_like($v);
        $args[]  = admin_like($v);
        $used[]  = 'discord';
    }
    if (($v = $has('group')) !== null && ctype_digit($v)) {
        $where[] = 'admin_rank = ?';
        $args[]  = (int)$v;
        $used[]  = 'group';
    }
    if (($v = $has('status')) !== null && in_array($v, ['active','suspended','pending'], true)) {
        $where[] = 'status = ?';
        $args[]  = $v;
        $used[]  = 'status';
    }
    if (($v = $has('twofa')) !== null && ($v === '0' || $v === '1')) {
        $where[] = $v === '1' ? 'totp_enabled = 1' : '(totp_enabled IS NULL OR totp_enabled = 0)';
        $used[]  = 'twofa';
    }
    if (($v = admin_date($has('joined_after'))) !== null) {
        $where[] = 'created_at >= ?'; $args[] = $v . ' 00:00:00'; $used[] = 'joined_after';
    }
    if (($v = admin_date($has('joined_before'))) !== null) {
        $where[] = 'created_at <= ?'; $args[] = $v . ' 23:59:59'; $used[] = 'joined_before';
    }
    if (($v = admin_date($has('seen_after'))) !== null) {
        $where[] = 'last_login >= ?'; $args[] = $v . ' 00:00:00'; $used[] = 'seen_after';
    }
    if (($v = admin_date($has('seen_before'))) !== null) {
        $where[] = 'last_login <= ?'; $args[] = $v . ' 23:59:59'; $used[] = 'seen_before';
    }

    // The one criterion that leaves this server.
    if (($v = $has('forum')) !== null) {
        $used[] = 'forum';
        list($ids, $note) = admin_forum_ids($v);
        if ($ids === null) {
            // Couldn't ask. Refuse the whole search rather than returning
            // the other criteria's matches as though the forum filter had
            // been applied — that would be a wrong answer, not a partial one.
            return ['', [], $used, $note];
        }
        if (!$ids) {
            $where[] = '1 = 0';   // nobody matched on the forum side
        } else {
            $where[] = 'forum_member_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            foreach ($ids as $id) $args[] = $id;
        }
    }

    return [$where ? implode(' AND ', $where) : '', $args, $used, $note];
}

/**
 * Asks the forum which members match a name, and returns their ids.
 *
 * Every name IPS returns is checked against the query here as well. If a
 * future IPS release stops honouring the `name` filter it would hand back
 * the whole member list, and without this check that would silently turn
 * into "every account matches" — the worst possible failure for a tool
 * somebody uses before issuing a ban.
 *
 * @return array{0:?array,1:?string}  ids (null = couldn't ask), note
 */
function admin_forum_ids(string $q): array
{
    $url = function_exists('ips_endpoint') ? ips_endpoint('core/members', ['name' => $q, 'perPage' => 50]) : null;
    if ($url === null || !function_exists('curl_init')) {
        return [null, 'The forum API isn\'t reachable from here, so forum names can\'t be searched.'];
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
        return [null, 'The forum didn\'t answer, so these results would be incomplete. Try again shortly.'];
    }

    $data = json_decode($body, true);
    $list = (is_array($data) && isset($data['results']) && is_array($data['results'])) ? $data['results'] : [];

    $needle = mb_strtolower($q);
    $ids = [];
    foreach ($list as $m) {
        $mid  = (int)($m['id'] ?? 0);
        $name = (string)($m['name'] ?? '');
        if ($mid > 0 && $name !== '' && mb_strpos(mb_strtolower($name), $needle) !== false) $ids[] = $mid;
    }

    $note = null;
    if (!$ids && $list) {
        $note = 'The forum answered, but nothing there matches that name.';
    }
    return [$ids, $note];
}


/**
 * One account row as a result.
 *
 * Two shapes, decided here rather than by the page.
 *
 * A row the caller may open carries the usual detail, with the email masked
 * — an admin who already has the address can confirm the match, but the
 * table can't be used to collect addresses it wasn't given, which is the
 * same line api/check.php and api/reset.php already hold.
 *
 * A staff row seen by anyone below Staff Management carries the id, the
 * name, and the single fact that it belongs to staff. Not the group, not
 * the email, not when they last signed in. The page draws a lock, but the
 * page isn't what's protecting them: those values never leave the server.
 */
function admin_result_out(array $r, bool $canSeeStaff = true, int $actorId = 0): array
{
    $rank = (int)$r['admin_rank'];
    $self = $actorId > 0 && (int)$r['id'] === $actorId;

    if (!admin_may_view($canSeeStaff, $rank, $self)) {
        return [
            'id'       => (int)$r['id'],
            'name'     => (string)$r['username'],
            'viewable' => false,
            'staff'    => true,
        ];
    }

    return [
        'id'         => (int)$r['id'],
        'name'       => (string)$r['username'],
        'viewable'   => true,
        'staff'      => $rank >= 1,
        'rank'       => $rank,
        'role'       => rank_name($rank),
        'status'     => (string)$r['status'],
        'email'      => mask_email((string)$r['email']),
        'created_at' => $r['created_at'],
        'last_login' => $r['last_login'],
        'twofa'      => !empty($r['totp_enabled']),
        'forum'      => $r['forum_member_id'] !== null,
        'discord'    => $r['discord_username'] ?: ($r['discord'] ?: null),
    ];
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
