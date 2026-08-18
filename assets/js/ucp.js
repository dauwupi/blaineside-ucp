/* BlaineSide UCP — shared front-end helpers.
   Loaded by every auth page. Keeps API access, CSRF and the time-of-day
   backdrop in one place so the pages only contain their own behaviour. */
(function (w) {
  'use strict';

  /* Root-absolute so pages at any depth (/login, /dashboard/bulletin) hit the
     same endpoints. */
  var API = '/api/';
  var csrf = null;

  /* ---- CSRF -------------------------------------------------------------
     Fetched once per page load; every POST carries it as X-CSRF-Token. */
  function loadCsrf() {
    return fetch(API + 'csrf.php', { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.ok) csrf = d.token; return csrf; })
      .catch(function () { return null; });
  }

  /* POST JSON to an endpoint. Resolves { status, data } either way so callers
     can branch on HTTP status (401 wrong password, 429 locked). A stale
     CSRF token comes back as 403 with { csrf: true }. */
  function post(endpoint, body) {
    function send() {
      return fetch(API + endpoint, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf || ''
        },
        body: JSON.stringify(body || {})
      }).then(function (r) {
        return r.json().catch(function () { return {}; })
          .then(function (d) { return { status: r.status, data: d }; });
      });
    }
    return (csrf ? Promise.resolve() : loadCsrf()).then(send).then(function (res) {
      // A stale token (session recycled) — refresh once and retry silently.
      // Keyed on the body flag: the old check was res.status === 419, but
      // Apache rewrites that non-standard code to 500, so it never matched.
      if ((res.data && res.data.csrf) || res.status === 419) {
        return loadCsrf().then(send);
      }
      return res;
    });
  }

  function get(endpoint) {
    function send() {
      return fetch(API + endpoint, {
        credentials: 'include',
        headers: { 'X-CSRF-Token': csrf || '' }
      }).then(function (r) { return r.json(); });
    }
    // Some read endpoints (the email availability check) require the token
    // too, so make sure we have one before the first call.
    return (csrf ? Promise.resolve() : loadCsrf())
      .then(send)
      .then(function (d) {
        if (d && d.ok === false && d.csrf) { return loadCsrf().then(send); }
        return d;
      })
      .catch(function () { return { ok: false }; });
  }

  /* ---- Time-of-day backdrop --------------------------------------------
     Kept identical to the mockups: night / dawn / day / dusk, always dark.
     Uses the server hour when the page provides one, so the tint doesn't
     depend on the visitor's clock being correct. */
  function applyTod() {
    try {
      var attr = document.body && document.body.getAttribute('data-server-hour');
      var h = (attr !== null && attr !== '' && !isNaN(+attr))
        ? +attr
        : new Date().getUTCHours();
      var t = h >= 5 && h < 8 ? 'dawn' : h >= 8 && h < 17 ? 'day' : h >= 17 && h < 21 ? 'dusk' : 'night';
      var st = document.querySelector('.stage');
      if (st) {
        st.classList.remove('tod-night', 'tod-dawn', 'tod-day', 'tod-dusk');
        st.classList.add('tod-' + t);
      }
    } catch (e) {}
  }

  /* ---- Footer clock ----------------------------------------------------- */
  function startClock() {
    var el = document.querySelector('.clock');
    if (!el) return;
    function tick() {
      var d = new Date();
      var hh = String(d.getUTCHours()).padStart(2, '0');
      var mm = String(d.getUTCMinutes()).padStart(2, '0');
      el.innerHTML = '<i class="fa fa-clock-o"></i> UTC ' + hh + ':' + mm;
    }
    tick();
    setInterval(tick, 30000);
  }

  /* ---- Sign in <-> Create UCP cross-fade --------------------------------
     Moves the active underline to the clicked tab, fades the whole centre
     column out, then navigates. The next page fades its own column in, so
     the two halves join into one transition. */
  function initTabFade() {
    var stage = document.querySelector('.stage');
    var tabs  = document.querySelector('.tabs');
    if (!stage || !tabs) return;
    var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var col = stage.querySelector('.center');

    /* Hand control of opacity back to CSS transitions once the entry
       animation has finished, so the exit fade can actually ease. */
    function releaseAnim() { if (col) col.classList.add('bs-anim-done'); }
    if (col) col.addEventListener('animationend', releaseAnim);
    setTimeout(releaseAnim, 600);

    tabs.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('a.tab') : null;
      if (!link || reduce) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;

      e.preventDefault();
      var current = tabs.querySelector('.tab.on');
      if (current) current.classList.remove('on');
      link.classList.add('on');
      // Make sure the entry animation is gone before the exit starts,
      // then force a reflow so the browser sees a real value change.
      releaseAnim();
      if (col) void col.offsetWidth;
      stage.classList.add('is-leaving');

      var done = false;
      var go = function () { if (done) return; done = true; w.location = link.getAttribute('href'); };
      if (col) {
        col.addEventListener('transitionend', function h(ev) {
          if (ev.propertyName !== 'opacity') return;
          col.removeEventListener('transitionend', h); go();
        });
      }
      // Never let a missed transitionend strand the user on a faded page.
      setTimeout(go, 340);
    });

    // Coming back via the back button restores the faded-out page from
    // bfcache — clear the state so it isn't left invisible.
    w.addEventListener('pageshow', function (e) {
      if (e.persisted) stage.classList.remove('is-leaving');
    });
  }

  /* ---- Misc helpers ----------------------------------------------------- */
  function esc(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /** "3 hours ago" / "yesterday" / "2 days ago" from a unix timestamp. */
  function relTime(ts) {
    var s = Math.floor(Date.now() / 1000) - ts;
    if (s < 60) return 'just now';
    if (s < 3600) { var m = Math.floor(s / 60); return m + (m === 1 ? ' minute ago' : ' minutes ago'); }
    if (s < 86400) { var h = Math.floor(s / 3600); return h + (h === 1 ? ' hour ago' : ' hours ago'); }
    var d = Math.floor(s / 86400);
    if (d === 1) return 'yesterday';
    if (d < 30) return d + ' days ago';
    var mo = Math.floor(d / 30);
    return mo + (mo === 1 ? ' month ago' : ' months ago');
  }

  function readCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  }

  function fmtSecs(s) {
    var m = Math.floor(s / 60), r = s % 60;
    return m ? (m + 'm ' + (r < 10 ? '0' : '') + r + 's') : (r + 's');
  }

  /* =====================================================================
     WHO IS SIGNED IN

     Every page needs the same three facts before it can draw its own
     chrome: the name, the group name, and the rank the menu is gated on.
     They come from api/session.php, which is a round trip — so on every
     navigation the sidebar first drew with no Administration section, the
     account button drew blank, and both then jumped when the answer came
     back. That double-draw is the flicker.

     So the answer is kept. It is the player's own name and their own
     group — not a secret, and not a permission: the pages and the
     endpoints check the rank with the server on every request regardless.
     All this does is let the FIRST paint be right instead of empty, and
     session.php still corrects it a moment later if anything changed.

     Cleared on sign-out, so the next person at the same computer starts
     from nothing.
     ===================================================================== */
  var ME_KEY = 'bs_me';

  function meRead() {
    try {
      var m = JSON.parse(w.localStorage.getItem(ME_KEY) || 'null');
      if (!m || typeof m.rank !== 'number') return null;
      // Written before teams existed — treat as none rather than undefined,
      // so callers can index it without guarding every use.
      if (!Array.isArray(m.teams)) m.teams = [];
      return m;
    } catch (e) { return null; }
  }
  function meWrite(m) {
    try {
      w.localStorage.setItem(ME_KEY, JSON.stringify({
        name: String(m.name || ''), role: String(m.role || 'Member'), rank: m.rank | 0,
        /* Sub-group keys. The sidebar needs them: the Staff Report Panel is
           open to Staff Management holders at any administrator rank, so a
           menu drawn from rank alone would be wrong for exactly the people
           the sub-group exists for. */
        teams: Array.isArray(m.teams) ? m.teams.map(String) : [],
        /* The balance, so the top bar paints the right number on the FIRST
           frame. It was left out of this whitelist, which meant every load
           drew 0, then the real figure a moment later when session.php
           answered — a visible flicker, and a width change that shoved the
           search box and the account button sideways with it.

           A stale balance for one frame is fine; it is corrected by the
           same request that always corrected it. A wrong balance of 0 for
           one frame is not, because 0 is a number somebody might believe. */
        credits: typeof m.credits === 'number' ? m.credits : undefined
      }));
    } catch (e) { /* private mode, quota — the page still works, it just blinks */ }
  }
  function forgetMe() {
    try { w.localStorage.removeItem(ME_KEY); } catch (e) {}
  }

  /**
   * Records a group on <html> as a me-* class.
   *
   * assets/css/tones.css turns that into --me and --me-text, which is how
   * the role line in the account button gets its tier colour.
   */
  function setTone(rank) {
    var r = (typeof rank === 'number' && rank >= 0 && rank <= 9) ? (rank | 0) : 0;
    var el = document.documentElement;
    el.className = el.className.replace(/\bme-\d\b/g, '').trim();
    el.classList.add('me-' + r);
  }

  /** Paints a name/role/rank into the topbar and the account menu. */
  function paintMe(m) {
    if (!m) return;
    setTone(m.rank | 0);
    var ids = [['acctName', m.name], ['acctRole', m.role],
               ['menuName', m.name], ['menuRole', m.role]];
    for (var i = 0; i < ids.length; i++) {
      var el = document.getElementById(ids[i][0]);
      if (el && ids[i][1]) el.textContent = ids[i][1];
    }
  }

  /**
   * Call with whatever api/session.php (or profile.php) returned. Paints it
   * and keeps it for the next page load.
   */
  function rememberMe(d) {
    if (!d || typeof d.rank !== 'number') return;
    var m = { name: d.name || '', role: d.role || 'Member', rank: d.rank | 0,
              teams: Array.isArray(d.teams) ? d.teams.map(String) : [] };
    /* Carried through only when the server actually sends it. Writing 0
       into the cache for a UCP that has no ledger would then paint 0 on
       the next page before the session answers, which is a number nobody
       asked for. */
    if (typeof d.credits === 'number') {
      m.credits = d.credits;
    } else {
      /* Carry the last known balance forward.
         Not every endpoint that describes the caller reports credits —
         api/profile.php does not — and My Profile hands its payload
         straight to rememberMe(). Replacing the cache wholesale therefore
         dropped a balance we already knew and repainted the top bar as an
         em dash. A figure this page did not mention is a figure this page
         has nothing to say about, not a figure that has gone away. */
      var known = meRead();
      if (known && typeof known.credits === 'number') m.credits = known.credits;
    }
    meWrite(m);
    paintMe(m);
    paintCredits(m);
    initQuickSearch(m.rank);
    renderNav();               // the rank may have just changed the menu
  }

  /* The tone can go on before the document body exists, and should: it is
     what stops the role line flashing amber before it turns green. */
  var CACHED = meRead();
  if (CACHED) setTone(CACHED.rank);

  /* =====================================================================
     QUICK SEARCH  —  the box in the top bar

     Self-contained on purpose: markup, styles and behaviour all live here,
     so any page with a .searchbox gets a working quick search without
     copying a line into it. That is the same reason the tier colours moved
     to tones.css — five copies of a component is five things to forget when
     one of them changes.

     Trainee Admin and above. Below that the box is removed rather than
     disabled: a search field you can type into that never answers is worse
     than no search field, and everyone below that rank has nothing to search.
     The endpoint checks the rank too — this only decides what is drawn.
     ===================================================================== */
  var QS_CSS = [
    '.searchbox{position:relative}',
    /* Every page styles `input:focus` with the amber ring meant for form
       fields. The top-bar search is chrome, not a form — the ring drew an
       orange box around the whole thing. Cancelled here, where the widget
       lives, rather than in five page stylesheets. */
    '.searchbox input:focus{outline:none!important;box-shadow:none!important;border-color:transparent!important}',
    '.searchbox:focus-within{border-color:var(--charcoal-4,#2b2723)}',
    '.qs-menu{position:absolute;left:0;right:0;top:calc(100% + 8px);z-index:70;padding:6px;',
      'background:var(--charcoal-2,#1a1815);border:1px solid var(--border,#26221e);border-radius:13px;',
      'box-shadow:0 26px 54px -20px rgba(0,0,0,.85);max-height:min(60vh,420px);overflow-y:auto}',
    '.qs-menu[hidden]{display:none}',
    '.qs-item{display:flex;align-items:center;gap:11px;width:100%;padding:9px 11px;border-radius:9px;',
      'background:none;border:none;cursor:pointer;font-family:inherit;text-align:left;transition:.12s}',
    '.qs-item:hover,.qs-item.on{background:var(--charcoal-3,#221f1b)}',
    '.qs-item .qi{width:30px;height:30px;flex:none;display:grid;place-items:center;border-radius:9px;',
      'background:var(--charcoal,#121110);border:1px solid var(--border,#26221e);color:var(--text-dim,#655e51)}',
    '.qs-item .qi svg{width:14px;height:14px;stroke-width:2}',
    '.qs-item .qb{flex:1;min-width:0}',
    '.qs-item .qn{display:block;font-size:13.5px;font-weight:700;color:var(--parchment,#f1efe9);',
      'white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.qs-item .qsub{display:block;font-size:11.5px;color:var(--text-dim,#655e51);margin-top:2px;',
      'white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.qs-item .qid{flex:none;font-size:11px;font-weight:700;color:var(--stone,#8a7f70);font-variant-numeric:tabular-nums}',
    '.qs-item.locked{cursor:not-allowed}',
    '.qs-item.locked .qn{color:var(--text-faint,#968e7e)}',
    '.qs-item.locked:hover{background:none}',
    '.qs-note{padding:10px 12px;font-size:11.5px;color:var(--text-dim,#655e51);line-height:1.5}',
    '.qs-note b{color:var(--text-faint,#968e7e);font-weight:700}',
    '.qs-sep{height:1px;background:var(--border-soft,#1f1c18);margin:5px 8px}'
  ].join('');

  var QS_ICON = {
    account: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M5 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/></svg>',
    locked:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>'
  };

  function initQuickSearch(rank) {
    var box = document.querySelector('.searchbox');
    if (!box) return;

    var input = box.querySelector('input');
    if (!input) return;

    // Below Trainee Admin there is nothing here for them.
    if ((rank | 0) < 3) { box.style.display = 'none'; return; }
    box.style.display = '';
    if (box.getAttribute('data-qs') === 'on') return;   // already wired
    box.setAttribute('data-qs', 'on');

    if (!document.getElementById('qs-style')) {
      var st = document.createElement('style');
      st.id = 'qs-style';
      st.textContent = QS_CSS;
      document.head.appendChild(st);
    }

    input.placeholder = 'Quick search…';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('spellcheck', 'false');

    var menu = document.createElement('div');
    menu.className = 'qs-menu';
    menu.hidden = true;
    box.appendChild(menu);

    var items = [], cursor = -1, timer = null, seq = 0;

    function close() { menu.hidden = true; cursor = -1; }
    function open()  { menu.hidden = false; }

    function draw(d) {
      items = (d.results || []).filter(function (r) { return r.viewable; });
      var html = (d.results || []).map(function (r) {
        var idx = items.indexOf(r);
        return '<button type="button" class="qs-item' + (r.viewable ? '' : ' locked') + '"' +
          (r.viewable ? ' data-go="' + r.id + '" data-i="' + idx + '"' : ' disabled') + '>' +
          '<span class="qi">' + (r.viewable ? QS_ICON.account : QS_ICON.locked) + '</span>' +
          '<span class="qb"><span class="qn">' + esc(r.name) + '</span>' +
            '<span class="qsub">' + esc(r.sub || '') + '</span></span>' +
          '<span class="qid">#' + r.id + '</span>' +
        '</button>';
      }).join('');

      if (!html) {
        html = '<div class="qs-note">Nothing matches <b>' + esc(d.q) + '</b>.</div>';
      } else if (d.more > 0) {
        html += '<div class="qs-sep"></div><div class="qs-note">' + d.more +
                ' more match — open <b>Administrative Search</b> to narrow it down.</div>';
      }

      /* Says what this box cannot find yet. Somebody typing a plate and
         getting nothing would otherwise conclude the vehicle isn't
         registered, rather than that vehicles aren't in the UCP. */
      if (d.pending && d.pending.length) {
        html += '<div class="qs-sep"></div><div class="qs-note">' +
                d.pending.join(', ') + ' will be searchable here once the game server is linked.</div>';
      }

      menu.innerHTML = html;
      open();
    }

    function ask() {
      var q = input.value.trim();
      if (q.length < 2) { close(); return; }
      var mine = ++seq;
      get('admin-quick.php?q=' + encodeURIComponent(q)).then(function (d) {
        if (mine !== seq) return;            // a later keystroke already won
        if (!d || d.ok !== true) { close(); return; }
        draw(d);
      }).catch(function () { close(); });
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(ask, 220);
    });
    input.addEventListener('focus', function () {
      if (menu.innerHTML && input.value.trim().length >= 2) open();
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { close(); input.blur(); return; }
      if (menu.hidden || !items.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        cursor += (e.key === 'ArrowDown' ? 1 : -1);
        if (cursor < 0) cursor = items.length - 1;
        if (cursor >= items.length) cursor = 0;
        var all = menu.querySelectorAll('.qs-item[data-i]');
        for (var i = 0; i < all.length; i++) all[i].classList.toggle('on', +all[i].dataset.i === cursor);
      } else if (e.key === 'Enter' && cursor > -1) {
        e.preventDefault();
        window.location.href = '/dashboard/lookup?id=' + items[cursor].id;
      }
    });

    menu.addEventListener('mousedown', function (e) {
      var b = e.target.closest('[data-go]');
      if (!b) return;
      e.preventDefault();                     // before the input loses focus
      window.location.href = '/dashboard/lookup?id=' + b.dataset.go;
    });

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.searchbox')) close();
    });
  }


  /* =====================================================================
     THE SIDEBAR

     One copy, here, for every page.

     It used to be a `const SIDEBAR = [...]`, an ICONS map, navMay() and
     renderSidebar() pasted into all eleven pages. Eleven copies is eleven
     things to forget when one of them changes — which is exactly what
     happened: Staff Reports gained a view on one page and kept the old
     sub-menu on the other ten. Adding a menu item is now an edit to NAV
     below and nothing else.

     Any page with a `<nav id="nav">` gets the menu. It is drawn on load
     from the cached rank so the first paint is right, and again the moment
     rememberMe() hears from api/session.php — that second pass is what
     stops the Administration section flickering in.

     Nothing here is a permission. It decides what is DRAWN; every page
     behind a link asks the server, and every endpoint checks again.
     ===================================================================== */
  var NAV = [
    {label:'Dashboard',icon:'home',href:'/dashboard'},
    {label:'Characters',icon:'user',children:[
      {label:'Empty character slot',empty:true},
      {label:'Empty character slot',empty:true},
      {label:'Empty character slot',empty:true}]},
    {heading:'Community'},
    {label:'Forums',icon:'chat',children:[
      {label:'Forums',href:'#'},
      {label:'Community Rules',href:'#'},
      {label:'Development Changelog',href:'#'}]},
    {label:'Factions',icon:'briefcase',children:[
      {label:'Legal factions',href:'#'},
      {label:'Illegal factions',href:'#'},
      {label:'Applications',href:'#'}]},
    {label:'Properties',icon:'house',children:[
      {label:'My properties',href:'#'},
      {label:'Businesses',href:'#'},
      {label:'Listings',href:'#'}]},
    /* min:1, not admins:true. Support Staff sit at rank 1 and their panel
       lives in this section; a heading gated at rank 3 would hide the
       section from exactly the people who use it most. */
    {heading:'Administration',min:1},
    /* Founder-only. Managers reach Group Management through the Management
       group below instead — same page, fewer powers, enforced server-side. */
    {label:'Founder',icon:'crown',founder:true,children:[
      {label:'Group Management',href:'/dashboard/groups'}]},
    /* One button, not five children. Every Management tool now lives on
       /dashboard/management, which means adding the sixth is a tile on
       that page rather than another line in a menu everybody scrolls
       past — and the list of tools stops being duplicated here. */
    {label:'Management',icon:'shield',admin:true,href:'/dashboard/management'},
    /* Trainee Admin and above — the whole admin ladder, not just Management.
       See BS_ADMIN_MIN_RANK in api/_admin.php, which is what decides. */
    {label:'Administrators',icon:'search',admins:true,children:[
      {label:'Administrative Search',href:'/dashboard/search'}]},
    /* Support Staff and above. The application queue is the work rank 1
       exists to do, so it is a rank gate rather than a sub-group — see
       BS_APP_PANEL_RANK in api/_applications.php, which is what decides. */
    {label:'Support Staff',icon:'lifebuoy',min:1,children:[
      {label:'Application Panel',href:'/dashboard/applications'}]},
    {heading:'Reports, Appeals & Refunds'},
    /* Everyone submits; who may open a PANEL is the only thing that varies,
       and it is `min` (a rank) OR `team` (a sub-group that opens it at any
       rank). Mirrors api/_queues.php, which is what actually enforces it. */
    {label:'Refund Requests',icon:'coin',children:[
      {label:'Submit a Request',href:'/dashboard/refunds#submit'},
      {label:'My Requests',href:'/dashboard/refunds#mine'},
      {label:'Refund Request Panel',href:'/dashboard/refunds#panel',min:3}]},
    /* One page decides which view you get, so these are single buttons —
       a sub-menu would be two clicks to the same place, and a second copy
       of the panel's gate to keep in step. */
    {label:'Ban Appeals',icon:'gavel',href:'/dashboard/appeals'},
    {label:'Staff Reports',icon:'flag',href:'/dashboard/reports'},
    {label:'Asset Transfers',icon:'swap',children:[
      {label:'Submit an Asset Transfer',href:'/dashboard/transfers#submit'},
      {label:'My Asset Transfers',href:'/dashboard/transfers#mine'},
      {label:'Asset Transfer Panel',href:'/dashboard/transfers#panel',min:3}]},
    {heading:'Account'},
    /* My Profile is a parent now: the account page and the player's own
       application are two different places, and the application is the one
       a new player is looking for. Everyone sees it — passing an
       application is not a permission, it is a state. */
    {label:'My Profile',icon:'user',children:[
      {label:'My Account',href:'/profile'},
      {label:'Application',href:'/dashboard/application'}]},
    {label:'Credit Store',icon:'coin',href:'/dashboard/store'},
    {label:'XM Radio',icon:'radio',href:'#'}
  ];

  var NAV_ICONS = {
    crown:'<path d="M4 18h16M4 18l-1.5-9 5 3.5L12 5l4.5 7.5 5-3.5L20 18"/>',
    shield:'<path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/>',
    home:'<path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
    user:'<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
    chat:'<path d="M4 5h16v11H8l-4 3z"/>',
    briefcase:'<path d="M4 7h16v13H4z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
    house:'<path d="M3 10.5L12 4l9 6.5V20H3zM9 20v-6h6v6"/>',
    doc:'<path d="M4 5h16v14H4z"/><path d="M4 9h16M8 5v14"/>',
    clock:'<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
    radio:'<path d="M8 6l10 6-10 6z"/><path d="M4 5v14"/>',
    coin:'<circle cx="12" cy="12" r="8"/><path d="M12 8v8M9.5 9.8h4a1.7 1.7 0 0 1 0 3.4h-3a1.7 1.7 0 0 0 0 3.4h4"/>',
    gavel:'<path d="M3 21h8"/><path d="M6.5 17.5l7-7"/><path d="M11 4l6 6-2.5 2.5-6-6z"/><path d="M15 14l4.5 4.5"/>',
    flag:'<path d="M5 21V4h13l-2.5 4L18 12H5"/>',
    swap:'<path d="M4 8h13l-3-3M20 16H7l3 3"/>',
    chev:'<path d="M9 6l6 6-6 6"/>',
    search:'<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
    lifebuoy:'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.6"/>' +
             '<path d="M5.6 5.6l3.9 3.9M14.5 14.5l3.9 3.9M18.4 5.6l-3.9 3.9M9.5 14.5l-3.9 3.9"/>'
  };
  function navIcon(n, c){
    return '<svg class="' + c + '" viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
           (NAV_ICONS[n] || '') + '</svg>';
  }

  /* `min` is a rank on the ladder in api/_ranks.php. `team` is a sub-group
     key that opens the item on its own at ANY rank — which is how a Staff
     Management holder reaches the Staff Report Panel without being
     Management. A menu drawn from rank alone would be wrong for exactly the
     people the sub-group exists for. */
  function navMayItem(x, rank, teams){
    if (!x) return true;
    if (x.admin && rank < 8) return false;
    if (x.founder && rank < 9) return false;
    if (x.admins && rank < 3) return false;
    if (x.notFounder && rank >= 9) return false;
    if (typeof x.min === 'number' && rank < x.min &&
        !(x.team && teams.indexOf(x.team) > -1)) return false;
    return true;
  }

  var navDrawn = '';        // what the menu was last drawn for

  function renderNav(items){
    var host = document.getElementById('nav');
    if (!host) return;

    var me = meRead() || {};
    var rank = me.rank | 0;
    var teams = Array.isArray(me.teams) ? me.teams : [];
    items = items || w.SIDEBAR || NAV;

    /* Redrawing an unchanged menu closes every group the reader had open,
       which is what made the sub-menus snap shut a second after landing.

       The signature used to be the rank and the sub-groups. That is not
       enough: the first paint runs on the CACHED rank and the second on
       whatever api/session.php answers, and when the cache is empty — a
       first visit, or a browser that has just been cleared — those two
       differ even though the menu they produce is usually identical. The
       result was a visible redraw a beat after landing on every page.

       So the signature is now the MARKUP. Build it, compare it, and only
       touch the DOM when it actually differs. When it does differ — the
       Administration section appearing for staff — the new menu fades in
       rather than snapping. */
    var html = items.filter(function (it) {
      return navMayItem(it, rank, teams);
    }).map(function (it) {
      if (it.heading) return '<div class="nav-heading">' + esc(it.heading) + '</div>';
      if (it.children) {
        var subs = it.children.filter(function (c) {
          return navMayItem(c, rank, teams);
        }).map(function (c) {
          return c.empty
            ? '<a class="slot-empty">' + esc(c.label) + '</a>'
            : '<a href="' + esc(c.href || '#') + '">' + esc(c.label) + '</a>';
        }).join('');
        return '<div class="nav-group" data-collapsible><div class="nav-item">' +
          navIcon(it.icon, 'i') + '<span class="lbl">' + esc(it.label) + '</span>' +
          navIcon('chev', 'chev') + '</div><div class="sub">' + subs + '</div></div>';
      }
      var tag = it.href ? 'a' : 'div', attr = it.href ? ' href="' + esc(it.href) + '"' : '';
      return '<div class="nav-group"><' + tag + ' class="nav-item"' + attr + '>' +
        navIcon(it.icon, 'i') + '<span class="lbl">' + esc(it.label) + '</span></' + tag + '>' +
        '</div>';
    }).join('');

    if (html === navDrawn && host.children.length) return;

    /* Only the SECOND and later paints fade. The first one is the page
       arriving, and fading it in on top of everything else arriving reads
       as the page being slow rather than as a transition. */
    var again = navDrawn !== '';
    navDrawn = html;
    host.innerHTML = html;
    if (again) {
      host.classList.remove('bs-fade');
      void host.offsetWidth;      // restart the animation
      host.classList.add('bs-fade');
    }

    /* The current page's own entry, marked. Every page used to do this by
       hand or not at all. */
    var here = location.pathname.replace(/\/$/, '') || '/dashboard';
    Array.prototype.forEach.call(host.querySelectorAll('a.nav-item, .sub a'), function (a) {
      var h = (a.getAttribute('href') || '').split('#')[0].replace(/\/$/, '');
      if (h && h === here) {
        var item = a.classList.contains('nav-item') ? a : a.closest('.nav-group');
        if (item) {
          (a.classList.contains('nav-item') ? a : a).classList.add('active');
          if (!a.classList.contains('nav-item')) item.classList.add('open');
        }
      }
    });

    Array.prototype.forEach.call(host.querySelectorAll('.nav-group[data-collapsible] > .nav-item'),
      function (item) {
        item.addEventListener('click', function () {
          item.parentElement.classList.toggle('open');
        });
      });
  }


  /* =====================================================================
     NOTIFICATIONS — the bell in the top bar

     Self-contained on purpose, like the quick search above it: markup,
     styles and behaviour all live here, so every page with a bell button
     gets a working panel without a line copied into it. Eleven copies of a
     component is eleven things to forget when one of them changes.

     What it does NOT do is invent urgency. There is no sound, no toast, no
     red badge for something read a week ago. The dot appears when there is
     something unread and goes away when there isn't, and the count is
     collapsed server-side so one conversation is one notification rather
     than fourteen.
     ===================================================================== */
  var NOTE_CSS = [
    '.bellwrap{position:relative;display:inline-flex}',
    '.bellwrap .icon-btn .dot{display:none}',
    '.bellwrap.has .icon-btn .dot{display:block}',
    '.notepanel{position:absolute;top:calc(100% + 10px);right:0;width:370px;max-width:88vw;',
      'background:var(--charcoal-2,#1a1815);border:1px solid var(--border,#26221e);',
      'border-radius:13px;box-shadow:0 26px 60px -24px rgba(0,0,0,.85);z-index:80;',
      'overflow:hidden;display:none}',
    '.notepanel.open{display:block}',
    '.notepanel .nh{display:flex;align-items:center;gap:10px;padding:13px 15px;',
      'border-bottom:1px solid var(--rule,#302b25)}',
    '.notepanel .nh b{font-size:13.5px;font-weight:700;color:var(--parchment,#f1efe9)}',
    '.notepanel .nh .n{font-size:11px;font-weight:800;padding:2px 8px;border-radius:100px;',
      'color:#e3bd72;background:rgba(226,182,92,.12);border:1px solid rgba(226,182,92,.3)}',
    '.notepanel .nh button{margin-left:auto;border:0;background:none;font-family:inherit;',
      'font-size:12px;font-weight:600;color:var(--text-faint,#968e7e);cursor:pointer}',
    '.notepanel .nh button:hover{color:var(--gold,#e2b65c)}',
    '.notelist{max-height:400px;overflow:auto}',
    '.noterow{display:flex;gap:12px;padding:12px 15px;border-bottom:1px solid var(--rule,#302b25);',
      'cursor:pointer;transition:.13s}',
    '.noterow:last-child{border-bottom:0}',
    '.noterow:hover{background:var(--charcoal-3,#221f1b)}',
    '.noterow .i{flex:none;width:30px;height:30px;display:grid;place-items:center;',
      'border-radius:9px;background:var(--charcoal-3,#221f1b);border:1px solid var(--border,#26221e);',
      'color:var(--text-dim,#655e51)}',
    '.noterow.new .i{color:#e3bd72;background:rgba(226,182,92,.1);',
      'border-color:rgba(226,182,92,.28)}',
    '.noterow .i svg{width:15px;height:15px;stroke-width:2;fill:none;stroke:currentColor}',
    '.noterow .b{min-width:0;flex:1}',
    '.noterow .t{font-size:12.5px;font-weight:600;color:var(--text-faint,#968e7e);',
      'line-height:1.45}',
    '.noterow.new .t{color:var(--parchment,#f1efe9);font-weight:700}',
    '.noterow .s{font-size:11.5px;color:var(--text-dim,#655e51);line-height:1.5;margin-top:3px;',
      'overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}',
    '.noterow .w{font-size:11px;color:var(--text-dim,#655e51);margin-top:4px}',
    '.notenone{padding:26px 18px;text-align:center;font-size:12.5px;',
      'color:var(--text-dim,#655e51);line-height:1.6}',
    '.notefoot{display:block;text-align:center;padding:12px;font-size:12.5px;font-weight:600;',
      'color:var(--text-faint,#968e7e);border-top:1px solid var(--rule,#302b25);',
      'background:var(--charcoal,#121110)}',
    '.notefoot:hover{color:var(--gold,#e2b65c)}'
  ].join('');

  var NOTE_ICONS = {
    appeal:'<path d="M3 21h8"/><path d="M6.5 17.5l7-7"/><path d="M11 4l6 6-2.5 2.5-6-6z"/>' +
           '<path d="M15 14l4.5 4.5"/>',
    report:'<path d="M5 21V4h13l-2.5 4L18 12H5"/>',
    application:'<path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6M9 17h3"/>',
    system:'<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>'
  };

  var noteOpen = false, noteTimer = null, noteWired = false;

  function noteBell() {
    return document.querySelector('.icon-btn[aria-label="Notifications"], ' +
                                  '.icon-btn[title="Notifications"]');
  }

  function noteRender(d) {
    var panel = document.getElementById('notepanel');
    var wrap = panel && panel.parentNode;
    if (!panel) return;

    var list = (d && d.notifications) || [];
    var unread = (d && d.unread) | 0;

    if (wrap) wrap.classList.toggle('has', unread > 0);

    panel.querySelector('.nh .n').textContent = unread;
    panel.querySelector('.nh .n').style.display = unread ? '' : 'none';

    panel.querySelector('.notelist').innerHTML = list.length
      ? list.map(function (n) {
          var icon = NOTE_ICONS[n.area] || NOTE_ICONS.system;
          return '<div class="noterow' + (n.read ? '' : ' new') + '" data-id="' + n.id + '"' +
            (n.url ? ' data-url="' + esc(n.url) + '"' : '') + '>' +
            '<span class="i"><svg viewBox="0 0 24 24">' + icon + '</svg></span>' +
            '<span class="b">' +
              '<div class="t">' + esc(n.title) + '</div>' +
              (n.body ? '<div class="s">' + esc(n.body) + '</div>' : '') +
              '<div class="w">' + esc(relTime(n.at)) +
                (n.actor ? ' · ' + esc(n.actor) : '') + '</div>' +
            '</span></div>';
        }).join('')
      /* No list of what can appear here — this is the whole UCP's
         notification area, not the appeals one. */
      : '<div class="notenone">Nothing yet.<br>Anything that needs your attention ' +
        'turns up here.</div>';
  }

  function noteFetch() {
    return get('notifications.php').then(function (d) {
      if (d && d.ok === true) noteRender(d);
      return d;
    });
  }

  function initNotifications() {
    var bell = noteBell();
    if (!bell || noteWired) return;
    noteWired = true;

    if (!document.getElementById('note-style')) {
      var st = document.createElement('style');
      st.id = 'note-style';
      st.textContent = NOTE_CSS;
      document.head.appendChild(st);
    }

    /* The button is wrapped rather than replaced, so a page that styles its
       own top bar keeps whatever it did to the button. */
    var wrap = document.createElement('span');
    wrap.className = 'bellwrap';
    bell.parentNode.insertBefore(wrap, bell);
    wrap.appendChild(bell);

    var panel = document.createElement('div');
    panel.className = 'notepanel';
    panel.id = 'notepanel';
    panel.innerHTML =
      '<div class="nh"><b>Notifications</b><span class="n" style="display:none">0</span>' +
      '<button type="button" data-readall>Mark all read</button></div>' +
      '<div class="notelist"></div>' +
      /* Everything, filterable and paged, lives on the dashboard. The panel
         holds the recent ones; this is the way to the rest. */
      '<a class="notefoot" href="/dashboard/notifications">View all notifications</a>';
    wrap.appendChild(panel);

    bell.addEventListener('click', function (e) {
      e.stopPropagation();
      noteOpen = !noteOpen;
      panel.classList.toggle('open', noteOpen);
      if (noteOpen) noteFetch();
    });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () {
      noteOpen = false; panel.classList.remove('open');
    });

    panel.querySelector('[data-readall]').addEventListener('click', function () {
      post('notification-read.php', { all: true }).then(noteFetch);
    });

    /* Opening one marks it read and goes where it points. Marking it read
       first and navigating second means a slow request cannot leave the
       badge lit for something the reader has plainly seen. */
    panel.querySelector('.notelist').addEventListener('click', function (e) {
      var row = e.target.closest('.noterow');
      if (!row) return;
      var url = row.getAttribute('data-url');
      row.classList.remove('new');
      post('notification-read.php', { id: +row.getAttribute('data-id') })
        .then(function () { if (url) window.location.href = url; else noteFetch(); })
        .catch(function () { if (url) window.location.href = url; });
    });

    noteFetch();
    /* Every 90 seconds. Often enough that a reply arrives while the tab is
       open, rare enough that a dashboard left up all day is not a load. */
    if (noteTimer) clearInterval(noteTimer);
    noteTimer = setInterval(function () {
      if (!document.hidden) noteFetch();
    }, 90000);
  }


  /* =====================================================================
     THE SIDEBAR FOOTER — the clock, the build number, the status line

     Here for the same reason the menu is: it was identical markup in
     twelve files, and one of them said a different version number than the
     rest within a week. `UCP_VERSION` below is the only place the build
     number is written down.

     The pages keep the markup as a fallback for a browser that never runs
     this; what is drawn here replaces it.
     ===================================================================== */

  /* =====================================================================
     THE CREDIT BALANCE

     Sits immediately before the account button in the top bar, on every
     page, drawn from here rather than pasted into fourteen files.

     Nothing else in the bar moves: the search box and the bell keep their
     places and the account block shifts left by exactly the width of this
     one.

     There is no credit ledger yet, so the balance reads 0 and comes from
     whatever api/session.php reports. When a ledger exists, session.php
     gains a `credits` number and this lights up with no change here.
     ===================================================================== */
  /* In full to 9,999, then abbreviated from 10,0K. Never wider than five
     characters, so the box cannot change width as somebody spends.
     Truncated rather than rounded: a balance that reads higher than it is
     is the one error nobody forgives. */
  function creditFormat(n){
    n = Math.max(0, Math.floor(Number(n) || 0));
    if (n < 10000) return n.toLocaleString('en-US');
    var unit = 'K', div = 1000;
    if (n >= 1000000) { unit = 'M'; div = 1000000; }
    /* One integer division, then split the digits — NOT floor((v - whole)
       * 10). 1400000 / 1000000 is 1.4000000000000001 in binary floating
       point, and that expression turned 1,4M into 1,3M. Tenths are counted
       here, never subtracted. */
    var tenths = Math.floor(n / (div / 10));
    return Math.floor(tenths / 10) + ',' + (tenths % 10) + unit;
  }

  function initCredits(){
    var bar = document.querySelector('.topbar');
    if (!bar || document.querySelector('.creditbox')) return;
    var acct = bar.querySelector('.account');
    if (!acct) return;

    var box = document.createElement('div');
    box.className = 'creditbox';
    box.innerHTML =
      '<span class="cmain">' +
        '<svg class="cico" viewBox="0 0 24 24">' +
          '<circle cx="12" cy="12" r="8"/><path d="M12 7.2v9.6"/>' +
          '<path d="M15 9.4a3.6 3.6 0 1 0 0 5.2"/></svg>' +
        '<span class="cnum unknown" id="creditValue">—</span></span>' +
      '<a class="cplus" href="/dashboard/store#credits" aria-label="Buy credits" ' +
        'title="Buy credits">' +
        '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></a>';

    acct.parentNode.insertBefore(box, acct);
    paintCredits(meRead());
  }

  function paintCredits(me){
    var v = document.getElementById('creditValue');
    if (!v) return;

    /* Nothing cached and nothing from the server yet: an em dash, dimmed.
       It holds the same width as a real figure and says "not known yet",
       which 0 does not. */
    if (!me || typeof me.credits !== 'number') {
      v.textContent = '—';
      v.className = 'cnum unknown';
      v.removeAttribute('title');
      return;
    }
    v.textContent = creditFormat(me.credits);
    v.className = 'cnum';
    v.setAttribute('title', me.credits.toLocaleString('en-US') + ' credits');
  }


  /* The account dropdown is closed by assets/css/tones.css, in the head,
     so it is never painted open. The pages toggle it by reading
     menu.style.display, though — which is '' until something writes it,
     and '' is not 'none', so the first click would close an already
     closed menu and appear to do nothing. Writing it here keeps those
     toggles working without touching a single page. */
  function initAccountMenu(){
    var menu = document.getElementById('acctMenu');
    if (menu && !menu.style.display) menu.style.display = 'none';
  }

  /* =====================================================================
     THE BRAND MARK

     BlaineSide in the top-left is the first thing anybody tries to click
     to get home, and on every page it was a <span>. Done here rather than
     in fourteen files: it is one line of markup that was wrong in the same
     way everywhere.

     A real anchor, not a click handler — so middle-click opens a tab and
     the keyboard can reach it, which a div with an onclick cannot.
     ===================================================================== */
  function initBrandLink(){
    var name = document.querySelector('.side-brand .name');
    if (!name || name.closest('a')) return;
    var a = document.createElement('a');
    a.href = '/dashboard';
    a.className = 'name';
    a.setAttribute('aria-label', 'BlaineSide — go to the dashboard');
    a.innerHTML = name.innerHTML;
    name.parentNode.replaceChild(a, name);
  }

  var UCP_VERSION = '3.0.2';

  var FOOT_DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  var FOOT_MON  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  function initSideFoot() {
    var foot = document.querySelector('.side-foot');
    if (!foot) return;

    foot.innerHTML =
      '<div class="foot-line"><span class="fv" id="utcTime">00:00:00</span> UTC · ' +
        '<span id="utcDate">—</span></div>' +
      '<div class="foot-line">UCP v' + esc(UCP_VERSION) + ' · ' +
        '<span class="st"><span class="d"></span>All systems normal</span></div>';

    var t = document.getElementById('utcTime'), d2 = document.getElementById('utcDate');
    function pad(n) { return String(n).padStart(2, '0'); }
    function tick() {
      var d = new Date();
      t.textContent = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' +
                      pad(d.getUTCSeconds());
      d2.textContent = FOOT_DAYS[d.getUTCDay()] + ', ' + d.getUTCDate() + ' ' +
                       FOOT_MON[d.getUTCMonth()] + ' ' + d.getUTCFullYear();
    }
    tick();
    setInterval(tick, 1000);
  }

  w.UCP = {
    post: post, get: get, loadCsrf: loadCsrf,
    esc: esc, relTime: relTime, readCookie: readCookie, fmtSecs: fmtSecs,
    setTone: setTone,
    me: CACHED,                 // {name, role, rank} or null — the LAST KNOWN
    rank: CACHED ? CACHED.rank : null,
    rememberMe: rememberMe, forgetMe: forgetMe, paintMe: paintMe,
    initQuickSearch: initQuickSearch,
    nav: renderNav, NAV: NAV, version: UCP_VERSION,
    notifications: noteFetch
  };

  /* Pages still say `renderSidebar(SIDEBAR)` after the session lands, and
     that call is worth keeping — it is the redraw that corrects the menu
     for the real rank. Both names resolve here now. */
  w.SIDEBAR = NAV;
  w.renderSidebar = renderNav;

  applyTod();
  /* =====================================================================
     BOOT

     Not DOMContentLoaded. This script is loaded at the END of the body,
     after the top bar and the sidebar have already been parsed, so those
     elements exist the moment it runs — but DOMContentLoaded does not fire
     until the whole document has finished. Everything in between is a
     window where the browser has painted the page's own static markup and
     none of this has touched it yet, which is the flash of a bare top bar
     on every load.

     So: run now if the bar is already there, and fall back to the event
     for any page that loads this file in the head.
     ===================================================================== */
  function boot() {
    applyTod();
    startClock();
    initTabFade();
    loadCsrf();
    paintMe(CACHED);            // first paint, before session.php answers
    renderNav();                // ditto for the menu, from the cached rank
    initNotifications();
    initSideFoot();
    initBrandLink();
    initCredits();
    initAccountMenu();
    // Drawn from the cached rank so the box doesn't flash in and out; the
    // real rank corrects it a moment later via rememberMe() below.
    initQuickSearch(CACHED ? CACHED.rank : 0);
  }

  if (document.querySelector('.topbar') || document.querySelector('#nav')) {
    boot();
  } else {
    document.addEventListener('DOMContentLoaded', boot);
  }
})(window);
