<?php
/**
 * The other half of the shell: everything below a page's own content,
 * up to and including the shared boot script.
 *
 * A page's own <script> goes AFTER this include, so it can rely on
 * ucp.js and on renderSidebar() having run.
 */
?></main>
  </div>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

<script src="/assets/js/ucp.js"></script>
<script>
  /* ===================== SIDEBAR (shared config) ===================== */
  /* The sidebar lives in assets/js/ucp.js — one copy for every page.
     It used to be pasted into all eleven, which is eleven things to forget
     when one of them changes; adding a menu item is now an edit to NAV in
     that file and nothing else. Any page with <nav id="nav"> gets it, drawn
     from the cached rank on load and again when api/session.php answers. */
  function svgI(n,c){return `<svg class="${c}" viewBox="0 0 24 24" fill="none" stroke="currentColor">${ICONS[n]||''}</svg>`;}
  /* Administration items appear only for Management and Founders. The pages
     behind them check the rank themselves; hiding the link is so nobody is
     shown a door that won't open. */
  let IS_MANAGER=false, IS_FOUNDER=false, IS_ADMINISTRATOR=false;
  let MY_RANK = 0, MY_TEAMS = [];   // the ladder rung, and sub-group keys
  /* Menu gates.

     `min` is a rank on the ladder in api/_ranks.php. `team` is a sub-group
     key that opens the item on its own at ANY rank — which is how a Staff
     Management holder reaches the Staff Report Panel without being
     Management. A menu drawn from rank alone would be wrong for exactly the
     people the sub-group exists for.

     This decides what is DRAWN. Every page behind a link asks the server,
     and every endpoint checks again; nothing here is a permission. */

  /* Seed the menu gates from the last known session so the FIRST paint is
     right. Without this every navigation drew the sidebar twice — once with
     no Administration section, once with it — which is the flicker.
     api/session.php confirms it below, and both the pages and the endpoints
     check the rank with the server on every request regardless. */
  (function(){
    var me = window.UCP && UCP.me;
    if(!me) return;
    IS_ADMINISTRATOR = me.rank >= 3;
    IS_MANAGER       = me.rank >= 8;
    IS_FOUNDER       = me.rank >= 9;
    MY_RANK          = me.rank | 0;
    MY_TEAMS         = me.teams || [];
  })();
  renderSidebar(SIDEBAR);

  /* =====================================================================
     PAINT

     Every one of these pages starts with a placeholder and replaces it
     the moment the server answers. Swapping innerHTML is instant, which
     is exactly what makes it read as a flicker rather than as loading.

     paint() does the same swap and eases the new content in. The keyframes
     live in assets/css/tones.css so the whole UCP shares them, and they
     are disabled under prefers-reduced-motion.

     Use it for a view ARRIVING — a first load, a route change, a reload
     after an action. Do NOT use it for a redraw in place, such as opening
     one row of a list: fading the whole panel because one row moved is
     more distracting than the instant redraw it replaced.
     ===================================================================== */
  function paint(host, html){
    if (typeof host === 'string') host = document.getElementById(host);
    if (!host) return;
    host.innerHTML = html;
    host.classList.remove('bs-in');
    void host.offsetWidth;          // restart the animation
    host.classList.add('bs-in');
  }

  /* =====================================================================
     SESSION

     Every dashboard page does this identically: confirm there is a session,
     put the name and rank in the top bar, hand them to ucp.js so the next
     page paints right first time, and redraw the sidebar if the rank the
     server reports differs from the cached one.

     Bounce to /login only on a definite "not authenticated". A network
     failure draws the page instead: the endpoints check every request
     anyway, and signing somebody out because their wifi dropped is worse
     than a page that says it could not reach the server.
     ===================================================================== */
  UCP.get('session.php').then(function(d){
    if(!d || d.authenticated !== true){
      window.location.replace('/login?return=' + encodeURIComponent('/dashboard/store'));
      return;
    }
    var an = document.getElementById('acctName'), ar = document.getElementById('acctRole');
    if(an) an.textContent = d.name || '';
    if(ar) ar.textContent = d.role || 'Member';
    var mn = document.getElementById('menuName'), mr = document.getElementById('menuRole');
    if(mn) mn.textContent = d.name || '';
    if(mr) mr.textContent = d.role || 'Member';
    if(window.UCP && UCP.rememberMe) UCP.rememberMe(d);

    var was = [IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join();
    IS_ADMINISTRATOR = (d.rank|0) >= 3;
    IS_MANAGER       = (d.rank|0) >= 8;
    IS_FOUNDER       = (d.rank|0) >= 9;
    MY_RANK          = d.rank | 0;
    MY_TEAMS         = d.teams || [];
    if([IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join() !== was)
      renderSidebar(SIDEBAR);
  });


  /* =====================================================================
     CREDIT STORE

     Three of these tabs draw from a list in this file, because there is
     nothing to draw them from yet. That is deliberate and temporary: when
     a payment provider and a credit ledger exist, TIERS and ITEMS become
     endpoints and nothing else on the page changes.

     The fourth tab is real. api/store-tickets.php pages it exactly the way
     the administrative record pages a punishment list — ten to a page, a
     window of three, arrows either side — because paging that behaves
     differently on different pages is a small cruelty.
     ===================================================================== */
  var CRED  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/><path d="M12 7.2v9.6"/><path d="M15 9.4a3.6 3.6 0 1 0 0 5.2"/></svg>';
  var TAGI  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12.6V5.4a1.4 1.4 0 0 1 1.4-1.4h7.2a1.4 1.4 0 0 1 1 .4l7 7a1.4 1.4 0 0 1 0 2l-7.2 7.2a1.4 1.4 0 0 1-2 0l-7-7a1.4 1.4 0 0 1-.4-1z"/><circle cx="8.4" cy="8.4" r="1.5"/></svg>';
  var STACKI= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><ellipse cx="12" cy="7" rx="7.5" ry="3.2"/><path d="M4.5 7v4.4c0 1.8 3.4 3.2 7.5 3.2s7.5-1.4 7.5-3.2V7"/><path d="M4.5 11.6V16c0 1.8 3.4 3.2 7.5 3.2s7.5-1.4 7.5-3.2v-4.4"/></svg>';
  var GEAR  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20l4.5-1 9-9a2.1 2.1 0 0 0-3-3l-9 9L4 20z"/><path d="M13.5 6.5l3 3"/></svg>';
  var CARD  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2.4"/><path d="M3 10h18"/></svg>';
  var PPAL  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 19l2-14h5.5a3.5 3.5 0 0 1 0 7H10"/></svg>';
  var BANK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3.5 9.5L12 4.5l8.5 5"/><path d="M5.5 10.5v7M10 10.5v7M14 10.5v7M18.5 10.5v7"/><path d="M3.5 19.5h17"/></svg>';
  var LOCK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="10.5" width="14" height="9.5" rx="2.2"/><path d="M8.4 10.5V8a3.6 3.6 0 0 1 7.2 0v2.5"/></svg>';
  var CLOCK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>';
  var DOC   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 3.5h8l4 4V20a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 6 20V4a.5.5 0 0 1 .5-.5z"/><path d="M14 3.5V8h4"/><path d="M9 12.5h6M9 16h4"/></svg>';
  var BALANCE = null;

  var I_USER   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3.6"/><path d="M5.5 19.5a6.5 6.5 0 0 1 13 0"/></svg>';
  var I_INBOX  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3.5 13h4l1.5 3h6l1.5-3h4"/><path d="M5 5h14l2 8v5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-5l2-8z"/></svg>';
  var I_SEARCH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>';
  var I_PLUS   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg>';
  var I_BACK   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>';
  var I_CHECK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg>';
  var I_UNDO   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 10h9a5 5 0 0 1 0 10h-3"/><path d="M8 6l-4 4 4 4"/></svg>';
  var I_TICK   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg>';
  var I_CIRCLE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8.5"/></svg>';
  var I_LOCK   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="10.5" width="14" height="9.5" rx="2.2"/><path d="M8.4 10.5V8a3.6 3.6 0 0 1 7.2 0v2.5"/></svg>';
  var CAT_ICON = {
    credits: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/><path d="M12 7.2v9.6"/><path d="M15 9.4a3.6 3.6 0 1 0 0 5.2"/></svg>',
    double:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 4.5l8.5 15h-17l8.5-15z"/><path d="M12 10.5v4M12 17h.01"/></svg>',
    wrong:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
    other:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8.5"/><path d="M9.6 9.6a2.5 2.5 0 1 1 3.1 2.5v1.4M12 17h.01"/></svg>'
  };

  function fullDate(ts){
    var d = new Date(ts * 1000);
    return d.toLocaleDateString(undefined, {day:'numeric', month:'short', year:'numeric'}) +
      ', ' + d.toLocaleTimeString(undefined, {hour:'2-digit', minute:'2-digit'});
  }

  var TAB = 'overview', TICKETS = null, TPAGE = 1, TSCOPE = 'mine', TSTATUS = 'live', ONE = null;
  var TQ = '', TSORT = 'newest', NEWFORM = false, ME_NAME = '';
  var STORE_OPEN_MAX = 5;
  var STAFF = false, ME = 0;

  function el(id){ return document.getElementById(id); }
  function num(n){ return (n | 0).toLocaleString(); }
  function ago(ts){
    if(!ts) return '—';
    var s = Math.max(1, Math.floor(Date.now()/1000) - ts);
    if(s < 60)   return s + ' second' + (s===1?'':'s') + ' ago';
    var m = Math.floor(s/60); if(m < 60) return m + ' minute' + (m===1?'':'s') + ' ago';
    var h = Math.floor(m/60); if(h < 24) return h + ' hour'   + (h===1?'':'s') + ' ago';
    var d = Math.floor(h/24); if(d < 30) return d + ' day'    + (d===1?'':'s') + ' ago';
    return new Date(ts*1000).toLocaleDateString(undefined,{day:'numeric',month:'short',year:'numeric'});
  }
  function dt(ts){
    if(!ts) return '—';
    var d = new Date(ts*1000);
    return d.toLocaleDateString(undefined,{day:'2-digit',month:'short',year:'numeric'}) + ' ' +
           d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'});
  }

  /* ---------------- the shopfront, until there is a ledger ---------------- */
  /* Prices, and nothing else. Every percentage, per-credit figure and bar
     length on this page and on the Overview is derived from these two
     numbers, so changing a price cannot leave a stale claim behind. */
  /* Prices, and nothing else. Every percentage, per-credit figure and bar
     length on this page and on the Overview is derived from these two
     numbers, so changing a price cannot leave a stale claim behind.

     The ladder is deliberately dense at the bottom: most people buy one
     item, and the gap from €4.99 to €14.99 was wide enough that the small
     pack was the only realistic first purchase. */
  var TIERS = [
    {credits:50,   price:'€4.99'},
    {credits:120,  price:'€10.99'},
    {credits:180,  price:'€14.99'},
    {credits:260,  price:'€19.99'},
    {credits:375,  price:'€24.99'},
    {credits:850,  price:'€49.99'},
    {credits:2000, price:'€99.99'}
  ];



  var ICONS = {
    person:'<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
    slot:'<path d="M4 21V10l8-6 8 6v11"/><path d="M9 21v-6h6v6"/>',
    car:'<path d="M3 13l2-5h14l2 5v5h-3M6 18H3v-5"/><circle cx="7.5" cy="18" r="2"/><circle cx="16.5" cy="18" r="2"/>',
    house:'<path d="M4 20h16"/><path d="M6 20V9l6-4 6 4v11"/><path d="M10 20v-5h4v5"/>',
    star:'<path d="M12 3l2.4 5 5.6.8-4 4 1 5.6L12 15.8 6.9 18.4l1-5.6-4-4 5.6-.8z"/>',
    tag:'<path d="M5 12h14"/><path d="M12 5v14"/><circle cx="12" cy="12" r="9"/>'
  };

  var ITEMS = [
    {cat:'Characters', icon:'person', name:'Character name change', cost:150,
     blurb:'Changes the first or last name of one character. Your record, punishments and ' +
           'property all follow the new name.',
     facts:['One character','Applied within 24h','Staff reviewed']},
    {cat:'Characters', icon:'slot', name:'Extra character slot', cost:400,
     blurb:'One more slot on your account, permanently. You start at three; this is how you get ' +
           'a fourth without giving one up.',
     facts:['Permanent','Account wide','Instant']},
    {cat:'Vehicles', icon:'car', name:'Custom plate', cost:200,
     blurb:'Pick the plate on one vehicle you own. Up to eight characters, letters and numbers, ' +
           'subject to the naming rules.',
     facts:['One vehicle','8 characters','Staff reviewed']},
    {cat:'Property', icon:'house', name:'Furniture slots · 500', cost:250,
     blurb:'Raises the furniture limit on one property by 500 pieces. Stacks with slots you have ' +
           'already bought for the same address.',
     facts:['One property','Stacks','Instant']},
    {cat:'Account', icon:'star', name:'Donator · 30 days', cost:300,
     blurb:'A month of donator standing: the forum badge, priority in the login queue, and an ' +
           'extra vehicle slot for as long as it runs.',
     facts:['30 days','Renews manually','Instant']},
    {cat:'Account', icon:'tag', name:'UCP name change', cost:500,
     blurb:'Changes the name on this account itself, not a character. Everything you have ever ' +
           'posted moves with it.',
     facts:['Once per 90 days','Staff reviewed']}
  ];

  var CAT = 'Everything', SHOPQ = '';

  var OFFLINE = '<div class="notice"><b>Payments aren\'t switched on yet.</b>' +
    '<span>Prices are final, but nothing can be bought until a provider is connected. ' +
    'Purchase support below is live.</span></div>';

  /* ---------------- tabs ---------------- */
  var TI = {
    overview:'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/></svg>',
    credits:'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 7.2v9.6"/><path d="M15 9.4a3.6 3.6 0 1 0 0 5.2"/></svg>',
    shop:'<svg viewBox="0 0 24 24"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
    history:'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>',
    support:'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.4"/><path d="M6 6l3.6 3.6M18 6l-3.6 3.6M6 18l3.6-3.6M18 18l-3.6-3.6"/></svg>'
  };

  function tabs(){
    var open = TICKETS && TICKETS.counts
      ? (TICKETS.counts.open || 0) + (TICKETS.counts.answered || 0) : 0;
    var defs = [
      ['overview', 'Overview',         0],
      ['credits',  'Purchase Credits', 0],
      ['shop',     'Credit Shop',      0],
      ['history',  'Purchase History', 0],
      ['support',  'Purchase Support', open]
    ];
    el('tabs').innerHTML = defs.map(function(d){
      return '<button class="tab' + (TAB === d[0] ? ' on' : '') + '" data-tab="' + d[0] + '">' +
        '<span class="ic">' + TI[d[0]] + '</span><b>' + d[1] + '</b>' +
        (d[2] ? '<span class="n">' + d[2] + '</span>' : '') + '</button>';
    }).join('');
    document.querySelectorAll('[data-tab]').forEach(function(b){
      b.addEventListener('click', function(){ location.hash = this.getAttribute('data-tab'); });
    });
  }

  /* ---------------- 0 · overview ----------------
     The store is the only page in the UCP people arrive at from outside
     it, so it gets an introduction: what a credit is, what a pack costs
     per credit, how buying works, and the answers to the questions that
     would otherwise arrive as tickets. Nothing here is fetched — it is
     all statements about how the store behaves. */
  var VSEL = 2;
  var CENTS = TIERS.map(function(t){
    return +(parseFloat(t.price.replace(/[^0-9.]/g,'')) * 100 / t.credits).toFixed(1);
  });
  function saveOf(i){ return Math.round((1 - CENTS[i] / CENTS[0]) * 100); }
  /* Scaled to the best saving on offer, so adding a pack cannot push a bar
     past the end of its track. */
  function saveTop(){
    var m = 1;
    TIERS.forEach(function(t, i){ m = Math.max(m, saveOf(i)); });
    return m;
  }

  var WARN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 4.5l8.5 15h-17l8.5-15z"/><path d="M12 10.5v4M12 17h.01"/></svg>';
  var INFO = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/></svg>';
  var CHK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg>';
  var CLK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>';
  var PER  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3.6"/><path d="M5.5 19.5a6.5 6.5 0 0 1 13 0"/></svg>';
  var CV   = '<svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9l6 6 6-6"/></svg>';

  var QS = [
    ['Buying','Which pack is the best value?',
     'Every credit spends the same, but the larger packs cost less to buy per credit. The 2,000 pack works out 50% cheaper per credit than the 50 pack. If you already know what you want and it costs more than a small pack covers, buying the larger pack once is cheaper than topping up twice.' +
     '<div class="kb">' + INFO + '<span>The comparison at the top of this page shows the difference for every pack.</span></div>'],
    ['Buying','How do I pay?',
     'By bank transfer, through the payment provider &mdash; the store never sees or stores your bank details.' +
     '<div class="kb no">' + WARN + '<span>Payments are not switched on yet. Prices are final, but nothing can be bought until the provider is connected.</span></div>'],
    ['Spending','Does any of this give an advantage?',
     'No, and that is deliberate. Everything sold here is cosmetic, administrative or convenience: names, slots, plates, standing. Nothing you buy changes how your character performs against anyone else, and nothing affects the in-game economy.'],
    ['Spending','Can I gift credits or items to someone else?',
     'Not at the moment. Credits stay on the account that bought them, and anything you buy applies to that account&rsquo;s own characters.'],
    ['Refunds','Can I get a refund on credits?',
     'No. All credit purchases are <b>final and non-refundable in any form</b>, whether or not the credits have been spent. This is what keeps the store from being used to move real money in and out of the game.' +
     '<div class="kb no">' + WARN + '<span>Buying credits is a one-way transaction. Only buy what you intend to spend.</span></div>'],
    ['Refunds','Can I get a refund on something I bought in the shop?',
     'Once an item has applied, it is final &mdash; a name change cannot be undone by refunding it. There are two exceptions, and both are corrections rather than refunds:' +
     '<ul><li><b>Charged but not delivered.</b> If the credits left your balance and the item never applied, the item will be applied or the credits returned.</li>' +
     '<li><b>Applied to the wrong place.</b> If the system put an item on the wrong character or property, it will be moved.</li></ul>' +
     '<div class="kb">' + INFO + '<span>Both are handled in Purchase Support. Quote the receipt number and it is usually a single reply.</span></div>'],
    ['Refunds','What happens to my credits if I am punished or banned?',
     'They stay on the account. Credits are not confiscated as a punishment, and they are not refunded because of one either &mdash; a ban does not entitle you to money back, and the balance is still there if the account returns.'],
    ['Problems','A purchase did not apply. What now?',
     'Open Purchase History first &mdash; it will tell you whether the charge went through at all:' +
     '<ul><li><b>Nothing listed.</b> The purchase never happened and you have not been charged. Try again.</li>' +
     '<li><b>Listed as applied, but you cannot see it in-game.</b> Relog once. If it is still missing, open a ticket with the receipt number.</li></ul>'],
    ['Problems','I think I have been charged twice.',
     'Purchase History lists every top-up with its date and time, so two identical entries a few seconds apart is a duplicate worth reporting. One entry and two emails from the payment provider usually means a failed attempt followed by a successful one &mdash; only the successful one is charged. Either way, open a ticket and quote both receipt numbers.']
  ];

  function valPanel(){
    var bars = TIERS.map(function(t, i){
      var sv = saveOf(i);
      return '<button class="bar' + (i === VSEL ? ' on' : '') + '" data-pack="' + i + '">' +
        '<span class="pk">' + num(t.credits) + '</span>' +
        '<span class="tr"><i style="width:' + (sv ? Math.round(sv / saveTop() * 100) : 0) + '%"></i></span>' +
        '<span class="rt' + (sv ? '' : ' base') + '">' + (sv ? sv + '% off' : 'baseline') + '</span></button>';
    }).join('');
    var t = TIERS[VSEL], save = saveOf(VSEL);
    return '<div class="bars">' + bars + '</div>' +
      '<div class="out">' +
        '<div class="cell"><div class="k">Pack price</div><span class="n gold">' + t.price + '</span></div>' +
        '<div class="cell"><div class="k">Each credit</div><span class="n">' + CENTS[VSEL].toFixed(1) + ' cents</span></div>' +
        '<div class="cell"><div class="k">Cheaper by</div><span class="n' + (save ? ' good' : '') + '">' +
          (save ? save + '%' : '—') + '</span></div>' +
      '</div>' +
      '<div class="note">Prices are in euro, so a cent here is a euro cent. Measured against the ' +
      num(TIERS[0].credits) + '-credit pack at ' + CENTS[0].toFixed(1) +
      ' cents a credit, the most expensive of the ' + TIERS.length + '.</div>';
  }

  /* One delegated listener on the page body rather than a listener per
     element: redrawing the value panel used to call this again, which
     bound a second handler to every FAQ row, so a click fired twice and
     the answer opened and shut in the same tick. */
  function wireOverview(){
    var host = el('body');
    if(!host || host.dataset.wired) return;
    host.dataset.wired = '1';

    host.addEventListener('click', function(e){
      var bar = e.target.closest('[data-pack]');
      if(bar){
        VSEL = parseInt(bar.getAttribute('data-pack'), 10);
        var vb = el('valBody');
        if(vb) vb.innerHTML = valPanel();
        return;
      }

      /* One question open at a time: nine open at once is a page nobody
         can find their way back up. */
      var qh = e.target.closest('.faq .qh');
      if(qh){
        var q = qh.parentNode, was = q.classList.contains('on');
        host.querySelectorAll('.faq .q.on').forEach(function(o){ o.classList.remove('on'); });
        if(!was) q.classList.add('on');
        return;
      }

      var go = e.target.closest('[data-goto]');
      if(go){ e.preventDefault(); location.hash = go.getAttribute('data-goto'); }
    });
  }

  function viewOverview(){
    var faq = QS.map(function(q, i){
      return '<div class="q' + (i === 0 ? ' on' : '') + '"><div class="qh"><span>' + q[1] + '</span>' +
        '<span class="tg">' + q[0] + '</span>' + CV + '</div>' +
        '<div class="qb">' + q[2] + '</div></div>';
    }).join('');

    paint('body',
      '<div class="card hero">' +
        '<span class="img"></span><span class="veil"></span><span class="glow"></span>' +
        '<div class="in">' +
          '<div class="eyebrow">What credits are</div>' +
          '<h2>One balance. <em>No subscriptions</em>, no expiry, nothing that renews behind your back.</h2>' +
          '<p>Credits are the store&rsquo;s only currency. You buy them once, they sit on your account until you ' +
          'spend them, and every purchase writes a receipt you can open years later. Purchases apply themselves ' +
          'the moment you confirm. Nothing sold here changes how your character performs in-game: it changes ' +
          'names, slots, plates and standing, not outcomes.</p>' +
          '<div class="acts">' +
            '<a class="btn primary" href="#credits" data-goto="credits">Buy credits</a>' +
            '<a class="btn" href="#shop" data-goto="shop">Browse the shop</a>' +
          '</div>' +
        '</div>' +
        '<div class="facts">' +
          '<div class="f"><b>' + CLK + 'Never expires</b><span>A balance bought today is still there in three years, played or not.</span></div>' +
          '<div class="f"><b>' + CHK + 'Applies instantly</b><span>Confirm a purchase and it is done &mdash; nothing waits on staff.</span></div>' +
          '<div class="f"><b>' + PER + 'Cosmetic only</b><span>Names, slots, plates and standing. Never an in-game advantage.</span></div>' +
        '</div>' +
      '</div>' +

      '<div class="ovtop" style="margin-top:14px">' +
        '<div class="card val"><div class="card-h"><h3>Where credits go furthest</h3></div>' +
        '<div class="card-b" id="valBody">' + valPanel() + '</div></div>' +
        '<div class="card"><div class="card-h"><h3>How buying works</h3></div>' +
        '<div class="flow" style="grid-template-columns:1fr">' +
          '<div class="step"><div class="mk"><span class="n">1</span><h4>Buy credits</h4></div>' +
          '<p>Pick a pack and pay once. Credits land on your account immediately and show in the top bar of every page from then on.</p></div>' +
          '<div class="step" style="border-left:0;border-top:1px solid var(--border)"><div class="mk"><span class="n">2</span><h4>Spend them</h4></div>' +
          '<p>Choose an item, confirm, and the system applies it for you. No ticket, no approval, no waiting.</p></div>' +
          '<div class="step" style="border-left:0;border-top:1px solid var(--border)"><div class="mk"><span class="n">3</span><h4>Keep the receipt</h4></div>' +
          '<p>Every top-up and every purchase is listed under Purchase History with the date, the cost and exactly where it was applied.</p></div>' +
        '</div>' +
        /* A footer rather than padding: the two cards in this row should end
           level, and empty space with a border round it is the thing we keep
           taking out of this page. */
        '<div class="paying"><div class="k">Paying</div>' +
          '<div class="prow2">' + BANK + '<span><b>Bank transfer.</b> Handled by the payment provider &mdash; ' +
          'the store never sees or stores your bank details.</span></div>' +
          '<div class="prow2">' + LOCK + '<span><b>Euro, tax included.</b> The price on the pack is the price ' +
          'charged. Nothing recurs and nothing renews.</span></div>' +
        '</div></div>' +
      '</div>' +

      '<div class="card faq" style="margin-top:14px"><div class="card-h"><h3>Before you buy</h3></div>' +
        '<div>' + faq + '</div>' +
        '<div class="foot"><span>Still unsure?</span> <a data-goto="support">Ask in Purchase Support</a>' +
        '<span>&mdash; it is private, and it is the same team that handles the charge.</span></div>' +
      '</div>' +

      '<div class="card help" style="margin-top:14px">' +
        '<div class="hleft">' +
          '<div class="eyebrow">Purchase Support</div>' +
          '<h4>Something went wrong? Tell us privately.</h4>' +
          '<p>Purchase Support is a private line between you and the Management team. Nobody else on the server ' +
          'can read it, it never appears on the forums, and it stays open until the charge is resolved.</p>' +
          '<div class="sfacts">' +
            '<div class="sf"><b>Private</b><span>You and Management only</span></div>' +
            '<div class="sf"><b>Same panel</b><span>Replies arrive here, not by email</span></div>' +
            '<div class="sf"><b>Kept</b><span>Filed against the receipt for good</span></div>' +
          '</div>' +
          '<a class="btn primary" data-goto="support">Open a ticket</a>' +
        '</div>' +
        '<div class="hright">' +
          '<div class="hk">What to include</div>' +
          '<div class="hrow">' + CLK + '<span><b>The receipt number.</b> Copy it from Purchase History &mdash; it is the fastest way to find the charge.</span></div>' +
          '<div class="hrow">' + PER + '<span><b>The character it was for.</b> Items apply per character, so the name narrows it immediately.</span></div>' +
          '<div class="hrow">' + CHK + '<span><b>What you expected to happen.</b> Say what you saw instead &mdash; it saves a round of questions.</span></div>' +
        '</div>' +
      '</div>');

    wireOverview();
  }

  /* ---------------- 1 · credits ----------------
     A list you choose from on the left, a live summary of that choice on
     the right. The pack list speaks the Overview's language exactly — the
     same bar, the same "17% off / baseline", the same cents per credit —
     so somebody arriving from the homepage already knows how to read it.

     A promotion is one object with a name, a kind and a value. It applies
     to every pack, and it flows through every number here rather than
     being announced beside them. */
  var PROMO = null, MAY_PROMO = false, PSEL = 2, PDRAFT = null;

  function packPrice(t){
    return parseFloat(t.price.replace(/[^0-9.]/g, ''));
  }
  /* Credits a pack gives once the promotion is applied. */
  function packCredits(t){
    return (PROMO && PROMO.kind === 'bonus')
      ? Math.round(t.credits * (1 + PROMO.value / 100)) : t.credits;
  }
  /* What it costs once the promotion is applied. */
  function packCost(t){
    return (PROMO && PROMO.kind === 'off')
      ? +(packPrice(t) * (1 - PROMO.value / 100)).toFixed(2) : packPrice(t);
  }
  function packCents(t){ return +(packCost(t) * 100 / packCredits(t)).toFixed(1); }
  function packSave(i){
    return Math.round((1 - packCents(TIERS[i]) / packCents(TIERS[0])) * 100);
  }
  /* The bars are scaled to the best saving on offer rather than a fixed
     50%, so adding a pack cannot push a bar past the end of its track. */
  function packSaveMax(){
    var m = 1;
    TIERS.forEach(function(t, i){ m = Math.max(m, packSave(i)); });
    return m;
  }
  function eur(n){ return '€' + n.toFixed(2); }

  function packRows(){
    var top = packSaveMax();
    return TIERS.map(function(t, i){
      var sv = packSave(i), bonus = packCredits(t) - t.credits, cut = PROMO && PROMO.kind === 'off';
      /* .prow / data-buy, not .pk / data-pack: the Overview's value panel
         already owns those names, and sharing them made a click on one of
         its bars jump to this tab. */
      return '<button class="prow' + (i === PSEL ? ' on' : '') + '" data-buy="' + i + '">' +
        '<span><span class="top">' + CRED + '<span class="n">' + num(packCredits(t)) + '</span></span>' +
          '<span class="line">' + (bonus
            ? num(t.credits) + ' + <b>' + num(bonus) + ' bonus</b>'
            : 'credits') + '</span></span>' +
        '<span class="tr"><i style="width:' + (sv ? Math.round(sv / top * 100) : 0) + '%"></i></span>' +
        '<span class="off' + (sv ? '' : ' base') + '">' + (sv ? sv + '% off' : 'baseline') + '</span>' +
        '<span class="pr"><span class="now">' + eur(packCost(t)) + '</span>' +
          (cut ? '<span class="was">' + eur(packPrice(t)) + '</span>' : '') +
          '<span class="each">' + packCents(t).toFixed(1) + ' cents each</span></span>' +
        '<span class="rd"></span></button>';
    }).join('');
  }

  function promoLabel(p){
    return p.kind === 'off'
      ? p.value + '% off every pack'
      : '+' + p.value + '% credits on every pack';
  }
  /* "3d 04h 12m", counted down from the row's end time. */
  function promoLeft(sec){
    if(sec <= 0) return 'ending';
    var d = Math.floor(sec / 86400), h = Math.floor(sec % 86400 / 3600), m = Math.floor(sec % 3600 / 60);
    return (d ? d + 'd ' : '') + pad2(h) + 'h ' + pad2(m) + 'm';
  }
  function pad2(n){ return n < 10 ? '0' + n : String(n); }

  function packHead(){
    var edit = MAY_PROMO
      ? '<button class="fbtn" id="promoBtn">' + GEAR + (PROMO ? 'Edit' : 'Promotion') + '</button>' : '';
    if(!PROMO){
      return '<div class="card-h"><h3>Choose a pack</h3>' +
        '<span class="r">Larger packs cost less per credit</span>' + edit + '</div>';
    }
    return '<div class="p3h"><span class="ic">' + (PROMO.kind === 'off' ? TAGI : STACKI) + '</span>' +
      '<span class="tx"><b>' + escapeHtml(PROMO.name) + ' &mdash; <em>' + promoLabel(PROMO) + '</em></b>' +
      '<span>Applied at checkout. The prices below already include it.</span></span>' +
      '<span class="r"><span class="t">' + promoLeft(PROMO.left) + '</span>' +
      '<span class="k">remaining</span></span>' + edit + '</div>';
  }

  function summary(){
    var t = TIERS[PSEL], sv = packSave(PSEL);
    var bonus = packCredits(t) - t.credits, cut = PROMO && PROMO.kind === 'off';
    var bal = (typeof BALANCE === 'number') ? BALANCE : null;

    return '<div class="big"><span class="bi">' + CRED + '</span>' +
      '<span class="n">' + num(packCredits(t)) + '</span><span class="u">credits</span>' +
      '<span class="pr"><span class="k">You pay</span><span class="n2"' +
      (cut ? ' style="color:#9dbd77"' : '') + '>' + eur(packCost(t)) + '</span></span></div>' +

      '<div class="row"><span>Pack</span><span class="v">' + num(t.credits) + ' credits</span></div>' +
      (bonus ? '<div class="row"><span>' + escapeHtml(PROMO.name) + ' bonus</span>' +
        '<span class="v good">+' + num(bonus) + ' credits</span></div>' : '') +
      (cut ? '<div class="row"><span>List price</span><span class="v strike">' + eur(packPrice(t)) + '</span></div>' +
        '<div class="row"><span>' + escapeHtml(PROMO.name) + ' discount</span><span class="v good">&minus;' +
        eur(+(packPrice(t) - packCost(t)).toFixed(2)) + '</span></div>' : '') +
      '<div class="row"><span>Each credit</span><span class="v">' + packCents(t).toFixed(1) + ' cents</span></div>' +
      '<div class="row"><span>Cheaper than the smallest pack</span><span class="v' + (sv ? ' good' : '') + '">' +
        (sv ? sv + '%' : '&mdash;') + '</span></div>' +

      (bal === null ? '' :
        '<div class="after">' + CRED + '<span>Balance after this purchase</span><b>' +
        num(bal + packCredits(t)) + '</b></div>') +

      '<div class="methods"><span class="mth">' + BANK + 'Bank transfer</span>' +
      '<span class="mth">' + LOCK + 'Encrypted</span></div>' +

      '<button class="btn primary go" disabled>Payments aren’t switched on yet</button>' +
      '<div class="note">Charged once in euro, tax included. Credits land immediately and a receipt is ' +
      'written to Purchase History. Credit purchases are final and non-refundable.</div>';
  }

  function viewCredits(){
    paint('body',
      '<div class="buygrid">' +
        '<div class="card">' + packHead() + '<div class="packs">' + packRows() + '</div></div>' +
        '<div class="card sum"><div class="card-h"><h3>Your purchase</h3></div>' +
        '<div class="card-b">' + summary() + '</div></div>' +
      '</div>' +
      '<div class="card" style="margin-top:14px"><div class="det">' +
        '<div class="dc"><b>' + CLOCK + 'Instant, every time</b><p>Credits appear on your balance the moment ' +
        'the payment clears, and show in the top bar on every page. Nothing waits on a member of staff.</p></div>' +
        '<div class="dc"><b>' + DOC + 'Receipted permanently</b><p>Every top-up is written to Purchase History ' +
        'with the date, the amount, the price paid and any promotion applied &mdash; quote that number if ' +
        'anything needs correcting.</p></div>' +
        '<div class="dc"><b>' + LOCK + 'Handled by the provider</b><p>Payment is by bank transfer, handled ' +
        'by the payment provider. The store never sees or stores your bank details, and prices are in euro ' +
        'with tax included.</p></div>' +
      '</div></div>');
  }

  /* ---- founder: the promotion editor ---- */
  function promoDraft(){
    if(PDRAFT) return PDRAFT;
    var d = new Date(Date.now() + 7 * 86400000);
    PDRAFT = PROMO
      ? {name: PROMO.name, kind: PROMO.kind, value: PROMO.value,
         ends: fmtLocalEnds(new Date(PROMO.ends * 1000))}
      : {name: '', kind: 'off', value: 20, ends: fmtLocalEnds(d)};
    return PDRAFT;
  }
  function fmtLocalEnds(d){
    return d.getUTCFullYear() + '-' + pad2(d.getUTCMonth() + 1) + '-' + pad2(d.getUTCDate()) +
      ' ' + pad2(d.getUTCHours()) + ':' + pad2(d.getUTCMinutes());
  }

  /* The preview writes out what the offer actually does to a real pack.
     Setting 90% by accident should be visible before it is saved, not
     after somebody has bought at that price. */
  function promoPreview(d){
    var t = TIERS[2], before = packPrice(t);
    if(d.kind === 'off'){
      var after = +(before * (1 - d.value / 100)).toFixed(2);
      return '<b>' + escapeHtml(d.name || 'Untitled') + ' &mdash; ' + d.value + '% off every pack</b><br>' +
        num(t.credits) + ' credits drops from ' + eur(before) + ' to <b>' + eur(after) + '</b>.';
    }
    var got = Math.round(t.credits * (1 + d.value / 100));
    return '<b>' + escapeHtml(d.name || 'Untitled') + ' &mdash; +' + d.value + '% credits on every pack</b><br>' +
      'The ' + num(t.credits) + ' pack gives <b>' + num(got) + ' credits</b> for the same ' + eur(before) + '.';
  }

  function promoModal(){
    var d = promoDraft();
    var host = document.createElement('div');
    host.className = 'pmask';
    host.innerHTML =
      '<div class="pmodal"><div class="mh"><h4>Credit promotion</h4>' +
      '<span class="who">Founder only</span></div><div class="mb">' +
        '<div class="fld"><label>What it does</label><div class="seg">' +
          '<button data-kind="off"' + (d.kind === 'off' ? ' class="on"' : '') +
            '>Discount the price<span>Packs cost less</span></button>' +
          '<button data-kind="bonus"' + (d.kind === 'bonus' ? ' class="on"' : '') +
            '>Add bonus credits<span>Packs give more</span></button>' +
        '</div></div>' +
        '<div class="fld"><label>Name shown to players</label>' +
          '<input id="pName" maxlength="60" value="' + escapeHtml(d.name) + '" placeholder="Founders Week"></div>' +
        '<div class="two"><div class="fld"><label>Amount %</label>' +
          '<input id="pVal" inputmode="numeric" value="' + d.value + '"></div>' +
          '<div class="fld"><label>Ends (UTC)</label>' +
          '<input id="pEnds" value="' + escapeHtml(d.ends) + '" placeholder="2026-08-23 23:59"></div></div>' +
        '<div class="prev"><div class="k">Players will see</div>' +
        '<div class="l" id="pPrev">' + promoPreview(d) + '</div></div>' +
      '</div>' +
      '<div class="mf"><span class="warn">Applies to every pack. Purchases already made are not changed.</span>' +
      '<span class="sp">' +
        (PROMO ? '<button class="btn" id="pStop">Stop the promotion</button>' : '') +
        '<button class="btn" id="pCancel">Cancel</button>' +
        '<button class="btn primary" id="pSave">Save</button>' +
      '</span></div></div>';
    document.body.appendChild(host);

    function sync(){
      d.name = el('pName').value;
      d.value = Math.max(0, parseInt(el('pVal').value, 10) || 0);
      d.ends = el('pEnds').value;
      el('pPrev').innerHTML = promoPreview(d);
    }
    host.querySelectorAll('[data-kind]').forEach(function(b){
      b.addEventListener('click', function(){
        d.kind = b.getAttribute('data-kind');
        host.querySelectorAll('[data-kind]').forEach(function(o){ o.classList.remove('on'); });
        b.classList.add('on');
        sync();
      });
    });
    ['pName','pVal','pEnds'].forEach(function(id){ el(id).addEventListener('input', sync); });

    function close(){ PDRAFT = null; host.remove(); }
    el('pCancel').addEventListener('click', close);
    host.addEventListener('click', function(e){ if(e.target === host) close(); });

    if(el('pStop')){
      el('pStop').addEventListener('click', function(){
        UCP.post('store-promo.php', {stop: 1}).then(function(r){
          if(!r.data || !r.data.ok) return toast((r.data && r.data.error) || 'Could not stop it.');
          PROMO = null; close(); toast('Promotion stopped.'); route();
        });
      });
    }
    el('pSave').addEventListener('click', function(){
      sync();
      UCP.post('store-promo.php', {name: d.name, kind: d.kind, value: d.value, ends: d.ends})
        .then(function(r){
          if(!r.data || !r.data.ok) return toast((r.data && r.data.error) || 'Could not save that.');
          PROMO = r.data.promo || null; close(); toast('Promotion saved.'); route();
        });
    });
  }

  /* ---------------- 2 · shop ---------------- */
  function shopGrid(){
    var hits = ITEMS.filter(function(i){
      if(CAT !== 'Everything' && i.cat !== CAT) return false;
      if(!SHOPQ) return true;
      return (i.name + ' ' + i.blurb + ' ' + i.cat).toLowerCase().indexOf(SHOPQ) > -1;
    });
    if(!hits.length) return '<div class="empty">Nothing in the shop matches that.</div>';
    return '<div class="items">' + hits.map(function(i){
      return '<div class="item"><div class="ihead">' +
        '<span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
          (ICONS[i.icon] || '') + '</svg></span>' +
        '<div><div class="eyebrow">' + escapeHtml(i.cat) + '</div>' +
        '<h3>' + escapeHtml(i.name) + '</h3></div>' +
        '<div class="cost"><div class="n">' + num(i.cost) + '</div><div class="u">credits</div></div></div>' +
        '<p>' + escapeHtml(i.blurb) + '</p>' +
        '<div class="facts">' + i.facts.map(function(f){
          return '<span class="fact">' + escapeHtml(f) + '</span>';
        }).join('') + '</div>' +
        '<div class="foot"><span class="own">Not available yet</span>' +
        '<button class="btn quiet" disabled>Buy</button></div></div>';
    }).join('') + '</div>';
  }

  function viewShop(){
    var cats = ['Everything','Characters','Vehicles','Property','Account'];
    paint('body', OFFLINE +
      '<div class="toolbar">' + cats.map(function(c){
        return '<span class="chip' + (CAT === c ? ' on' : '') + '" data-cat="' + c + '">' + c + '</span>';
      }).join('') +
      '<span class="sbox"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/>' +
      '<path d="M20 20l-3.5-3.5"/></svg>' +
      '<input id="shopq" placeholder="Search the shop…" autocomplete="off" value="' +
      escapeHtml(SHOPQ) + '"></span></div>' +
      '<div id="shopgrid">' + shopGrid() + '</div>');

    document.querySelectorAll('[data-cat]').forEach(function(c){
      c.addEventListener('click', function(){
        CAT = this.getAttribute('data-cat');
        document.querySelectorAll('[data-cat]').forEach(function(x){ x.classList.remove('on'); });
        this.classList.add('on');
        el('shopgrid').innerHTML = shopGrid();
      });
    });
    var q = el('shopq');
    if(q) q.addEventListener('input', function(){
      SHOPQ = this.value.trim().toLowerCase();
      /* Grid alone, so the box keeps focus while it is being typed in. */
      el('shopgrid').innerHTML = shopGrid();
    });
  }

  /* ---------------- 3 · history ---------------- */
  function viewHistory(){
    paint('body', OFFLINE +
      '<div class="card"><div class="card-h"><h3>Purchase history</h3></div>' +
      '<div class="card-b"><div class="empty">Nothing here yet. Credits you buy and items you ' +
      'spend them on will appear as a running list, newest first.</div></div></div>');
  }

  /* ---------------- 4 · support ---------------- */
  /* The administrative record's pager, to the pixel: window of three,
     arrows either side, ends pinned with an ellipsis. */
  /* The administrative record's pager, to the letter: a count on the left,
     Previous / numbers / Next on the right. Paging that behaves differently
     on different pages is a small cruelty, so this borrows the record's
     shape rather than inventing a third one. */
  function pager(page, pages, total, per){
    if(pages <= 1) return '';
    var first = (page - 1) * per;
    var b = [];
    b.push('<button data-page="' + (page-1) + '"' + (page <= 1 ? ' disabled' : '') + '>Previous</button>');
    for(var i = 1; i <= pages; i++){
      b.push('<button data-page="' + i + '"' + (i === page ? ' aria-current="true"' : '') + '>' + i + '</button>');
    }
    b.push('<button data-page="' + (page+1) + '"' + (page >= pages ? ' disabled' : '') + '>Next</button>');
    return '<div class="pager">' +
      '<span class="pcount">Showing ' + (first + 1) + '–' + Math.min(first + per, total) +
      ' of ' + total + '</span>' +
      '<div class="pnav">' + b.join('') + '</div></div>';
  }

  /* Status reads as one of three words, in the appeal list's own pill:
     centred, uppercase, no dot. "Open" means it is waiting on Management;
     "Answered" means the ball is back with the player. */
  function statusPill(t, cls){
    var k = t.status === 'closed' ? 'closed' : (t.status === 'answered' ? 'answered' : 'open');
    var w = t.status === 'closed' ? 'Closed'
          : (t.status === 'answered' ? 'Answered' : (STAFF && TSCOPE === 'all' ? 'Unanswered' : 'Open'));
    return '<span class="' + (cls || 'pill') + ' ' + k + '">' + w + '</span>';
  }

  function catLabel(t){ return t.category_label || 'Something else'; }

  /* A name is a link to that account's record for staff, and plain text
     for everybody else — a player cannot open the lookup page, so a link
     there would be a dead end wearing a pointer cursor. */
  function nameLink(id, name){
    var safe = escapeHtml(name || 'somebody');
    return (STAFF && id) ? '<a class="plink" href="/dashboard/lookup?id=' + id + '">' + safe + '</a>' : safe;
  }

  function waitedFor(t){
    var since = (t.last && t.last.at) ? t.last.at : t.created_at;
    return ago(since);
  }

  function ticketRow(t){
    var bits = ['Opened ' + ago(t.created_at)];
    if(t.status === 'closed' && t.closed){
      bits.push('Closed by <b>' + escapeHtml(t.closed.by || 'somebody') + '</b>');
    } else if(t.last){
      bits.push((t.last.staff ? 'Management replied ' : 'Player replied ') + '<b>' + ago(t.last.at) + '</b>');
    } else {
      bits.push('<b>Waiting ' + waitedFor(t) + '</b>');
    }
    bits.push(t.replies ? t.replies + ' comment' + (t.replies === 1 ? '' : 's') : 'No comments');

    return '<a class="aprow" data-ticket="' + t.id + '">' +
      '<span class="num">' + escapeHtml(t.order_ref || 'N/A') + '</span>' +
      '<span><span class="t">' + escapeHtml(t.subject) + '</span>' +
      '<span class="s">' + bits.join('<span class="dot">·</span>') + '</span></span>' +
      '<span class="tcat">' + escapeHtml(catLabel(t)) + '</span>' +
      statusPill(t, 'ap') +
      '<svg class="go" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg></a>';
  }

  /* ---- the list ---- */
  function filterBar(){
    var opts = (STAFF && TSCOPE === 'all')
      ? [['open','Unanswered'],['answered','Answered'],['closed','Closed'],['all','Everything']]
      : [['live','Open'],['closed','Closed'],['all','Everything']];
    var c = (TICKETS && TICKETS.counts) || {};
    var n = {open:c.open|0, answered:c.answered|0, closed:c.closed|0,
             live:(c.open|0)+(c.answered|0), all:(c.open|0)+(c.answered|0)+(c.closed|0)};
    return '<span class="filters">' + opts.map(function(o){
      return '<button data-status="' + o[0] + '"' + (TSTATUS === o[0] ? ' class="on"' : '') + '>' +
        o[1] + '<span class="n">' + (n[o[0]] || 0) + '</span></button>';
    }).join('') + '</span>';
  }

  function viewSupport(){
    var d = TICKETS;
    if(!d){ paint('body', '<div class="empty">Loading…</div>'); return loadTickets(); }

    if(!d.available){
      paint('body', '<div class="card"><div class="card-b"><p class="lede">' +
        escapeHtml(d.why || 'Purchase support isn\'t switched on yet.') + '</p></div></div>');
      return;
    }

    if(NEWFORM) return viewNewTicket();

    var c = d.counts || {};
    var live = (c.open|0) + (c.answered|0);
    var all  = live + (c.closed|0);

    /* No page title and no lede: every other tab in the Credit Store opens
       straight onto its content, and a heading that repeats the tab you
       just clicked is a line of text that tells you nothing. */
    var head = '<div class="suphead">' +
      (STAFF
        ? '<div class="tabbar">' +
          '<button class="tab' + (TSCOPE === 'mine' ? ' on' : '') + '" data-scope="mine">' + I_USER +
          'Your tickets<span class="n">' + all + '</span></button>' +
          '<button class="tab' + (TSCOPE === 'all' ? ' on' : '') + '" data-scope="all">' + I_INBOX +
          'Ticket Management<span class="n">' + live + '</span></button></div>'
        : '<span></span>') +
      '<button class="btn primary" id="newBtn">' + I_PLUS + 'Open a ticket</button></div>';

    var search = (STAFF && TSCOPE === 'all')
      ? '<div class="searchrow"><span class="searchbox">' + I_SEARCH +
        '<input type="text" id="tq" placeholder="Search player, order reference or subject…" value="' +
        escapeHtml(TQ) + '"></span>' +
        '<button class="btn sm" id="tsort">' + (TSORT === 'oldest' ? 'Oldest first' : 'Newest first') +
        '</button></div>'
      : '';

    paint('body', head +
      '<div class="card"><div class="card-h"><h3>' +
        (TSCOPE === 'all' ? 'All tickets' : 'Your tickets') + '</h3>' +
        '<div class="r">' + filterBar() + '</div></div>' +
      '<div class="card-b">' + search +
      (d.rows.length
        ? '<div class="aplist">' + d.rows.map(ticketRow).join('') + '</div>'
        : '<div class="empty">' + (TSTATUS === 'all'
            ? 'No tickets yet.' : 'Nothing matches that filter.') + '</div>') +
      pager(d.page, d.pages, d.total, d.per || 10) +
      '</div></div>');

    wireSupport();
  }

  function wireSupport(){
    document.querySelectorAll('[data-ticket]').forEach(function(r){
      r.addEventListener('click', function(){ location.hash = 'ticket=' + this.getAttribute('data-ticket'); });
    });
    document.querySelectorAll('[data-page]').forEach(function(b){
      b.addEventListener('click', function(){
        if(this.disabled) return;
        TPAGE = parseInt(this.getAttribute('data-page'), 10) || 1;
        loadTickets();
      });
    });
    document.querySelectorAll('[data-scope]').forEach(function(b){
      b.addEventListener('click', function(){
        TSCOPE = this.getAttribute('data-scope');
        TSTATUS = TSCOPE === 'all' ? 'open' : 'live';
        TPAGE = 1; TQ = ''; loadTickets();
      });
    });
    document.querySelectorAll('[data-status]').forEach(function(b){
      b.addEventListener('click', function(){
        TSTATUS = this.getAttribute('data-status'); TPAGE = 1; loadTickets();
      });
    });
    var so = el('tsort');
    if(so) so.addEventListener('click', function(){
      TSORT = TSORT === 'oldest' ? 'newest' : 'oldest'; TPAGE = 1; loadTickets();
    });
    var q = el('tq');
    if(q) q.addEventListener('keydown', function(e){
      if(e.key === 'Enter'){ TQ = this.value.trim(); TPAGE = 1; loadTickets(); }
    });
    var nb = el('newBtn');
    if(nb) nb.addEventListener('click', function(){ NEWFORM = true; viewSupport(); });
  }

  /* ---- opening one ----
     Three steps rather than a wall of fields, and no subject: the title is
     built from the account and the order reference on the server, so every
     ticket in the queue reads the same way. */
  var DRAFT = {category:'credits', order:'', no_order:false, amount:'', char:'', body:''};

  function draftTitle(){
    var ref = DRAFT.no_order ? 'N/A' : (DRAFT.order.trim() || 'N/A');
    return 'Purchase Support — ' + (ME_NAME || 'you') + ' (' + ref + ')';
  }

  function checklist(){
    var rows = [
      [DRAFT.category !== '', 'Told us what it is about.', 'It decides who picks it up first.'],
      [DRAFT.no_order || DRAFT.order.trim() !== '', 'Order reference given.',
       'Copy it from Purchase History, or tick that you have none.'],
      [DRAFT.body.trim().length >= 20, 'Said what happened.',
       'At least 20 characters — the more precise, the fewer replies it takes.']
    ];
    return rows.map(function(r){
      return '<div class="ck' + (r[0] ? ' done' : '') + '">' +
        (r[0] ? I_TICK : I_CIRCLE) + '<span><b>' + r[1] + '</b> ' + r[2] + '</span></div>';
    }).join('');
  }

  function viewNewTicket(){
    var cats = (TICKETS && TICKETS.categories) || {};
    var keys = Object.keys(cats);
    var blurb = {
      credits: 'You paid, the money left your account, and the balance never moved.',
      double:  'Two charges for one purchase, or one charge and two receipts.',
      wrong:   'The purchase went through but landed on the wrong character or property.',
      other:   'Anything about a payment that the three above do not cover.'
    };

    paint('body',
      '<button class="btn sm backb" id="backList">' + I_BACK + 'Back to your tickets</button>' +
      '<div class="phead"><div><h2>Open a ticket</h2>' +
      '<p>Only you and Management can read this. You will be notified here when they reply.</p></div>' +
      '</div>' +

      '<div class="apgrid"><div class="card"><div class="steps">' +

      '<div class="step"><div class="sn"><span class="n">1</span><h4>What is this about?</h4>' +
      '<span class="req2">Required</span><p>Sets who picks it up first.</p></div>' +
      '<div class="cats">' + keys.map(function(k){
        return '<button class="cat' + (DRAFT.category === k ? ' on' : '') + '" data-cat="' + k + '">' +
          '<span class="ic">' + (CAT_ICON[k] || CAT_ICON.other) + '</span>' +
          '<span><b>' + escapeHtml(cats[k]) + '</b><span>' + (blurb[k] || '') + '</span></span></button>';
      }).join('') + '</div></div>' +

      '<div class="step"><div class="sn"><span class="n">2</span><h4>Which purchase?</h4>' +
      '<span class="req2">Required</span><p>The fastest way to find your charge.</p></div>' +
      '<div class="fld"><label>Order reference</label>' +
      '<div class="orow"><input type="text" id="nOrder" maxlength="40" placeholder="#595625" value="' +
      escapeHtml(DRAFT.order) + '"' + (DRAFT.no_order ? ' disabled' : '') + '>' +
      '<button class="na' + (DRAFT.no_order ? ' on' : '') + '" id="noOrder">' +
      '<span class="bx">' + I_TICK + '</span>I don\'t have one</button></div>' +
      '<div class="hint">Copy it from Purchase History. No order? Tick the box and it is recorded ' +
      'as <b>N/A</b>.</div></div>' +
      '<div class="two"><div class="fld"><label>Amount paid <u>optional</u></label>' +
      '<div class="pfxwrap"><span class="pfx">€</span>' +
      '<input type="text" id="nAmount" maxlength="40" placeholder="24.99" value="' +
      escapeHtml(DRAFT.amount) + '"></div></div>' +
      '<div class="fld"><label>Character <u>optional</u></label>' +
      '<input type="text" id="nChar" maxlength="60" placeholder="Marcus Reyes" value="' +
      escapeHtml(DRAFT.char) + '"></div></div></div>' +

      '<div class="step"><div class="sn"><span class="n">3</span><h4>What happened?</h4>' +
      '<span class="req2">Required</span><p>Written once, read by Management only.</p></div>' +
      '<div class="fld"><textarea id="nBody" rows="5" placeholder="When it happened, what you ' +
      'expected, and what you got instead.">' + escapeHtml(DRAFT.body) + '</textarea>' +
      '<div class="hint"><span class="count" id="nCount">' + DRAFT.body.trim().length +
      ' / 20 minimum</span>The more precise you are, the fewer replies it takes.</div></div></div>' +

      '<div class="fa"><button class="btn primary" id="openBtn">Open ticket</button>' +
      '<button class="btn" id="cancelBtn">Cancel</button></div>' +
      '</div></div>' +

      '<div><div class="card"><div class="card-h"><h3>Your ticket</h3></div><div class="card-b">' +
      '<div class="tcheck"><div class="k">It will be titled</div>' +
      '<div class="v" id="tTitle">' + escapeHtml(draftTitle()) + '</div></div>' +
      '<div class="asech">Before you send</div><div id="tChecks">' + checklist() + '</div>' +
      '<div class="asech">Who can read this</div>' +
      '<div class="priv">' + I_LOCK + '<span>Only you and the Management team. It never appears on ' +
      'the forums, and it stays open until the charge is settled.</span></div>' +
      '</div></div></div></div>');

    wireNewTicket();
  }

  function wireNewTicket(){
    function sync(){
      var t = el('tTitle'), c = el('tChecks'), n = el('nCount');
      if(t) t.textContent = draftTitle();
      if(c) c.innerHTML = checklist();
      if(n) n.textContent = DRAFT.body.trim().length + ' / 20 minimum';
    }
    document.querySelectorAll('[data-cat]').forEach(function(b){
      b.addEventListener('click', function(){
        DRAFT.category = this.getAttribute('data-cat');
        document.querySelectorAll('[data-cat]').forEach(function(o){ o.classList.remove('on'); });
        this.classList.add('on'); sync();
      });
    });
    var no = el('noOrder');
    if(no) no.addEventListener('click', function(){
      DRAFT.no_order = !DRAFT.no_order;
      this.classList.toggle('on', DRAFT.no_order);
      el('nOrder').disabled = DRAFT.no_order;
      sync();
    });
    [['nOrder','order'],['nAmount','amount'],['nChar','char'],['nBody','body']].forEach(function(f){
      var e = el(f[0]);
      if(e) e.addEventListener('input', function(){ DRAFT[f[1]] = this.value; sync(); });
    });
    el('backList').addEventListener('click', function(){ NEWFORM = false; viewSupport(); });
    el('cancelBtn').addEventListener('click', function(){ NEWFORM = false; viewSupport(); });
    el('openBtn').addEventListener('click', openTicket);
  }

  function openTicket(){
    var b = el('openBtn'); b.disabled = true;
    UCP.post('store-ticket-open.php', {
      category: DRAFT.category,
      order_ref: DRAFT.order,
      no_order: DRAFT.no_order ? 1 : 0,
      /* The € is drawn in the field rather than typed, so it has to be put
         back on the way out — otherwise the detail panel would read "24.99"
         with no currency against it. */
      amount: DRAFT.amount.trim() === '' ? '' :
              (/^[€]/.test(DRAFT.amount.trim()) ? DRAFT.amount.trim() : '€' + DRAFT.amount.trim()),
      char_name: DRAFT.char,
      body: DRAFT.body
    }).then(function(res){
      var d = res.data || {};
      b.disabled = false;
      if(!d.ok) return toast(d.error || 'Could not open the ticket');
      DRAFT = {category:'credits', order:'', no_order:false, amount:'', char:'', body:''};
      NEWFORM = false; TICKETS = null;
      toast('Ticket opened. Management have been notified.');
      location.hash = 'ticket=' + d.id;
    }).catch(function(){ b.disabled = false; toast('Could not reach the server'); });
  }

  /* ---------------- one ticket ----------------
     Laid out like a ban appeal: the request on the left, the details as a
     key/value list beside it, comments as bordered cards, composer last. */
  function viewTicket(t){
    ONE = t;
    STAFF = !!t.staff;

    var first = (t.messages || [])[0];
    var rest  = (t.messages || []).slice(1);

    var comments = rest.map(function(m){
      return '<div class="cm' + (m.staff ? ' staff' : '') + '">' +
        '<div class="h"><b>' + nameLink(m.id, m.author) + '</b>' +
        '<span class="gchip tone-' + (m.rank | 0) + '">' + escapeHtml(m.role || 'Member') + '</span>' +
        '<span class="when">' + ago(m.at) + '</span></div>' +
        '<div class="body">' + escapeHtml(m.body) + '</div></div>';
    }).join('');

    var kv = [
      ['Order reference', escapeHtml(t.order_ref || 'N/A'), !t.order_ref || t.order_ref === 'N/A'],
      ['About', escapeHtml(t.category_label || 'Something else'), false],
      ['Amount paid', t.amount ? escapeHtml(t.amount) : 'Not given', !t.amount],
      ['Character', t.char_name ? escapeHtml(t.char_name) : 'Not given', !t.char_name],
      ['Opened by', t.player ? nameLink(t.player.id, t.player.name) : '—', false],
      ['Opened', fullDate(t.created_at), true]
    ].map(function(r){
      return '<div class="r"><div class="k">' + r[0] + '</div>' +
        '<div class="v' + (r[2] ? ' soft' : '') + '">' + r[1] + '</div></div>';
    }).join('');

    var hist = t.history
      ? '<div class="card" style="margin-top:16px"><div class="card-h"><h3>Their history</h3></div>' +
        '<div class="card-b"><div class="kv">' +
        '<div class="r"><div class="k">Tickets opened</div><div class="v">' + t.history.tickets +
        ' · ' + t.history.closed + ' closed</div></div>' +
        '<div class="r"><div class="k">Member since</div><div class="v soft">' +
        escapeHtml((t.history.since || '').slice(0, 10) || 'unknown') + '</div></div>' +
        '</div></div></div>'
      : '';

    var acts = '';
    if(t.may_reply && STAFF) acts = '<button class="btn" id="closeBtn">' + I_CHECK + 'Close ticket</button>';

    paint('body',
      '<button class="btn sm backb" id="backBtn">' + I_BACK + 'Back to ' +
        (TSCOPE === 'all' ? 'the queue' : 'your tickets') + '</button>' +

      '<div class="phead"><div><h2>' + escapeHtml(t.subject) + '</h2>' +
      '<p>Opened ' + ago(t.created_at) +
      (t.player ? ' by <b>' + nameLink(t.player.id, t.player.name) + '</b>' : '') +
      (t.closed ? ' · Closed by <b>' + escapeHtml(t.closed.by || 'somebody') + '</b> ' + ago(t.closed.at)
                : (t.last ? ' · Last comment ' + ago(t.last.at) : '')) + '</p></div>' +
      (acts ? '<span style="display:flex;gap:10px">' + acts + '</span>' : '') + '</div>' +

      '<div class="apgrid"><div>' +
        '<div class="card"><div class="card-h"><h3>The request</h3>' +
        '<div class="r">' + statusPill(t) + '</div></div>' +
        '<div class="card-b"><p class="card-lede">' +
        escapeHtml(first ? first.body : '') + '</p></div></div>' +

        '<div class="card" style="margin-top:16px"><div class="card-h"><h3>Comments</h3>' +
        '<div class="r">' + rest.length + '</div></div><div class="card-b">' +
        (rest.length ? comments : '<div class="empty">No comments yet.</div>') +

        (t.may_reply
          ? '<div class="composer"><textarea id="rBody" rows="4" placeholder="Write a comment…"></textarea>' +
            '<div class="row"><button class="btn primary" id="replyBtn">Post comment</button>' +
            (STAFF ? '<button class="btn" id="replyCloseBtn">' + I_CHECK + 'Post and close</button>' : '') +
            '<span class="grow">' + (STAFF ? 'The player is notified either way.'
                                           : 'Management are notified.') + '</span></div></div>'
          : '<div class="evrow2">' + I_CHECK + '<span>This ticket is closed. A new comment reopens it.</span>' +
            '<button class="btn sm" id="reopenBtn">' + I_UNDO + 'Reopen and comment</button></div>') +
        '</div></div>' +
      '</div><div>' +
        '<div class="card"><div class="card-h"><h3>Details</h3></div>' +
        '<div class="card-b"><div class="kv">' + kv + '</div></div></div>' + hist +
      '</div></div>');

    el('backBtn').addEventListener('click', function(){ location.hash = 'support'; });
    var rb = el('replyBtn');
    if(rb) rb.addEventListener('click', function(){ sendReply(t.id, false); });
    var rcb = el('replyCloseBtn');
    if(rcb) rcb.addEventListener('click', function(){ sendReply(t.id, true); });
    var cb = el('closeBtn');
    if(cb) cb.addEventListener('click', function(){ closeTicket(t.id, false); });
    var rob = el('reopenBtn');
    if(rob) rob.addEventListener('click', function(){ closeTicket(t.id, true); });
  }

  function sendReply(id, andClose){
    var box = el('rBody');
    if(!box.value.trim()) return toast('Write something first.');
    var btn = el(andClose ? 'replyCloseBtn' : 'replyBtn');
    btn.disabled = true;
    UCP.post('store-ticket-reply.php', {id: id, body: box.value}).then(function(res){
      var d = res.data || {};
      btn.disabled = false;
      if(!d.ok) return toast(d.error || 'Could not send that');
      TICKETS = null;
      if(andClose) return closeTicket(id, false);
      loadTicket(id);
    }).catch(function(){ btn.disabled = false; toast('Could not reach the server'); });
  }

  function closeTicket(id, reopen){
    UCP.post('store-ticket-close.php', {id: id, reopen: reopen ? 1 : 0}).then(function(res){
      var d = res.data || {};
      if(!d.ok) return toast(d.error || 'Could not do that');
      toast(reopen ? 'Reopened.' : 'Ticket closed.');
      TICKETS = null;
      loadTicket(id);
    }).catch(function(){ toast('Could not reach the server'); });
  }

  /* ---------------- loading ---------------- */
  function loadTickets(){
    UCP.get('store-tickets.php?scope=' + TSCOPE + '&status=' + TSTATUS + '&page=' + TPAGE +
              '&sort=' + TSORT + '&q=' + encodeURIComponent(TQ))
      .then(function(d){
        if(!d || !d.ok){
          paint('body', '<div class="card"><div class="card-b"><p class="lede">' +
            escapeHtml((d && d.error) || 'Could not load your tickets.') + '</p></div></div>');
          return;
        }
        TICKETS = d; STAFF = !!d.staff; ME = d.me ? d.me.id : 0;
        tabs();
        if(TAB === 'support' && !onOneTicket()) viewSupport();
      });
  }

  function loadTicket(id){
    UCP.get('store-ticket.php?id=' + id).then(function(d){
      if(!d || !d.ok){
        paint('body', '<div class="backline"><button class="btn" id="backBtn">Back to tickets</button></div>' +
          '<div class="card"><div class="card-b"><p class="lede">' +
          escapeHtml((d && d.error) || 'Could not open that ticket.') + '</p></div></div>');
        var bb = el('backBtn');
        if(bb) bb.addEventListener('click', function(){ location.hash = 'support'; });
        return;
      }
      viewTicket(d);
    });
  }

  /* ---------------- routing ---------------- */
  function route(){
    var h = (location.hash || '').replace('#','');
    var m = h.match(/ticket=(\d+)/);
    if(m){ TAB = 'support'; tabs(); return loadTicket(parseInt(m[1], 10)); }

    TAB = ['overview','credits','shop','history','support'].indexOf(h) > -1 ? h : 'overview';
    tabs();
    if(TAB === 'overview') return viewOverview();
    if(TAB === 'credits') return viewCredits();
    if(TAB === 'shop')    return viewShop();
    if(TAB === 'history') return viewHistory();
    return viewSupport();
  }
  window.addEventListener('hashchange', route);

  /* One delegated listener for the whole page body: the credits view is
     redrawn on every pack click, and per-element handlers would multiply
     each time it was. */
  (function(){
    var host = el('body');
    if(!host) return;
    host.addEventListener('click', function(e){
      var pk = e.target.closest('[data-buy]');
      if(pk){ PSEL = parseInt(pk.getAttribute('data-buy'), 10); return viewCredits(); }
      if(e.target.closest('#promoBtn')) return promoModal();
    });
  })();

  /* Draw first, fill in after.
     The page used to wait on three requests before it drew anything, which
     meant any one of them being slow left the store on "Loading…" with no
     way out. Now the first paint happens immediately from what is already
     known, and each request repaints the part it owns when it lands. A
     store that renders and then corrects itself beats one that renders
     nothing.

     The calls are also made one after another rather than all at once:
     PHP serialises requests that touch the same session anyway, so firing
     them in parallel bought nothing and made the queue longer. */
  route();

  /* A single ticket is also TAB === 'support', so a late-arriving fetch
     must not repaint the list over the top of whatever ticket is open. */
  function onOneTicket(){ return /ticket=\d+/.test(location.hash || ''); }

  function refresh(){
    if(onOneTicket()) return;
    if(TAB === 'credits') return viewCredits();
    if(TAB === 'overview') return viewOverview();
    if(TAB === 'support') return viewSupport();
  }

  UCP.get('store-promo.php')
    .then(function(d){
      if(d && d.ok){ PROMO = d.promo || null; MAY_PROMO = !!d.may_edit; refresh(); }
      return UCP.get('session.php');
    })
    .then(function(d){
      if(d && d.name) ME_NAME = d.name;
      if(d && typeof d.credits === 'number'){ BALANCE = d.credits; refresh(); }
      return UCP.get('store-tickets.php?scope=mine&status=live&page=1');
    })
    .then(function(d){
      if(d && d.ok){
        TICKETS = d; STAFF = !!d.staff; ME = d.me ? d.me.id : 0;
        tabs();
        if(TAB === 'support' && !onOneTicket()) viewSupport();
      }
    });

  /* ===== ACCOUNT MENU ===== */
  (function(){
    var btn = document.getElementById('acctBtn'), menu = document.getElementById('acctMenu');
    if(!btn || !menu) return;
    menu.style.display = 'none';
    btn.addEventListener('click', function(e){ e.stopPropagation();
      menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; });
    menu.addEventListener('click', function(e){ e.stopPropagation(); });
    document.addEventListener('click', function(){ menu.style.display = 'none'; });

    /* Log out through fetch, so the browser never lands on the endpoint's raw
       JSON. The href stays a working no-JS fallback. Forget the cached
       identity first — the next person at this computer starts blank. */
    document.getElementById('logoutBtn').addEventListener('click', function(e){
      e.preventDefault();
      this.style.pointerEvents = 'none';
      if(window.UCP && UCP.forgetMe) UCP.forgetMe();
      UCP.post('logout.php', {}).then(function(res){
        var d = res && res.data ? res.data : {};
        window.location.replace(d.redirect || '/login');
      }).catch(function(){ window.location.href = '/api/logout.php?next=/login'; });
    });
  })();

  /* ===================== UTIL ===================== */
  function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  let toastTimer=null;
  function toast(msg){
    const t=document.getElementById('toast');
    document.getElementById('toastMsg').textContent=msg;
    t.classList.add('show'); clearTimeout(toastTimer);
    toastTimer=setTimeout(()=>t.classList.remove('show'),2200);
  }

  /* mobile drawer */
  const scrim=document.getElementById('scrim'), menuToggle=document.getElementById('menuToggle');
  menuToggle.addEventListener('click',()=>{document.body.classList.toggle('nav-open');scrim.classList.toggle('show');});
  scrim.addEventListener('click',()=>{document.body.classList.remove('nav-open');scrim.classList.remove('show');});
  window.addEventListener('resize',()=>{if(window.innerWidth>760){document.body.classList.remove('nav-open');scrim.classList.remove('show');}});

  /* The clock, the build number and the status line are drawn by
     assets/js/ucp.js — one copy for every page. */
</script>