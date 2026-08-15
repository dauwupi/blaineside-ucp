<?php
/**
 * BlaineSide UCP — request queues.
 *
 * Four things a player can send to staff, and the staff-side queue each one
 * lands in:
 *
 *   Refund Requests    submit / mine / panel
 *   Ban Appeals        submit        / panel
 *   Staff Reports      submit / mine / panel
 *   Asset Transfers    submit / mine / panel
 *
 * Ban Appeals and Staff Reports are built (see _appeals.php and
 * _reports.php). The other two are not yet, and
 * that is why this file exists first: it is the one place that says who may
 * open what, so the sidebar, the pages and the endpoints that eventually do
 * the work all read the same answer instead of each re-deciding it. When a
 * queue is built, the only change here is 'live' => true.
 *
 * Everyone can submit. What varies is who may open the staff panel:
 *
 *   Ban Appeals      Support Staff and above          — the widest, because
 *                    appeals are the highest-volume queue and Support exists
 *                    to work through volume.
 *   Refunds          Trainee Admin and above          — money and property
 *                    move as a result.
 *   Asset Transfers  Trainee Admin and above          — same reason.
 *   Staff Reports    Management, Founder, or anyone   — a report about a
 *                    holding Staff Management            staff member cannot
 *                                                        be visible to the
 *                                                        staff being reported
 *                                                        on, which is the same
 *                                                        rule Administrative
 *                                                        Search follows.
 *
 * Ranks are the ladder in _ranks.php: 0 Member, 1 Support Staff,
 * 2 Development Team, 3 Trainee Admin … 8 Management, 9 Founder.
 */

require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_teams.php';

const BS_QUEUE_APPEAL_RANK   = 1;  // Support Staff
const BS_QUEUE_REFUND_RANK   = 3;  // Trainee Admin
const BS_QUEUE_TRANSFER_RANK = 3;  // Trainee Admin
const BS_QUEUE_REPORT_RANK   = 8;  // Management (or the Staff Management team)

/**
 * The four areas, in sidebar order.
 *
 * Each view carries its own gate so a page can ask about one view rather
 * than about the area. Where 'live' is false, the page says so rather than
 * drawing an empty table that would read as "you have no requests".
 */
function queues_registry(): array
{
    return [
        'refunds' => [
            'label' => 'Refund Requests',
            'path'  => '/dashboard/refunds',
            'live'  => false,
            'why'   => 'Refund requests aren\'t switched on yet.',
            'views' => [
                'submit' => ['label' => 'Submit a Request', 'min' => 0],
                'mine'   => ['label' => 'My Requests',      'min' => 0],
                'panel'  => ['label' => 'Refund Request Panel', 'min' => BS_QUEUE_REFUND_RANK],
            ],
        ],
        'appeals' => [
            'label' => 'Ban Appeals',
            'path'  => '/dashboard/appeals',
            // Built. See _appeals.php, and dashboard/appeals.html + appeal.html.
            'live'  => true,
            'why'   => null,
            'views' => [
                'submit' => ['label' => 'Appeal your Punishment', 'min' => 0],
                'panel'  => ['label' => 'Ban Appeal Panel',       'min' => BS_QUEUE_APPEAL_RANK],
            ],
        ],
        'reports' => [
            'label' => 'Staff Reports',
            'path'  => '/dashboard/reports',
            // Built. See _reports.php, and dashboard/reports.html.
            'live'  => true,
            'why'   => null,
            'views' => [
                'submit' => ['label' => 'Submit a Staff Report', 'min' => 0],
                'mine'   => ['label' => 'My Staff Reports',      'min' => 0],
                'panel'  => ['label' => 'Staff Report Panel',
                             'min'   => BS_QUEUE_REPORT_RANK,
                             'team'  => 'staff_management'],
            ],
        ],
        'transfers' => [
            'label' => 'Asset Transfers',
            'path'  => '/dashboard/transfers',
            'live'  => false,
            'why'   => 'Asset transfers aren\'t switched on yet.',
            'views' => [
                'submit' => ['label' => 'Submit an Asset Transfer', 'min' => 0],
                'mine'   => ['label' => 'My Asset Transfers',       'min' => 0],
                'panel'  => ['label' => 'Asset Transfer Panel', 'min' => BS_QUEUE_TRANSFER_RANK],
            ],
        ],
    ];
}

/**
 * May this account open this view?
 *
 * 'team' is an OR, not an AND: a Staff Management holder gets the Staff
 * Report Panel at any administrator rank, exactly as they get staff profiles
 * in Administrative Search.
 */
function queue_may_view(array $view, int $rank, array $teams): bool
{
    if ($rank >= (int)($view['min'] ?? 0)) return true;
    $team = $view['team'] ?? null;
    return $team !== null && in_array($team, $teams, true);
}

/** Why they can't, in a sentence written to be shown to them. */
function queue_block_reason(array $view, string $label): string
{
    $min  = (int)($view['min'] ?? 0);
    $team = $view['team'] ?? null;
    $who  = rank_name($min) . ' and above';
    if ($team !== null) $who .= ', or anyone holding ' . team_label($team);
    return $label . ' is for ' . $who . '.';
}

/**
 * The whole registry, resolved for one account: every area, every view, each
 * marked with whether this person may open it and — when they may not — why.
 *
 * Returned in full rather than filtered. The sidebar hides what it shouldn't
 * show, but a page reached by typing the URL needs to be able to say "this is
 * for Trainee Admin and above" rather than 404.
 */
function queues_for(PDO $pdo, array $acc): array
{
    $rank  = (int)($acc['admin_rank'] ?? 0);
    $teams = function_exists('teams_for') ? teams_for($pdo, (int)$acc['id']) : [];
    $out   = [];

    foreach (queues_registry() as $key => $area) {
        $views = [];
        foreach ($area['views'] as $vk => $v) {
            $may = queue_may_view($v, $rank, $teams);
            $views[$vk] = [
                'key'   => $vk,
                'label' => $v['label'],
                'may'   => $may,
                'why'   => $may ? null : queue_block_reason($v, $v['label']),
            ];
        }
        $out[$key] = [
            'key'   => $key,
            'label' => $area['label'],
            'path'  => $area['path'],
            'live'  => $area['live'],
            'why'   => $area['why'],
            'views' => $views,
        ];
    }
    return $out;
}
