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
     can branch on HTTP status (401 wrong password, 429 locked, 419 stale CSRF). */
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
      if (res.status === 419) {
        return loadCsrf().then(send);
      }
      return res;
    });
  }

  function get(endpoint) {
    return fetch(API + endpoint, { credentials: 'include' })
      .then(function (r) { return r.json(); })
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

  w.UCP = {
    post: post, get: get, loadCsrf: loadCsrf,
    esc: esc, relTime: relTime, readCookie: readCookie, fmtSecs: fmtSecs
  };

  applyTod();
  document.addEventListener('DOMContentLoaded', function () {
    applyTod();
    startClock();
    loadCsrf();
  });
})(window);
