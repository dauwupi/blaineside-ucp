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
  }

  /* The tone can go on before the document body exists, and should: it is
     what stops the role line flashing amber before it turns green. */
  var CACHED = meRead();
  if (CACHED) setTone(CACHED.rank);

  w.UCP = {
    post: post, get: get, loadCsrf: loadCsrf,
    esc: esc, relTime: relTime, readCookie: readCookie, fmtSecs: fmtSecs,
    setTone: setTone,
    me: CACHED,                 // {name, role, rank} or null — the LAST KNOWN
    rank: CACHED ? CACHED.rank : null,
    rememberMe: rememberMe, forgetMe: forgetMe, paintMe: paintMe
  };

  applyTod();
  document.addEventListener('DOMContentLoaded', function () {
    applyTod();
    startClock();
    initTabFade();
    loadCsrf();
    paintMe(CACHED);            // first paint, before session.php answers
  });
})(window);
