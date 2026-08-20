<?php
/**
 * The UCP shell — everything above a page's own content.
 *
 * ONE copy of the sidebar, the top bar, the credit box and the backdrop.
 * They used to be pasted into eighteen files, which is eighteen places to
 * forget when one of them changes — and exactly how /store ended up with
 * two credit boxes.
 *
 * Included, not built by JavaScript, on purpose: an element that arrives
 * after the first paint is a visible jump in the top bar on every load,
 * which is the flicker this markup was inlined to stop. Apache assembles
 * the page; the browser still receives complete HTML.
 *
 * A page sets, before including this:
 *
 *   $PAGE_TITLE    <title>, and the heading unless one is given
 *   $PAGE_HEADING  optional, for when the two differ
 *   $PAGE_HEAD     everything from the page's first <style> to </head>,
 *                  verbatim. It is raw rather than just a stylesheet
 *                  because the pages do not agree on the shape of that
 *                  region — the account lookup has two <style> blocks with
 *                  the tones.css link BETWEEN them, and that order is
 *                  load-bearing: what comes after tones.css overrides it.
 *                  Handing the whole region over unchanged is the only way
 *                  to share the shell without quietly reordering somebody's
 *                  cascade.
 *
 * Everything below a page's content stays in the page: each one has its own
 * scripts, and several have their own toast markup. Only the shell above is
 * shared, because only the shell above is identical.
 */
define('UCP_CSS_VERSION', '2.7.0');

if (!isset($PAGE_TITLE)) $PAGE_TITLE = 'BlaineSide UCP';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($PAGE_TITLE) ?></title>
  <!-- Every page of the UCP sits behind a sign-in and shows somebody's
       account, so none of it belongs in a search index. Only profile.html
       and the account lookup carried this before; the other sixteen were
       relying on the login redirect alone. -->
  <meta name="robots" content="noindex,nofollow">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='23' fill='%23121110'/%3E%3Ctext x='50' y='70' font-family='Oswald,sans-serif' font-size='60' font-weight='700' fill='%23e2b65c' text-anchor='middle'%3EB%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
<?= $PAGE_HEAD ?? '' ?>

<?php /* The global stylesheet, last on purpose: it overrides each page's own
         copy of a shared rule, which is how one file can restyle the whole
         UCP. The version lives here and nowhere else — bumping it here
         busts the cache for every page at once, instead of editing the
         same <link> in nineteen files. */ ?>
<link rel="stylesheet" href="/assets/css/tones.css?v=<?= UCP_CSS_VERSION ?>">
<body>

<!-- Backdrop. `stage` is the hook assets/js/ucp.js uses for the time-of-day
     tint; the sign-in page uses the same one. -->
<div class="bg-stage stage" aria-hidden="true">
  <div class="scene"></div>
  <div class="tod"></div>
  <div class="bg-scrim"></div>
</div>

  <aside class="sidebar">
    <div class="side-inner">
      <div class="side-brand"><span class="name">Blaine<b>Side</b></span></div>
      <nav class="side-scroll" id="nav"></nav>
    </div>
    <div class="side-foot">
      <div class="foot-line"><span class="fv" id="utcTime">00:00:00</span> UTC · <span id="utcDate">—</span></div>
      <div class="foot-line">UCP v2.4.1 · <span class="st"><span class="d"></span>All systems normal</span></div>
    </div>
  </aside>

  <div class="scrim" id="scrim"></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn hamburger" id="menuToggle" title="Menu" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div class="page-title"><h1 id="pageTitle"><?= htmlspecialchars($PAGE_HEADING ?? $PAGE_TITLE) ?></h1></div>
      <div class="spacer"></div>
<?php /* Off on My Profile and the account lookup, which never had one. */
   if ($PAGE_SEARCH ?? true): ?>
      <div class="searchbox">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
        <input placeholder="Quick search…">
      </div>
      <button class="icon-btn search-mini" aria-label="Search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg></button>
<?php endif; ?>
      <button class="icon-btn" title="Notifications" aria-label="Notifications"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/></svg><span class="dot"></span></button>
      <div class="divider"></div>
      <!-- Credit balance. In the markup, not created by JavaScript: an
           element that arrives after the first paint is a visible jump in
           the top bar on every single load. assets/js/ucp.js fills the
           figure in and still inserts the whole block on any page that
           does not carry it. -->
      <div class="creditbox">
        <span class="cmain">
          <svg class="cico" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/><path d="M12 7.2v9.6"/><path d="M15 9.4a3.6 3.6 0 1 0 0 5.2"/></svg>
          <span class="cnum unknown" id="creditValue">&mdash;</span>
        </span>
        <a class="cplus" href="/dashboard/store#credits" aria-label="Buy credits" title="Buy credits"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg></a>
      </div>
      <div class="account">
        <button class="account-btn" id="acctBtn">
          <svg class="acct-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/></svg>
          <span class="account-meta"><span class="u" id="acctName">&nbsp;</span><span class="r" id="acctRole">&nbsp;</span></span>
          <svg class="caret" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="account-menu" id="acctMenu">
          <div class="mhead">
            <div class="n" id="menuName">&nbsp;</div>
            <div class="rr" id="menuRole">&nbsp;</div>
          </div>
          <a class="menu-item" href="/profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M5 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/></svg>
            My Profile
          </a>
          <div class="menu-sep"></div>
          <a class="menu-item danger" id="logoutBtn" href="/api/logout.php?next=/login">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 17l-5-5 5-5M5 12h11"/></svg>
            Log out
          </a>
        </div>
      </div>

      <!-- First paint of the account block, from the same localStorage cache
           assets/js/ucp.js keeps ("bs_me"). ucp.js runs at the end of the
           body, so on a long page the browser paints the blank placeholders
           first and the name appears to flash in on every load. This runs
           while the header is still being parsed, so the name is already
           there when the row is first painted. ucp.js then repaints the same
           values a moment later, which the eye cannot see. -->
      <script>
      (function(){
        try{
          var m = JSON.parse(localStorage.getItem('bs_me') || 'null');
          if(!m) return;
          var e = document.documentElement;
          e.className = e.className.replace(/\bme-\d\b/g,'').trim();
          e.classList.add('me-' + ((typeof m.rank === 'number' && m.rank >= 0 && m.rank <= 9) ? (m.rank|0) : 0));
          var f = [['acctName', m.name], ['acctRole', m.role],
                   ['menuName', m.name], ['menuRole', m.role]];
          for(var i=0;i<f.length;i++){
            var el = document.getElementById(f[i][0]);
            if(el && f[i][1]) el.textContent = f[i][1];
          }
        }catch(err){}
      })();
      </script>
    </header>

    <main class="content">