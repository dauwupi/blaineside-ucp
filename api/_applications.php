<?php
/**
 * Applications — the shared rules.
 *
 * A player cannot join the server until an application has been passed. The
 * UCP and the forums stay open the whole time; only the server is gated,
 * which is why nothing in here refuses anybody a page.
 *
 * Three ideas hold the rest together:
 *
 *  1. An application is a SNAPSHOT. ucp_app_answers stores a copy of the
 *     question's title, prompt and length minimum, not just its id. Questions
 *     get rewritten and retired; a two-year-old application must still show
 *     what it actually asked. This is why questions are retired rather than
 *     deleted, and why nothing here joins answers back to the live question.
 *
 *  2. A claim is the lock. Nobody decides an application they have not
 *     claimed — except Staff Management, Management and Founder, who
 *     override every claim. Two people writing feedback on one application
 *     is the failure this prevents.
 *
 *  3. Everything degrades. Every table is optional at runtime: one migration
 *     behind, the feature says "not switched on yet" and the rest of the UCP
 *     carries on. See applications_available().
 */

/** Support Staff and above reach the panel. */
const BS_APP_PANEL_RANK = 1;
/** Rank that overrides somebody else's claim. Staff Management does too. */
const BS_APP_OVERRIDE_RANK = 8;
/** Rank that may edit the questions and the saved responses. */
const BS_APP_MANAGE_RANK = 8;
/** A claim this old is treated as abandoned and can be taken by anyone. */
const BS_APP_CLAIM_IDLE = 7200;
/** Drafts older than this are swept. */
const BS_APP_DRAFT_KEEP = 2592000;

const APP_STATUSES = ['draft', 'pending', 'passed', 'denied'];


/* ---------------------------------------------------------------------
   Is the feature switched on?

   `static $known` so a page that asks five times costs one query, and a
   try/catch so a missing table is a feature that is off rather than a 500.
   --------------------------------------------------------------------- */
function applications_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_applications LIMIT 1');
        $pdo->query('SELECT 1 FROM ucp_app_questions LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}

function applications_missing_reason(): string
{
    return 'Applications aren\'t switched on yet.';
}

/** Does the IP table exist? Its absence costs the warning, not the page. */
function app_ips_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try { $pdo->query('SELECT 1 FROM ucp_account_ips LIMIT 1'); $known = true; }
    catch (Throwable $e) { $known = false; }
    return $known;
}


/* ---------------------------------------------------------------------
   Who may open the panel.

   Rank 1 and up, and nothing else. Deliberately NOT a sub-group: Support
   Staff is a rank on the ladder and reading applications is the work that
   rank exists to do, so there is nobody the ladder leaves out.

   The second argument is accepted and ignored. Every caller has a handle
   and passes it; taking it would mean editing six files to remove an
   argument that costs nothing.
   --------------------------------------------------------------------- */
function app_may_panel(array $acc, ?PDO $pdo = null): bool
{
    return (int)$acc['admin_rank'] >= BS_APP_PANEL_RANK;
}

function app_panel_reason(): string
{
    return 'The Application Panel is for Support Staff and above.';
}

/**
 * Question Manager and Response Templates: Management and above.
 *
 * Deliberately NOT the same gate as the panel. Reviewing an application is
 * the work Support Staff do; deciding what every applicant is asked, and
 * what the standard replies say, sets the policy they work to. One is a
 * queue, the other is the rules of the queue.
 */
function app_may_manage(array $acc): bool
{
    return (int)$acc['admin_rank'] >= BS_APP_MANAGE_RANK;
}

function app_manage_reason(): string
{
    return 'Setting the questions and the saved responses is for Management and Founders.';
}

/**
 * May this account act on this application — claim it away from somebody,
 * decide it, write the feedback?
 *
 * The holder of the claim, or anyone senior enough to overrule a claim.
 * An unclaimed application is actionable by any Support Staff, because
 * claiming it IS the action they are about to take.
 */
function app_may_act(PDO $pdo, array $acc, array $app): bool
{
    $me   = (int)$acc['id'];
    $rank = (int)$acc['admin_rank'];

    if (!app_may_panel($acc, $pdo)) return false;
    if ($rank >= BS_APP_OVERRIDE_RANK) return true;
    if (app_has_staff_management($pdo, $me)) return true;

    $by = $app['claimed_by'] !== null ? (int)$app['claimed_by'] : 0;
    if (!$by) return true;                       // nobody holds it
    if ($by === $me) return true;                // you hold it
    return app_claim_stale($app);                // they walked away
}

/** A claim nobody has touched for BS_APP_CLAIM_IDLE is fair game. */
function app_claim_stale(array $app): bool
{
    if ($app['claimed_at'] === null) return true;
    return (time() - (int)$app['claimed_at']) > BS_APP_CLAIM_IDLE;
}

/** Sub-group lookup, guarded — the table arrives with a later migration. */
function app_has_team(PDO $pdo, int $id, string $key): bool
{
    static $cache = [];
    $k = $id . '|' . $key;
    if (isset($cache[$k])) return $cache[$k];
    $has = false;
    try {
        require_once __DIR__ . '/_teams.php';
        $has = has_team($pdo, $id, $key);
    } catch (Throwable $e) {
    }
    return $cache[$k] = $has;
}

function app_has_staff_management(PDO $pdo, int $id): bool
{
    return app_has_team($pdo, $id, 'staff_management');
}


/* ---------------------------------------------------------------------
   The player's own state.

   Exactly one of these is true at any moment, and the dashboard notice,
   the sidebar and /dashboard/application all read the same answer:

     none     never applied, no draft
     draft    started, not sent
     pending  sent, waiting on Support Staff
     passed   done; the server is open
     denied   last attempt was refused; they may start another
   --------------------------------------------------------------------- */
function app_state(PDO $pdo, int $accountId): array
{
    $out = ['state' => 'none', 'application' => null, 'attempts' => 0, 'passed' => false];
    if (!applications_available($pdo)) return $out;

    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM ucp_applications WHERE account_id = ? AND status <> ?'
    );
    $st->execute([$accountId, 'draft']);
    $out['attempts'] = (int)$st->fetchColumn();

    $st = $pdo->prepare(
        'SELECT * FROM ucp_applications
          WHERE account_id = ? AND status = ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$accountId, 'passed']);
    if ($row = $st->fetch()) {
        $out['state']  = 'passed';
        $out['passed'] = true;
        $out['application'] = $row;
        return $out;
    }

    $st = $pdo->prepare(
        'SELECT * FROM ucp_applications
          WHERE account_id = ? AND status IN (?, ?)
          ORDER BY FIELD(status, ?, ?), id DESC LIMIT 1'
    );
    $st->execute([$accountId, 'draft', 'pending', 'draft', 'pending']);
    if ($row = $st->fetch()) {
        $out['state'] = $row['status'];
        $out['application'] = $row;
        return $out;
    }

    $st = $pdo->prepare(
        'SELECT * FROM ucp_applications
          WHERE account_id = ? AND status = ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$accountId, 'denied']);
    if ($row = $st->fetch()) {
        $out['state'] = 'denied';
        $out['application'] = $row;
    }
    return $out;
}


/* ---------------------------------------------------------------------
   Building the question set for a new attempt.

   Every pinned question, in order, then `draw_count` drawn at random from
   the pool. The draw happens ONCE, when the draft is created, and is then
   frozen into ucp_app_answers — re-rolling the questions every time the
   page loaded would let somebody refresh until they liked the scenario.
   --------------------------------------------------------------------- */
function app_draw_count(PDO $pdo): int
{
    try {
        $st = $pdo->prepare('SELECT value FROM ucp_app_config WHERE name = ? LIMIT 1');
        $st->execute(['draw_count']);
        $v = $st->fetchColumn();
        if ($v !== false) return max(0, min(20, (int)$v));
    } catch (Throwable $e) {
    }
    return 2;
}

function app_pick_questions(PDO $pdo): array
{
    $pinned = $pdo->query(
        'SELECT * FROM ucp_app_questions
          WHERE retired = 0 AND pinned = 1 ORDER BY sort_order, id'
    )->fetchAll();

    $n    = app_draw_count($pdo);
    $pool = [];
    if ($n > 0) {
        $st = $pdo->prepare(
            'SELECT * FROM ucp_app_questions
              WHERE retired = 0 AND pinned = 0 ORDER BY RAND() LIMIT ' . (int)$n
        );
        $st->execute();
        $pool = $st->fetchAll();
    }
    return array_merge($pinned, $pool);
}

/**
 * Start a draft: one row, plus a frozen copy of the questions it asked.
 * Returns the application id.
 */
function app_start_draft(PDO $pdo, int $accountId): int
{
    $qs = app_pick_questions($pdo);
    if (!$qs) {
        throw new RuntimeException('There are no questions set up yet. Try again later.');
    }

    $st = $pdo->prepare('SELECT COUNT(*) FROM ucp_applications WHERE account_id = ? AND status <> ?');
    $st->execute([$accountId, 'draft']);
    $attempt = (int)$st->fetchColumn() + 1;

    $now = time();
    $st  = $pdo->prepare(
        'INSERT INTO ucp_applications (account_id, attempt, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $st->execute([$accountId, $attempt, 'draft', $now, $now]);
    $id = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare(
        'INSERT INTO ucp_app_answers
           (application_id, question_id, question_title, question_prompt,
            min_chars, pinned, sort_order, body)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULL)'
    );
    $i = 0;
    foreach ($qs as $q) {
        $ins->execute([
            $id, (int)$q['id'], $q['title'], $q['prompt'],
            (int)$q['min_chars'], (int)$q['pinned'], ++$i,
        ]);
    }
    return $id;
}

/** The answers on one application, in the order they were asked. */
function app_answers(PDO $pdo, int $applicationId): array
{
    $st = $pdo->prepare(
        'SELECT id, question_id, question_title, question_prompt, min_chars, pinned, sort_order, body
           FROM ucp_app_answers WHERE application_id = ? ORDER BY sort_order, id'
    );
    $st->execute([$applicationId]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[] = [
            'id'          => (int)$r['id'],
            'question_id' => $r['question_id'] !== null ? (int)$r['question_id'] : null,
            'title'     => $r['question_title'],
            'prompt'    => $r['question_prompt'],
            'min_chars' => (int)$r['min_chars'],
            'pinned'    => (bool)$r['pinned'],
            'order'     => (int)$r['sort_order'],
            'body'      => $r['body'],
            'chars'     => app_chars((string)$r['body']),
        ];
    }
    return $out;
}

/**
 * Character count, the same way everywhere, so the page and the server
 * agree on whether an answer is long enough.
 *
 * Characters rather than words because a word count is trivially gamed —
 * "a a a a a" is five words — and because mb_strlen() counts what the
 * applicant can see themselves in the box.
 *
 * Runs of whitespace collapse to one and the ends are trimmed, so a
 * minimum cannot be met with newlines.
 */
function app_chars(string $s): int
{
    return mb_strlen(trim(preg_replace('/\s+/u', ' ', $s)));
}


/* ---------------------------------------------------------------------
   Addresses.

   Recorded on sign-in by app_touch_ip(). Nothing back-fills: an account
   that has not signed in since the migration simply has no rows, which
   reads correctly as "we have not seen an address for them yet" rather
   than as a wrong answer.
   --------------------------------------------------------------------- */
function app_touch_ip(PDO $pdo, int $accountId, string $ip): void
{
    if ($ip === '' || !app_ips_available($pdo)) return;
    try {
        $now = time();
        $st  = $pdo->prepare(
            'INSERT INTO ucp_account_ips (account_id, ip, hits, first_seen, last_seen)
             VALUES (?, ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen)'
        );
        $st->execute([$accountId, substr($ip, 0, 45), $now, $now]);
    } catch (Throwable $e) {
    }
}

/** Every address we have seen for an account, newest first. */
function app_ips_for(PDO $pdo, int $accountId): array
{
    if (!app_ips_available($pdo)) return [];
    $st = $pdo->prepare(
        'SELECT ip, hits, first_seen, last_seen FROM ucp_account_ips
          WHERE account_id = ? ORDER BY last_seen DESC'
    );
    $st->execute([$accountId]);
    $rows = $st->fetchAll();
    $out  = [];
    foreach ($rows as $i => $r) {
        $out[] = [
            'ip'         => $r['ip'],
            'hits'       => (int)$r['hits'],
            'first_seen' => (int)$r['first_seen'],
            'last_seen'  => (int)$r['last_seen'],
            'current'    => $i === 0,
            'lookup'     => 'https://whatismyipaddress.com/ip/' . rawurlencode($r['ip']),
        ];
    }
    return $out;
}

/**
 * Other accounts that have used any of this account's addresses.
 *
 * Shared houses, universities and mobile carriers all produce these, so
 * the wording everywhere this is shown says "worth a look", never "this
 * is the same person". The page shows it; it never decides anything.
 */
function app_ip_matches(PDO $pdo, int $accountId): array
{
    if (!app_ips_available($pdo)) return [];
    $st = $pdo->prepare(
        'SELECT b.account_id, b.ip, b.last_seen, a2.username, a2.status, a2.admin_rank
           FROM ucp_account_ips a
           JOIN ucp_account_ips b ON b.ip = a.ip AND b.account_id <> a.account_id
           JOIN ucp_accounts a2 ON a2.id = b.account_id
          WHERE a.account_id = ?
          ORDER BY b.last_seen DESC LIMIT 25'
    );
    $st->execute([$accountId]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[] = [
            'id'        => (int)$r['account_id'],
            'name'      => $r['username'],
            'ip'        => $r['ip'],
            'last_seen' => (int)$r['last_seen'],
            'status'    => $r['status'],
        ];
    }
    return $out;
}


/* ---------------------------------------------------------------------
   Shaping one application for the wire.

   $full = false gives a row for a table. $full = true adds the answers,
   and — for staff only — the applicant block, the addresses and the log.
   --------------------------------------------------------------------- */
function app_row_out(array $r): array
{
    return [
        'id'        => (int)$r['id'],
        'attempt'   => (int)$r['attempt'],
        'status'    => $r['status'],
        'claimed'   => $r['claimed_by'] !== null ? [
            'id'   => (int)$r['claimed_by'],
            'name' => $r['claimed_by_name'],
            'at'   => $r['claimed_at'] !== null ? (int)$r['claimed_at'] : null,
            'stale'=> app_claim_stale($r),
        ] : null,
        'decided'   => $r['decided_by'] !== null ? [
            'id'   => (int)$r['decided_by'],
            'name' => $r['decided_by_name'],
            'at'   => $r['decided_at'] !== null ? (int)$r['decided_at'] : null,
        ] : null,
        'feedback'     => $r['feedback'],
        'submitted_at' => $r['submitted_at'] !== null ? (int)$r['submitted_at'] : null,
        'created_at'   => (int)$r['created_at'],
        'updated_at'   => (int)$r['updated_at'],
    ];
}

/** The applicant block on the review screen. */
function app_applicant(PDO $pdo, int $accountId): array
{
    $st = $pdo->prepare(
        'SELECT id, username, email, admin_rank, status, created_at, last_login
           FROM ucp_accounts WHERE id = ? LIMIT 1'
    );
    $st->execute([$accountId]);
    $a = $st->fetch();
    if (!$a) return ['id' => $accountId, 'name' => 'Deleted account'];

    $punishments = null;
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM ucp_punishments WHERE account_id = ?');
        $st->execute([$accountId]);
        $punishments = (int)$st->fetchColumn();
    } catch (Throwable $e) {
    }

    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM ucp_applications WHERE account_id = ? AND status IN (?, ?)'
    );
    $st->execute([$accountId, 'passed', 'denied']);

    return [
        'id'          => (int)$a['id'],
        'name'        => $a['username'],
        'email'       => $a['email'],
        'status'      => $a['status'],
        'rank'        => (int)$a['admin_rank'],
        'created_at'  => $a['created_at'],
        'last_login'  => $a['last_login'],
        'decided'     => (int)$st->fetchColumn(),
        'punishments' => $punishments,
    ];
}

/** Every attempt by one account, newest first — the history card. */
function app_history(PDO $pdo, int $accountId, ?int $exclude = null): array
{
    $st = $pdo->prepare(
        'SELECT * FROM ucp_applications
          WHERE account_id = ? AND status <> ? ORDER BY id DESC'
    );
    $st->execute([$accountId, 'draft']);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        if ($exclude !== null && (int)$r['id'] === $exclude) continue;
        $out[] = app_row_out($r);
    }
    return $out;
}

/** The running log on one application. */
function app_log_list(PDO $pdo, int $applicationId): array
{
    try {
        $st = $pdo->prepare(
            'SELECT actor_name, action, detail, created_at FROM ucp_app_log
              WHERE application_id = ? ORDER BY id'
        );
        $st->execute([$applicationId]);
        return array_map(function ($r) {
            return [
                'actor'  => $r['actor_name'],
                'action' => $r['action'],
                'detail' => $r['detail'],
                'at'     => (int)$r['created_at'],
            ];
        }, $st->fetchAll());
    } catch (Throwable $e) {
        return [];
    }
}

function app_log(PDO $pdo, int $applicationId, array $acc, string $action, ?string $detail = null): void
{
    try {
        $st = $pdo->prepare(
            'INSERT INTO ucp_app_log (application_id, actor_id, actor_name, action, detail, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $applicationId, (int)$acc['id'], $acc['username'], $action, $detail, time(),
        ]);
    } catch (Throwable $e) {
    }
}


/* ---------------------------------------------------------------------
   Questions and templates, shaped for the wire.
   --------------------------------------------------------------------- */
function app_question_out(array $r): array
{
    return [
        'assist'       => isset($r['assist']) ? (bool)$r['assist'] : false,
        'assist_rules' => isset($r['assist_rules']) ? assist_rules($r['assist_rules']) : [],
        'id'         => (int)$r['id'],
        'title'      => $r['title'],
        'prompt'     => $r['prompt'],
        'min_chars'  => (int)$r['min_chars'],
        'pinned'     => (bool)$r['pinned'],
        'retired'    => (bool)$r['retired'],
        'order'      => (int)$r['sort_order'],
        'asked'      => (int)$r['asked_count'],
        'by'         => $r['created_by_name'],
        'created_at' => (int)$r['created_at'],
        'updated_at' => (int)$r['updated_at'],
    ];
}

function app_template_out(array $r): array
{
    return [
        'id'         => (int)$r['id'],
        'title'      => $r['title'],
        'body'       => $r['body'],
        'use_for'    => $r['use_for'],
        'order'      => (int)$r['sort_order'],
        'used'       => (int)$r['used_count'],
        'by'         => $r['created_by_name'],
        'created_at' => (int)$r['created_at'],
        'updated_at' => (int)$r['updated_at'],
    ];
}

const APP_TEMPLATE_USES = ['pass' => 'Pass', 'deny' => 'Deny', 'either' => 'Either'];


/* ---------------------------------------------------------------------
   ASSIST

   A reading aid for whoever is reviewing an application. It reports two
   things about an answer: its shape (length, paragraphs, longest
   unbroken run) and whether wording the question cares about appears in
   it — "chain rob", "crime zone", and so on, configured per question in
   the Question Manager.

   Three things it deliberately is not:

     - a score. Nothing here adds up to a number, and nothing here is
       shown to the applicant.
     - a judgement. It matches TEXT. "I would never scam anyone" contains
       the word scam; a reviewer needs to see that, which is why every hit
       reports the phrase it matched rather than just a tick.
     - a gate. A missing phrase blocks nothing and is drawn in grey, not
       red: an applicant asked to name two rules who names two has done
       exactly what was asked, and six red crosses beside a correct answer
       teaches people to ignore the panel.

   Evaluated live against the question's CURRENT criteria rather than a
   copy stored with the answer. The wording of an answer is a record and
   is frozen; this is a lens somebody looks through today, and it should
   improve as the criteria do.
   --------------------------------------------------------------------- */

/** Are the assist columns there? One migration behind, the panel is off. */
function assist_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try { $pdo->query('SELECT assist FROM ucp_app_questions LIMIT 1'); $known = true; }
    catch (Throwable $e) { $known = false; }
    return $known;
}

/** [{label, words:[...]}, ...] — anything malformed is simply no rules. */
function assist_rules(?string $json): array
{
    if ($json === null || trim($json) === '') return [];
    $v = json_decode($json, true);
    if (!is_array($v)) return [];
    $out = [];
    foreach ($v as $r) {
        if (!is_array($r)) continue;
        $label = trim((string)($r['label'] ?? ''));
        $words = is_array($r['words'] ?? null) ? $r['words'] : [];
        $words = array_values(array_filter(array_map(function ($w) {
            return mb_strtolower(trim((string)$w));
        }, $words), function ($w) { return $w !== ''; }));
        if ($label === '' || !$words) continue;
        $out[] = ['label' => mb_substr($label, 0, 80), 'words' => array_slice($words, 0, 40)];
    }
    return array_slice($out, 0, 20);
}

/**
 * Look at one answer.
 *
 * Matching is a case-insensitive substring on whitespace-collapsed text,
 * so "chain  rob" in the answer still matches "chain rob" in the rule and
 * a line break in the middle of a phrase does not hide it.
 */
function assist_eval(string $body, array $rules, int $minChars = 0): array
{
    $flat = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $body)));

    /* Paragraphs are blank-line separated. An answer typed as one block is
       one paragraph, which is a fact worth showing on a question that asks
       for two. */
    $paras = preg_split('/\R\s*\R/u', trim($body));
    $paras = array_values(array_filter(array_map('trim', $paras ?: []), function ($p) {
        return $p !== '';
    }));

    $longest = 0;
    foreach ($paras as $p) {
        $n = count(preg_split('/\s+/u', trim($p)) ?: []);
        if ($n > $longest) $longest = $n;
    }

    $checks = [];
    foreach ($rules as $r) {
        $hit = null;
        foreach ($r['words'] as $w) {
            if ($w !== '' && mb_strpos($flat, $w) !== false) { $hit = $w; break; }
        }
        $checks[] = ['label' => $r['label'], 'found' => $hit !== null, 'match' => $hit];
    }

    $chars = mb_strlen($flat);
    return [
        'stats' => [
            'chars'      => $chars,
            'min_chars'  => $minChars,
            'meets_min'  => $minChars <= 0 || $chars >= $minChars,
            'paragraphs' => max(count($paras), $flat === '' ? 0 : 1),
            'longest'    => $longest,
        ],
        'checks' => $checks,
        'found'  => count(array_filter($checks, function ($c) { return $c['found']; })),
        'total'  => count($checks),
    ];
}

/**
 * Attach an assist block to each answer that has criteria behind it.
 *
 * STAFF ONLY. Every caller passes answers it already decided the viewer may
 * see; this adds the lens on top, and api/application.php calls it inside
 * the same `if ($staff)` that guards the addresses and the history.
 */
function assist_attach(PDO $pdo, array $answers): array
{
    if (!$answers || !assist_available($pdo)) return $answers;

    $ids = array_values(array_unique(array_filter(array_map(function ($a) {
        return (int)($a['question_id'] ?? 0);
    }, $answers))));
    if (!$ids) return $answers;

    $in = implode(',', array_map('intval', $ids));
    $cfg = [];
    foreach ($pdo->query('SELECT id, assist, assist_rules FROM ucp_app_questions WHERE id IN (' . $in . ')')
                 ->fetchAll() as $q) {
        if (!(int)$q['assist']) continue;
        $rules = assist_rules($q['assist_rules']);
        if ($rules) $cfg[(int)$q['id']] = $rules;
    }
    if (!$cfg) return $answers;

    foreach ($answers as &$a) {
        $qid = (int)($a['question_id'] ?? 0);
        if (isset($cfg[$qid])) {
            $a['assist'] = assist_eval((string)($a['body'] ?? ''), $cfg[$qid],
                                       (int)($a['min_chars'] ?? 0));
        }
    }
    unset($a);
    return $answers;
}
