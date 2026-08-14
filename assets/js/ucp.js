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
      return (m && typeof m.rank === 'number') ? m : null;
    } catch (e) { return null; }
  }
  function meWrite(m) {
    try {
      w.localStorage.setItem(ME_KEY, JSON.stringify({
        name: String(m.name || ''), role: String(m.role || 'Member'), rank: m.rank | 0
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
    var m = { name: d.name || '', role: d.role || 'Member', rank: d.rank | 0 };
    meWrite(m);
    paintMe(m);
    initQuickSearch(m.rank);
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

  w.UCP = {
    post: post, get: get, loadCsrf: loadCsrf,
    esc: esc, relTime: relTime, readCookie: readCookie, fmtSecs: fmtSecs,
    setTone: setTone,
    me: CACHED,                 // {name, role, rank} or null — the LAST KNOWN
    rank: CACHED ? CACHED.rank : null,
    rememberMe: rememberMe, forgetMe: forgetMe, paintMe: paintMe,
    initQuickSearch: initQuickSearch
  };

  applyTod();
  document.addEventListener('DOMContentLoaded', function () {
    applyTod();
    startClock();
    initTabFade();
    loadCsrf();
    paintMe(CACHED);            // first paint, before session.php answers
    // Drawn from the cached rank so the box doesn't flash in and out; the
    // real rank corrects it a moment later via rememberMe() below.
    initQuickSearch(CACHED ? CACHED.rank : 0);
  });
})(window);
