<?php
/**
 * My Profile.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'My Profile — BlaineSide UCP';
$PAGE_HEADING = 'My Profile';
$PAGE_HEAD = <<<'HTML'
<style>
/* ============================================================
   TOKENS — identical to dashboard/index.html. Kept in sync by hand
   for now; these should move to assets/css/ucp.css and be imported
   by both before a third page copies them.
   ============================================================ */
:root{
  --amber:#d4923a;
  --gold:#e2b65c;
  --charcoal:#121110;
  --charcoal-2:#1a1815;
  --charcoal-3:#221f1b;
  --charcoal-4:#2b2723;
  --parchment:#f1efe9;
  --stone:#8a7f70;
  --text-dim:#655e51;
  --text-faint:#968e7e;
  --border:#26221e;
  --border-soft:#1f1c18;
  /* divider inside a card — --border-soft vanishes against --charcoal-2 */
  --rule:#302b25;
  --danger:#c1553f;
  --ok:#7fa05a;
  --warn:#e2b65c;
  --sidebar-w:256px;
  --header-h:66px;
  --content-bg:#100f0e;
}
*{box-sizing:border-box;margin:0;padding:0}
/* display:flex/grid on a component beats the [hidden] attribute's UA rule,
   which is how the empty state ended up rendering underneath the table. */
[hidden]{display:none !important}
html{height:100%}
body{
  font-family:'Inter',system-ui,sans-serif;
  background:var(--content-bg);
  color:var(--parchment);
  -webkit-font-smoothing:antialiased;
  display:flex;min-height:100vh;
  font-size:14px;line-height:1.5;
}
a{color:var(--gold);text-decoration:none}
button{font-family:inherit}
::-webkit-scrollbar{width:9px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--charcoal-4);border-radius:6px}

/* ================= SIDEBAR (matches dashboard) ================= */
.sidebar{width:var(--sidebar-w);flex:none;position:relative;
  background:var(--charcoal-2);border-right:1px solid var(--border-soft);z-index:50}
.side-inner{position:sticky;top:0;height:100vh;display:flex;flex-direction:column;padding-bottom:66px}
.side-brand{display:flex;align-items:center;height:var(--header-h);padding:0 24px;
  border-bottom:1px solid var(--border-soft);flex:none}
.side-brand .name{font-family:'Oswald',sans-serif;font-weight:600;font-size:25px;
  letter-spacing:.07em;text-transform:uppercase;line-height:1;color:var(--parchment)}
.side-brand .name b{color:var(--gold);font-weight:700}
.side-scroll{flex:1;overflow-y:auto;padding:12px 14px 18px}
.nav-group{margin-bottom:1px}
.nav-item{display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:9px;
  font-size:14px;font-weight:500;color:var(--text-faint);cursor:pointer;
  transition:background .14s,color .14s;position:relative;user-select:none}
.nav-item svg.i{width:18px;height:18px;flex:none;stroke-width:1.8}
.nav-item:hover{background:var(--charcoal-3);color:var(--parchment)}
.nav-item.active{background:var(--charcoal-3);color:var(--parchment)}
.nav-item.active::before{content:"";position:absolute;left:-14px;top:9px;bottom:9px;width:3px;
  border-radius:0 3px 3px 0;background:linear-gradient(180deg,var(--gold),var(--amber))}
.nav-item.active svg.i{color:var(--gold)}
.nav-item .lbl{flex:1}
.nav-item .chev{width:15px;height:15px;opacity:.5;transition:transform .2s;flex:none;stroke-width:2}
.nav-group.open > .nav-item .chev{transform:rotate(90deg)}
.sub{max-height:0;overflow:hidden;transition:max-height .26s ease;margin-left:9px;
  border-left:1px solid var(--border);padding-left:8px}
.nav-group.open .sub{max-height:340px}
.sub a{display:block;padding:9px 12px;border-radius:8px;font-size:13px;font-weight:500;
  color:var(--text-faint);transition:.13s;margin:1px 0}
.sub a:hover{background:var(--charcoal-3);color:var(--parchment)}
.sub a.slot-empty{color:var(--text-dim);font-style:italic;cursor:default}
.sub a.slot-empty:hover{background:transparent;color:var(--text-dim)}
.nav-heading{font-size:10.5px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;
  color:var(--stone);padding:18px 12px 8px}
.side-foot{position:absolute;left:0;right:0;bottom:0;background:var(--charcoal-2);
  padding:13px 20px 15px;border-top:1px solid var(--border-soft);display:flex;flex-direction:column;gap:5px}
.foot-line{font-size:11px;color:var(--text-faint);font-variant-numeric:tabular-nums;
  display:flex;align-items:center;gap:5px;flex-wrap:wrap;line-height:1.5}
.foot-line .fv{color:var(--parchment);font-weight:600}
.foot-line .st{display:inline-flex;align-items:center;gap:6px}
.foot-line .st .d{width:6px;height:6px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}

/* ================= MAIN SHELL (matches dashboard) ================= */
.main{flex:1;min-width:0;display:flex;flex-direction:column;position:relative;
  background:transparent}

/* ============================================================
   BACKDROP — the sign-in page's scene, carried through the UCP.

   Four fixed layers behind everything: the Sandy Shores photo, the
   time-of-day tint, a scrim that buys back the contrast the photo
   costs, and the diagonal hairlines from the sign-in page.

   The tint is driven by assets/js/ucp.js, which looks for `.stage`
   and swaps a tod-* class on it — the same code the sign-in page
   uses, so the two can never drift out of step.
   ============================================================ */
.bg-stage{position:fixed;inset:0;top:var(--header-h);left:var(--sidebar-w);z-index:0;pointer-events:none;
  overflow:hidden;background:#0b0a08}
.bg-stage .scene{position:absolute;inset:0;
  background:url('/assets/img/bg-sandy.jpg') center/cover no-repeat;
  /* Present but never competing: this sits behind body copy all day, not
     behind one sign-in card. */
  opacity:.38;transform:scale(1.04)}
.bg-stage .tod{position:absolute;inset:0;transition:background 1s ease}
.bg-stage.tod-night .tod{background:rgba(20,28,56,.54)}
.bg-stage.tod-dawn  .tod{background:rgba(78,44,66,.42)}
.bg-stage.tod-day   .tod{background:rgba(64,46,26,.20)}
.bg-stage.tod-dusk  .tod{background:rgba(58,40,34,.34)}
/* An even vignette: equally dark on all four sides, lightest in the
   middle. The old version graded left-to-right, which read as a black
   band down one edge rather than as a backdrop. */
.bg-stage .bg-scrim{position:absolute;inset:0;background:
    radial-gradient(115% 95% at 50% 50%,
      rgba(10,9,8,.50) 0%, rgba(10,9,8,.74) 62%, rgba(10,9,8,.92) 100%)}
@media (max-width:760px){ .bg-stage{left:0} }
.topbar,.content{position:relative;z-index:1}

/* ---- First paint --------------------------------------------------
   Every value on this page arrives from api/profile.php, so the markup
   is empty for one frame and the browser paints the skeleton before it
   paints the account. Holding the content back until the first render
   turns that flash into one deliberate fade. `ready` is added whatever
   the answer was, including a failure — the error message lives inside
   .content and must not be hidden by the thing meant to hide the
   flicker. */
.content{opacity:0;transform:translateY(8px);
  transition:opacity .34s ease,transform .34s cubic-bezier(.22,.61,.36,1)}
/* The account block is deliberately NOT in this fade. The name and group
   are painted from the cached session while the header is still parsing,
   so they are correct at the first paint; fading them in made this page
   the only one where the top-right corner arrived a third of a second
   after everything else. */
.page-title h1{opacity:0;transition:opacity .34s ease .04s}
body.ready .content{opacity:1;transform:none}
body.ready .page-title h1{opacity:1}
@media (prefers-reduced-motion:reduce){
  .content,.page-title h1{transition:none}
}
.topbar{height:var(--header-h);flex:none;display:flex;align-items:center;gap:16px;
  padding:0 26px;background:var(--charcoal-2);border-bottom:1px solid var(--border);
  box-shadow:0 1px 0 rgba(0,0,0,.4), 0 6px 18px -12px rgba(0,0,0,.7);
  position:sticky;top:0;z-index:45}
.page-title h1{font-size:16px;font-weight:700;letter-spacing:-.01em}
.topbar .spacer{flex:1}
.icon-btn{width:38px;height:38px;flex:none;display:grid;place-items:center;border-radius:10px;
  background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint);cursor:pointer;
  transition:.14s;position:relative}
.icon-btn:hover{color:var(--parchment);background:var(--charcoal-3)}
.icon-btn svg{width:18px;height:18px;stroke-width:1.9}
.divider{width:1px;height:30px;background:var(--border);flex:none}
.account{position:relative}
.account-btn{display:flex;align-items:center;gap:12px;padding:6px 12px;border-radius:10px;
  background:var(--charcoal);border:1px solid var(--border);cursor:pointer;transition:.14s;min-width:170px}
.account-btn:hover{background:var(--charcoal-3)}
.account-meta{display:flex;flex-direction:column;line-height:1.3;flex:1;min-width:0;text-align:left}
.account-meta .u{font-size:13.5px;font-weight:600;color:var(--parchment);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.account-meta .r{font-size:11px;color:var(--amber);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.account-btn .caret{width:15px;height:15px;color:var(--stone);stroke-width:2;flex:none}
.account-btn .acct-ico{display:none}
.account-menu{position:absolute;right:0;top:calc(100% + 10px);width:230px;
  background:var(--charcoal-2);border:1px solid var(--border);border-radius:13px;
  box-shadow:0 24px 50px -18px rgba(0,0,0,.8);padding:8px;z-index:60}
.account-menu .mhead{padding:8px 10px 12px;border-bottom:1px solid var(--border-soft);margin-bottom:6px}
.account-menu .mhead .n{font-size:14px;font-weight:700}
.account-menu .mhead .rr{font-size:12px;color:var(--amber);font-weight:600;margin-top:1px}
.menu-item{display:flex;align-items:center;gap:12px;padding:10px;border-radius:9px;
  font-size:13.5px;font-weight:500;color:var(--text-faint);cursor:pointer;transition:.13s}
.menu-item svg{width:16px;height:16px;stroke-width:1.9;flex:none}
.menu-item:hover{background:var(--charcoal-3);color:var(--parchment)}
.menu-item.on{background:var(--charcoal-3);color:var(--parchment)}
.menu-item.on svg{color:var(--gold)}
.menu-item.danger{color:#d98a78}
.menu-item.danger:hover{background:rgba(193,85,63,.12);color:#eab3a6}
.menu-sep{height:1px;background:var(--border-soft);margin:6px 4px}
.hamburger{display:none}
.scrim{display:none;position:fixed;inset:0;background:rgba(8,7,6,.6);
  backdrop-filter:blur(2px);z-index:48;opacity:0;transition:opacity .22s}
.scrim.show{display:block;opacity:1}

/* ============================================================
   PAGE

   Everything sits on a surface. Nothing floats directly on the
   background gradient — text over the amber bloom is hard to read,
   and a group with no edge has nothing to scan against.
   ============================================================ */
.content{padding:26px 30px 60px;max-width:1180px;width:100%;margin:0 auto}

/* The card is the page's only container. No nesting: a bordered box
   inside a bordered box reads as a mistake, so things that live in a
   card (the FAQ, fact lists, the 2FA detail rows) are separated by
   hairlines rather than boxed again. */
.card{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;
  box-shadow:0 20px 44px -30px rgba(0,0,0,.9);overflow:hidden}
.card + .card{margin-top:20px}
.card-h{display:flex;align-items:baseline;justify-content:space-between;gap:16px;flex-wrap:wrap;
  padding:17px 22px 15px;border-bottom:1px solid var(--rule)}
.card-h h3{font-size:15.5px;font-weight:700;letter-spacing:-.015em}
.card-h .aside{font-size:12.5px;color:var(--text-faint);font-variant-numeric:tabular-nums}
.card-b{padding:6px 22px 16px}
.card-lede{font-size:13px;color:var(--text-faint);line-height:1.65;padding-top:14px;
  max-width:80ch;text-wrap:pretty}
.card-lede + *{margin-top:16px}
.card.danger .card-h{border-bottom-color:rgba(193,85,63,.22)}
.card.danger .card-h h3{color:#dfa294}

/* ---- identity header ---- */
.idcard{display:flex;align-items:center;gap:22px;flex-wrap:wrap;padding:20px 22px}
.idcard .who{flex:1;min-width:250px}
.nameline{display:flex;align-items:center;gap:11px;flex-wrap:wrap}
.nameline h2{font-size:27px;font-weight:700;letter-spacing:-.025em;line-height:1.15}
/* The rank badge lives in assets/css/tones.css, shared with the group
   chips and with the same badge on the read-only record. */
.idmeta{margin-top:8px;font-size:13px;color:var(--text-faint);display:flex;flex-wrap:wrap;
  align-items:center;gap:5px 10px}
.idmeta .sep{color:var(--stone)}
.idmeta b{color:var(--parchment);font-weight:600}
.idchips{display:flex;gap:8px;flex-wrap:wrap}
.chip{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;
  padding:7px 12px 7px 10px;border-radius:9px;border:1px solid var(--border);
  background:var(--charcoal);color:var(--text-faint);white-space:nowrap}
.chip svg{width:14px;height:14px;stroke-width:2;flex:none}
.chip.good{color:#9ec178;border-color:rgba(127,160,90,.34);background:rgba(127,160,90,.09)}
.chip.warn{color:#e3bd72;border-color:rgba(226,182,92,.32);background:rgba(226,182,92,.08)}
.chip.off{color:var(--stone)}
.chip.sm{padding:3px 8px;font-size:10.5px;gap:5px}

/* ---- tab bar --------------------------------------------------------
   A boxed segmented control rather than underlined text. Three sections
   is a real switch, not a hint: it needs an edge you can find without
   hunting, and an active state that holds up over the amber bloom. */
.tabbar{display:inline-flex;gap:4px;margin:20px 0;padding:5px;border-radius:13px;
  background:var(--charcoal-2);border:1px solid var(--border);
  box-shadow:0 14px 32px -26px rgba(0,0,0,.9);max-width:100%;overflow-x:auto;scrollbar-width:none}
.tabbar::-webkit-scrollbar{display:none}
.tab{display:inline-flex;align-items:center;gap:9px;padding:10px 17px;border-radius:9px;
  border:1px solid transparent;background:none;cursor:pointer;white-space:nowrap;
  font-size:13.5px;font-weight:600;color:var(--text-faint);
  transition:background .15s,color .15s,border-color .15s}
.tab svg{width:16px;height:16px;stroke-width:1.9;flex:none;color:var(--stone);transition:color .15s}
.tab:hover{background:var(--charcoal-3);color:var(--parchment)}
.tab:hover svg{color:var(--text-faint)}
.tab[aria-selected="true"]{background:var(--charcoal-4);color:var(--parchment);
  border-color:rgba(226,182,92,.34);box-shadow:0 1px 0 rgba(0,0,0,.35)}
.tab[aria-selected="true"] svg{color:var(--gold)}
.tab:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.tabpanel[hidden]{display:none}

/* ---- profile two-column split ---- */
.pgrid{display:grid;grid-template-columns:minmax(0,1fr) 336px;gap:20px;align-items:start}
.pgrid > .col-r{position:sticky;top:calc(var(--header-h) + 20px)}

/* ---- settings row: label + description left, control right ---- */
.rows > .row:first-child,
.rows > .link-row:first-child,
.rows > .kv:first-child,
.rows > .sess:first-child{padding-top:16px}
.row{display:flex;align-items:flex-start;gap:20px;padding:16px 0}
.row + .row{border-top:1px solid var(--rule)}
.row-l{flex:1;min-width:0}
.row-t{font-size:13.5px;font-weight:600;color:var(--parchment);display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.row-v{font-size:13px;color:var(--text-faint);margin-top:4px;word-break:break-word}
.row-v b{color:var(--parchment);font-weight:600}
.row-d{font-size:12.5px;color:var(--text-faint);margin-top:5px;line-height:1.6;
  max-width:72ch;text-wrap:pretty}
.row-r{flex:none;display:flex;align-items:center;gap:9px;padding-top:1px}

/* ---- buttons ---- */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:9px 15px;border-radius:9px;border:1px solid var(--border);background:var(--charcoal);
  color:var(--text-faint);font-size:13px;font-weight:600;cursor:pointer;
  transition:background .14s,color .14s,border-color .14s,transform .12s;white-space:nowrap}
.btn:hover{background:var(--charcoal-3);color:var(--parchment);border-color:var(--charcoal-4)}
.btn:active{transform:scale(.975)}
.btn:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.btn svg{width:15px;height:15px;stroke-width:2;flex:none}
.btn.primary{background:linear-gradient(180deg,var(--gold),var(--amber));border-color:transparent;
  color:#17140f;font-weight:800;box-shadow:0 10px 22px -12px rgba(212,146,58,.75)}
.btn.primary:hover{filter:brightness(1.06);color:#17140f}
.btn.danger{color:#d98a78;border-color:rgba(193,85,63,.36)}
.btn.danger:hover{background:rgba(193,85,63,.12);color:#eab3a6;border-color:rgba(193,85,63,.55)}
.btn.lg{padding:12px 20px;font-size:13.5px}
.btn[disabled],.btn[aria-disabled="true"]{opacity:.42;pointer-events:none;box-shadow:none}

/* A value the player can't change. Labelled rather than shown as a greyed
   -out button, so it doesn't read as a control that happens to be broken. */
.locked{display:inline-flex;align-items:center;gap:7px;font-size:11.5px;font-weight:600;
  color:var(--stone);padding:7px 12px 7px 10px;border-radius:9px;
  border:1px dashed var(--border);background:var(--charcoal);white-space:nowrap}
.locked svg{width:13px;height:13px;stroke-width:2;flex:none}

/* ---- segmented control ---- */
.seg{display:inline-flex;padding:3px;gap:2px;border-radius:11px;
  background:var(--charcoal);border:1px solid var(--border)}
.seg button{padding:9px 16px;border-radius:8px;border:1px solid transparent;background:none;
  cursor:pointer;font-size:12.5px;font-weight:600;color:var(--text-faint);transition:.14s}
.seg button:hover{color:var(--parchment)}
.seg button[aria-pressed="true"]{background:var(--charcoal-4);color:var(--parchment);
  border-color:rgba(226,182,92,.3)}

/* ---- inline expanding form ---- */
.expand{display:none;padding:2px 0 18px}
.expand.on{display:block;animation:expandIn .22s cubic-bezier(.2,.8,.3,1) both}
@keyframes expandIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
.form{background:var(--charcoal);border:1px solid var(--border);border-radius:12px;
  padding:20px;max-width:620px}
.form-grid{display:grid;gap:16px;max-width:430px}
.fld label{display:block;font-size:12.5px;font-weight:600;color:var(--parchment);margin-bottom:7px}
.fld input,.fld select,.fld textarea{width:100%;background:var(--charcoal-2);
  border:1px solid var(--border);border-radius:9px;padding:11px 13px;color:var(--parchment);
  font-family:inherit;font-size:13.5px;outline:none;transition:border-color .14s,box-shadow .14s}
.fld input::placeholder,.fld textarea::placeholder{color:var(--stone)}
.fld input:focus,.fld select:focus,.fld textarea:focus{border-color:var(--amber);
  box-shadow:0 0 0 3px rgba(212,146,58,.14)}
/* A validated field: the border carries the state and the hint line under it
   turns into the result, so there is one place to look rather than two. */
.ctl{position:relative}
.fld.chk input{padding-right:42px}
.fld.ok input{border-color:rgba(127,160,90,.6);box-shadow:0 0 0 3px rgba(127,160,90,.12);
  background:rgba(127,160,90,.04)}
.fld.err input{border-color:rgba(193,85,63,.72);box-shadow:0 0 0 3px rgba(193,85,63,.13);
  background:rgba(193,85,63,.05)}
.fld.ok input:focus{border-color:rgba(127,160,90,.85)}
.fld.err input:focus{border-color:rgba(193,85,63,.9)}
.ind{position:absolute;right:13px;top:50%;transform:translateY(-50%);width:18px;height:18px;
  display:grid;place-items:center;pointer-events:none;opacity:0;transition:opacity .15s}
.fld.ok .ind,.fld.err .ind,.fld.busy .ind{opacity:1}
.ind svg{width:18px;height:18px;stroke-width:2.4}
.fld.ok .ind{color:var(--ok)}
.fld.err .ind{color:#d98a78}
.fld.busy .ind{color:var(--stone)}
.spin{width:14px;height:14px;border-radius:50%;border:2px solid var(--charcoal-4);
  border-top-color:var(--stone);animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.fld .hint{font-size:11.5px;color:var(--text-faint);margin-top:6px;line-height:1.5;max-width:52ch;
  display:flex;align-items:flex-start;gap:6px}
.fld .hint.ok{color:#9ec178}
.fld .hint.err{color:#dd8878}
.fld .hint b{color:inherit;font-weight:700}
.form-acts{display:flex;gap:9px;margin-top:20px;flex-wrap:wrap}

/* password strength — same three rules as reset-confirm.html */
.meter{display:flex;gap:5px;margin-top:10px}
.meter i{height:4px;flex:1;border-radius:3px;background:var(--charcoal-4);transition:background .25s}
.meter.s1 i:nth-child(1){background:var(--danger)}
.meter.s2 i:nth-child(-n+2){background:var(--amber)}
.meter.s3 i:nth-child(-n+3){background:var(--gold)}
.meter.s4 i{background:var(--ok)}
.reqs{display:flex;flex-wrap:wrap;gap:7px;margin-top:11px}
.req{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;
  color:var(--text-faint);border:1px solid var(--border);border-radius:100px;padding:5px 11px}
.req svg{width:11px;height:11px;stroke-width:2.6}
.req.met{color:#9ec178;border-color:rgba(127,160,90,.35);background:rgba(127,160,90,.08)}

/* ---- notes / callouts ---- */
.note{display:flex;gap:11px;padding:13px 15px;border-radius:11px;font-size:12.5px;line-height:1.65;
  border:1px solid var(--border);background:var(--charcoal);color:var(--text-faint);
  margin-top:16px;max-width:88ch;text-wrap:pretty}
.note svg{width:16px;height:16px;flex:none;stroke-width:2;margin-top:1px;color:var(--stone)}
.note b{color:var(--parchment);font-weight:700}
.note.amber{border-color:rgba(212,146,58,.3);background:rgba(212,146,58,.07);color:#dcbd8b}
.note.amber svg{color:var(--gold)}
.note.amber b{color:#f3e2c4}
.note.red{border-color:rgba(193,85,63,.34);background:rgba(193,85,63,.08);color:#dfa294}
.note.red svg{color:#d98a78}
.note.red b{color:#f4d4cb}
.note.green{border-color:rgba(127,160,90,.32);background:rgba(127,160,90,.08);color:#b6cd97}
.note.green svg{color:#9ec178}
.note.green b{color:#dcecc6}
.blank.soon .ei{color:var(--stone);background:rgba(255,255,255,.02)}

/* ---- linked accounts ---- */
.link-row{display:flex;align-items:center;gap:14px;padding:15px 0}
.link-row + .link-row{border-top:1px solid var(--rule)}
.link-mark{width:40px;height:40px;border-radius:11px;flex:none;display:grid;place-items:center;
  background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint)}
.link-mark svg{width:19px;height:19px;stroke-width:1.9}
.link-mark.forum{color:var(--gold);background:rgba(212,146,58,.11);border-color:rgba(212,146,58,.26)}
.link-mark.discord{color:#a8afd4;background:rgba(107,116,168,.16);border-color:rgba(107,116,168,.3)}
.link-mark.game{color:#8fb4d6;background:rgba(120,160,200,.12);border-color:rgba(120,160,200,.26)}
.link-body{flex:1;min-width:0}
.link-body .n{font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.link-body .s{font-size:12.5px;color:var(--text-faint);margin-top:4px;line-height:1.55}
.link-body .s b{color:var(--parchment);font-weight:600}
.link-body .s.none{color:var(--stone)}
.link-body .s.mono b{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.02em}
.link-body .s .lic{color:var(--stone);font-weight:500}

/* ---- your account: a stacked key/value list, not a boxed grid ---- */
.kv{display:flex;align-items:baseline;justify-content:space-between;gap:14px;padding:12px 0}
.kv + .kv{border-top:1px solid var(--rule)}
.kv .k{font-size:12.5px;color:var(--text-faint);font-weight:500;flex:none}
.kv .v{font-size:13.5px;font-weight:600;text-align:right;font-variant-numeric:tabular-nums;
  display:flex;align-items:center;gap:7px;justify-content:flex-end;flex-wrap:wrap}
.kv .v .sm{font-size:12px;font-weight:500;color:var(--text-faint);font-variant-numeric:normal}
.kv .v a.ext{display:inline-flex;align-items:center;gap:6px;color:var(--parchment);
  border-bottom:1px solid rgba(226,182,92,.35);transition:.16s}
.kv .v a.ext:hover{color:var(--gold);border-bottom-color:var(--gold)}
.kv .v a.ext svg{width:12px;height:12px;stroke-width:2;color:var(--stone);flex:none}
.kv .v a.ext:hover svg{color:var(--gold)}
.copy{background:none;border:none;padding:2px;color:var(--stone);cursor:pointer;
  display:grid;place-items:center;border-radius:5px;transition:.13s}
.copy:hover{color:var(--gold)}
.copy svg{width:14px;height:14px;stroke-width:2}

/* ============================================================
   SECURITY
   ============================================================ */
.tfa-head{display:flex;align-items:flex-start;gap:16px;padding:22px 22px 20px}
.tfa-shield{width:46px;height:46px;border-radius:13px;flex:none;display:grid;place-items:center;
  background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint)}
.tfa-shield svg{width:23px;height:23px;stroke-width:1.8}
.card[data-on="1"] .tfa-shield{color:var(--ok);background:rgba(127,160,90,.11);border-color:rgba(127,160,90,.3)}
.tfa-head .t{flex:1;min-width:0}
.tfa-head h4{font-size:16.5px;font-weight:700;letter-spacing:-.015em;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.tfa-state{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;
  padding:3px 9px;border-radius:100px;background:var(--charcoal-4);color:var(--text-faint)}
.card[data-on="1"] .tfa-state{background:rgba(127,160,90,.16);color:#9ec178}
.tfa-head p{font-size:13px;color:var(--text-faint);line-height:1.65;margin-top:8px;
  max-width:80ch;text-wrap:pretty}
.tfa-body{padding:0 22px 20px}
.tfa-foot{display:flex;gap:9px;flex-wrap:wrap;padding:16px 22px;
  border-top:1px solid var(--rule);background:rgba(0,0,0,.2);border-radius:0 0 13px 13px}

/* three prerequisites in no particular order — check marks, not step numbers */
.need{list-style:none;margin-top:18px;display:grid;gap:11px}
.need li{position:relative;padding-left:28px;font-size:13px;
  color:var(--text-faint);line-height:1.6;max-width:78ch;text-wrap:pretty}
.need li::before{content:"";position:absolute;left:2px;top:5px;width:12px;height:8px;
  border-left:2px solid var(--gold);border-bottom:2px solid var(--gold);
  transform:rotate(-45deg);border-radius:1px}
.need li b{color:var(--parchment);font-weight:600}
.need.loss li::before{border:none;width:11px;height:2px;background:#d98a78;transform:none;
  top:11px;left:3px;border-radius:2px}

/* method + recovery rows — hairlines, no second box */
.tfa-detail{margin-top:18px}
.tfa-detail > div{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 0}
.tfa-detail > div + div{border-top:1px solid var(--rule)}
.tfa-detail .k{font-size:12.5px;color:var(--text-faint);font-weight:600;width:150px;flex:none}
.tfa-detail .v{font-size:13.5px;font-weight:600;flex:1;min-width:140px}
.tfa-detail .v .sm{display:block;font-size:12px;font-weight:500;color:var(--text-faint);margin-top:3px}
.codes-left{display:flex;align-items:center;gap:10px}
.pips{display:flex;gap:3px}
.pips i{width:7px;height:14px;border-radius:2px;background:var(--ok);opacity:.85}
.pips i.spent{background:var(--charcoal-4);opacity:1}
.tfa-detail .v.low{color:#e3bd72}
.tfa-detail .v.low .pips i:not(.spent){background:var(--warn)}

/* setup steps */
.steps{display:flex;align-items:center;gap:10px;margin-bottom:22px;flex-wrap:wrap}
.step{display:flex;align-items:center;gap:9px;font-size:12.5px;font-weight:600;color:var(--text-faint)}
.step .n{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:11px;
  font-weight:800;border:1px solid var(--border);background:var(--charcoal);color:var(--text-faint)}
.step.on{color:var(--parchment)}
.step.on .n{background:linear-gradient(145deg,var(--gold),var(--amber));border-color:transparent;color:#17140f}
.step.done .n{background:rgba(127,160,90,.16);border-color:rgba(127,160,90,.34);color:#9ec178}
.step-line{flex:1;min-width:16px;height:1px;background:var(--border)}

.scan{display:flex;gap:28px;flex-wrap:wrap;align-items:flex-start}
.qrbox{width:196px;padding:12px;background:#fff;border-radius:12px;line-height:0;flex:none}
.qrbox svg{width:172px;height:172px;display:block}
.scan-side{flex:1;min-width:250px;max-width:420px}
.keyval{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13.5px;
  font-weight:700;color:var(--gold);letter-spacing:.05em;line-height:1.8;word-break:break-all;
  padding:12px 14px;border:1px dashed var(--border);border-radius:10px;background:var(--charcoal);
  cursor:pointer;margin-top:8px}
.keyval:hover{border-color:var(--stone)}
.codeinput input{text-align:center;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  font-size:23px;font-weight:700;letter-spacing:.4em;text-indent:.4em;padding:13px}

/* recovery code sheet */
.codes{display:grid;grid-template-columns:repeat(5,1fr);gap:9px;margin-top:18px;max-width:880px}
.codes span{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13.5px;
  font-weight:700;letter-spacing:.04em;text-align:center;padding:11px 6px;border-radius:9px;
  border:1px solid var(--border);background:var(--charcoal);font-variant-numeric:tabular-nums}
.ack{display:flex;align-items:flex-start;gap:11px;margin-top:20px;font-size:13px;
  color:var(--text-faint);cursor:pointer;line-height:1.5}
.ack .box{width:19px;height:19px;flex:none;border-radius:6px;border:1px solid var(--border);
  background:var(--charcoal);display:grid;place-items:center;margin-top:1px;transition:.14s}
.ack .box svg{width:12px;height:12px;stroke-width:3;color:transparent}
.ack.on .box{background:var(--amber);border-color:var(--amber)}
.ack.on .box svg{color:#17140f}

/* ---- guidance disclosures — flush inside the card, no second border ---- */
.faq{margin-top:16px;display:grid;gap:8px}
.faq details{border:1px solid var(--rule);border-radius:11px;background:var(--charcoal);
  overflow:hidden;transition:border-color .15s}
.faq details[open]{border-color:rgba(226,182,92,.32)}
.faq summary{list-style:none;cursor:pointer;padding:14px 16px;font-size:13.5px;font-weight:600;
  display:flex;align-items:center;gap:12px;transition:background .14s,color .14s;color:var(--parchment)}
.faq summary::-webkit-details-marker{display:none}
.faq summary:hover{background:var(--charcoal-3)}
.faq summary:focus-visible{outline:2px solid var(--gold);outline-offset:-2px}
.faq summary .cv{width:15px;height:15px;flex:none;color:var(--stone);stroke-width:2.4;
  transition:transform .2s;margin-left:auto}
.faq details[open] summary .cv{transform:rotate(90deg);color:var(--gold)}
.faq details[open] summary{color:var(--gold)}
/* The answer runs the full width of the card rather than sitting in a narrow
   column with dead space beside it. */
.faq .a{padding:2px 16px 18px;font-size:13.5px;color:var(--text-faint);line-height:1.8;
  border-top:1px solid var(--rule);padding-top:14px;margin:0 0 0 0}
.faq .a p + p{margin-top:12px}
.faq .a b{color:var(--parchment);font-weight:600}

/* ---- sessions + activity ---- */
.sess{display:flex;align-items:center;gap:14px;padding:15px 0}
.sess + .sess{border-top:1px solid var(--rule)}
.sess-ico{width:38px;height:38px;border-radius:10px;flex:none;display:grid;place-items:center;
  background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint)}
.sess-ico svg{width:18px;height:18px;stroke-width:1.8}
.sess-body{flex:1;min-width:0}
.sess-body .n{font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.sess-body .s{font-size:12.5px;color:var(--text-faint);margin-top:3px}
.sess-body .stamp-utc{color:var(--text-dim);font-variant-numeric:tabular-nums;margin-top:2px}
.tagnow{font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
  padding:3px 8px;border-radius:100px;background:rgba(127,160,90,.16);color:#9ec178}

.log{position:relative;margin-top:18px;padding-left:26px}
.log::before{content:"";position:absolute;left:8px;top:8px;bottom:12px;width:1px;background:var(--border)}
.log-row{position:relative;padding:0 0 20px}
.log-row:last-child{padding-bottom:2px}
.log-dot{position:absolute;left:-22px;top:4px;width:9px;height:9px;border-radius:50%;
  background:var(--stone);border:2px solid var(--charcoal-2);box-shadow:0 0 0 1px var(--border)}
.log-row.good .log-dot{background:var(--ok);box-shadow:0 0 0 1px rgba(127,160,90,.5)}
.log-row.warn .log-dot{background:var(--warn);box-shadow:0 0 0 1px rgba(226,182,92,.5)}
.log-t{font-size:13.5px;font-weight:500;line-height:1.5}
.log-t b{font-weight:700}
.log-s{font-size:12px;color:var(--text-faint);margin-top:3px;font-variant-numeric:tabular-nums}

/* ============================================================
   CHARACTERS
   ============================================================ */
.chars{display:grid;gap:10px;margin-top:16px}
.char{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:11px;
  border:1px solid var(--rule);background:var(--charcoal)}
.char-ico{width:42px;height:42px;border-radius:11px;flex:none;display:grid;place-items:center;
  background:var(--charcoal-3);border:1px solid var(--border);color:var(--stone)}
.char-ico svg{width:20px;height:20px;stroke-width:1.8}
.char.filled .char-ico{color:var(--gold);background:rgba(212,146,58,.1);border-color:rgba(212,146,58,.26)}
.char-body{flex:1;min-width:0}
.char-body .n{font-size:14px;font-weight:700;display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.char-body .s{font-size:12.5px;color:var(--text-faint);margin-top:4px}
.char.empty{border-style:dashed;background:transparent}
.char.empty .char-body .n{color:var(--stone);font-weight:600}
.char-state{font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
  padding:3px 8px;border-radius:100px;background:rgba(127,160,90,.16);color:#9ec178}
.char-state.away{background:var(--charcoal-4);color:var(--text-faint)}

/* ============================================================
   ADMINISTRATIVE RECORD

   One summary panel, a collapsed list of past appeals, the staff
   Scratchpad, and one card per kind of entry. The staff view and the
   player's own view are the same markup driven by the same data from
   api/_punish.php — the only differences are drawn from flags on the
   payload, so the two pages cannot describe an account differently.
   ============================================================ */

/* ---- the summary panel ----
   Standalone. It used to sit inside a "Record summary" card, which put a
   bordered panel inside a bordered card to say one thing twice. */
.cert{position:relative;border-radius:14px;border:1px solid var(--border);
  background:linear-gradient(168deg,var(--charcoal-3),var(--charcoal) 62%);overflow:hidden;
  box-shadow:0 20px 44px -30px rgba(0,0,0,.9)}
.cert::before{content:"";position:absolute;inset:0;pointer-events:none;
  background:radial-gradient(120% 90% at 88% -20%,rgba(226,182,92,.07),transparent 62%)}
.cert.held::before{background:radial-gradient(120% 90% at 88% -20%,rgba(193,85,63,.1),transparent 62%)}
.cert + .card,.cert + *{margin-top:20px}
/* The kind cards live in their own wrapper, so `.card + .card` never fires
   between the last card above them — Previous appeals on the player's view,
   the Scratchpad on the staff view — and the first of them. */
#recCards{margin-top:20px}
#recCards > .card + .card{margin-top:20px}

/* The banner. This page's one job in a screenshot is to be unambiguous at
   thumbnail size, where the words are gone but the colour is not. */
.cert-top{position:relative;display:flex;align-items:baseline;gap:11px;
  padding:13px 20px 14px;border-bottom:1px solid var(--rule)}
.cert-dot{width:7px;height:7px;border-radius:50%;flex:none;position:relative;top:-1px;
  background:var(--ok)}
.cert-line{font-size:12.5px;line-height:1.6;min-width:0}
.cert-line b{font-size:11.5px;font-weight:800;letter-spacing:.085em;text-transform:uppercase;
  margin-right:11px;white-space:nowrap}
.cert-line span{color:var(--text-faint)}
.cert.good  .cert-top{background:rgba(127,160,90,.08);border-bottom-color:rgba(127,160,90,.2)}
.cert.watch .cert-top{background:rgba(226,182,92,.07);border-bottom-color:rgba(226,182,92,.2)}
.cert.held  .cert-top{background:rgba(193,85,63,.09);border-bottom-color:rgba(193,85,63,.24)}
.cert.good  .cert-dot{background:var(--ok)}
.cert.watch .cert-dot{background:var(--warn)}
.cert.held  .cert-dot{background:#d0705c}
.cert.good  .cert-line b{color:#9ec178}
.cert.watch .cert-line b{color:#e3bd72}
.cert.held  .cert-line b{color:#dfa294}

.cert-stats{position:relative;display:grid;grid-template-columns:repeat(5,1fr)}
.cert-stats > div{padding:13px 16px 14px}
.cert-stats > div + div{border-left:1px solid var(--rule)}
.cert-stats .k{font-size:10.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;
  color:var(--stone)}
.cert-stats .v{font-size:19px;font-weight:700;letter-spacing:-.02em;margin-top:7px;line-height:1.1;
  font-variant-numeric:tabular-nums;color:var(--parchment)}
.cert-stats .v.zero{color:var(--stone)}
.cert-stats .v.hit{color:#e3bd72}
.cert-stats .v.bad{color:#e0917f}
.cert-stats .v.sm{font-size:14px;line-height:1.35;letter-spacing:0}
.cert-stats .p{font-size:11px;color:var(--text-dim);margin-top:4px;line-height:1.45}

/* The strap line is what makes a screenshot of this self-dating and, on the
   staff view, attributable. A picture of a record with no capture time
   proves nothing about when it was true. */
.cert-foot{position:relative;display:flex;align-items:center;gap:4px 12px;flex-wrap:wrap;
  padding:10px 20px 11px;border-top:1px solid var(--rule);background:rgba(0,0,0,.22);
  font-size:11px;color:var(--text-dim);font-variant-numeric:tabular-nums}
.cert-foot b{color:var(--text-faint);font-weight:600}
.cert-foot .dot{color:var(--text-dim);opacity:.6}

/* ---- the kind marker in a card header ----
   Four labelled, coloured cards is a clearer table of contents than a strip
   of filter buttons above one merged list, and it costs no clicks to read. */
.dotk{width:9px;height:9px;border-radius:50%;flex:none;
  box-shadow:0 0 0 3px rgba(255,255,255,.03)}
.dotk.ban{background:#d98a78} .dotk.warn{background:#e3bd72}
.dotk.kick{background:#8fb0c4} .dotk.lock{background:#9d9384}
.tally-pill{font-size:11.5px;font-weight:700;font-variant-numeric:tabular-nums;
  border-radius:100px;padding:3px 10px;background:var(--charcoal-3);color:var(--text-faint);
  border:1px solid var(--border)}
.tally-pill.hot{color:#e0917f;border-color:rgba(193,85,63,.32);background:rgba(193,85,63,.09)}

/* ---- a collapsible card ---- */
.foldh{width:100%;background:none;border:none;cursor:pointer;text-align:left;
  display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
  padding:17px 22px 15px;transition:background .14s;font-family:inherit}
.foldh:hover{background:var(--charcoal-3)}
.foldh h3{font-size:15.5px;font-weight:700;letter-spacing:-.015em;color:var(--parchment);
  display:flex;align-items:center;gap:11px}
.foldh .r{display:flex;align-items:center;gap:11px;font-size:12.5px;color:var(--text-faint);
  font-variant-numeric:tabular-nums}
.foldh .cv{width:17px;height:17px;stroke-width:2.2;color:var(--stone);transition:transform .2s}
.fold.open .foldh{border-bottom:1px solid var(--rule)}
.fold.open .foldh .cv{transform:rotate(90deg)}
.fold .card-b{display:none}
.fold.open .card-b{display:block}
.foldh:focus-visible{outline:2px solid var(--gold);outline-offset:-2px}

/* ---- previous appeals ----
   Four aligned columns. The pills line up, the chevrons line up, and the
   highlight lands on exactly the same box as the rule above it. */
.aplist{margin-top:2px}
/* Boxed, like the Scratchpad's notes. Four aligned columns inside each box:
   the pills line up, the chevrons line up, and the row a hover highlights is
   unmistakably one row. */
.aprow{display:grid;grid-template-columns:46px minmax(0,1fr) 90px 14px;align-items:center;
  gap:0 16px;padding:12px 15px;border-radius:11px;
  background:var(--charcoal-3);border:1px solid var(--border-soft);
  cursor:pointer;transition:background .13s,border-color .13s}
.aprow + .aprow{margin-top:9px}
.aprow:hover{background:var(--charcoal-4);border-color:rgba(226,182,92,.26)}
.aprow:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.aprow .num{font-size:11.5px;font-weight:700;color:var(--text-dim);letter-spacing:.02em;
  font-variant-numeric:tabular-nums;transition:color .12s}
.aprow:hover .num{color:var(--gold)}
.aprow .t{display:block;font-size:13px;font-weight:700;color:var(--parchment);line-height:1.45;
  overflow-wrap:anywhere}
.aprow .t .vs{font-weight:500;color:var(--text-faint)}
.aprow .s{display:block;font-size:11.5px;color:var(--text-dim);margin-top:3px;line-height:1.5;
  font-variant-numeric:tabular-nums}
.aprow .s b{color:var(--text-faint);font-weight:600}
.aprow .s .dot{padding:0 5px;opacity:.55}
.aprow .ap{justify-self:stretch;text-align:center;padding:3px 0;font-size:11px;letter-spacing:.02em}
.aprow .go{width:14px;height:14px;stroke-width:2.4;color:var(--text-dim);
  transition:color .12s,transform .12s;justify-self:end}
.aprow:hover .go{color:var(--gold);transform:translateX(2px)}
#appealPager .pager{padding-top:16px}

/* ---- one entry ---- */
.list{margin-top:4px}
/* Boxed, the same as the appeal rows and the Scratchpad's notes. A run of
   entries separated only by a hairline reads as one block of text with dates
   in it; a box per entry gives the eye something to stop at. */
.ent{padding:13px 15px 14px;position:relative;border-radius:11px;
  background:var(--charcoal-3);border:1px solid var(--border-soft);
  transition:border-color .13s}
.ent + .ent{margin-top:9px}
.ent-1{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.stampd{font-size:12.5px;font-weight:700;color:var(--parchment);font-variant-numeric:tabular-nums;
  white-space:nowrap}
.stampd .t{color:var(--text-faint);font-weight:500;margin-left:7px}
.kind{display:inline-flex;align-items:center;gap:7px;font-size:11.5px;font-weight:700;
  padding:4px 10px;border-radius:100px;white-space:nowrap;
  border:1px solid var(--rule);background:var(--charcoal);color:var(--text-faint)}
.kind::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.kind.warn{color:#e3bd72;border-color:rgba(226,182,92,.3);background:rgba(226,182,92,.08)}
.kind.kick{color:#93b6cb;border-color:rgba(110,150,175,.34);background:rgba(110,150,175,.11)}
.kind.ban {color:#d98a78;border-color:rgba(193,85,63,.34);background:rgba(193,85,63,.1)}
/* A lock is not a punishment, so it does not get a punishment's colour. */
.kind.lock{color:#b3a894;border-color:rgba(157,147,132,.34);background:rgba(157,147,132,.1)}
.ref{font-size:11px;color:var(--text-dim);font-variant-numeric:tabular-nums}
.state{margin-left:auto;display:inline-flex;align-items:center;gap:9px;flex-wrap:wrap;
  justify-content:flex-end}
.st{font-size:12px;font-weight:700;white-space:nowrap}
.st.live{color:#e79187} .st.done{color:var(--stone)}
.ap{font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:100px;white-space:nowrap;
  border:1px solid var(--rule);background:var(--charcoal);display:inline-block}
.ap.accepted{color:#9fae8d;border-color:rgba(127,160,90,.3);background:rgba(127,160,90,.08)}
.ap.rejected{color:#d29b8d;border-color:rgba(193,85,63,.3);background:rgba(193,85,63,.08)}
.ap.pending {color:#e3bd72;border-color:rgba(226,182,92,.3);background:rgba(226,182,92,.08)}
.ap.none{color:var(--text-dim)}
.ent-why{font-size:13.5px;color:var(--parchment);line-height:1.6;margin-top:9px;
  overflow-wrap:anywhere;text-wrap:pretty}
.ent-why.empty{color:var(--text-dim);font-style:italic}
.ent-meta{display:flex;flex-wrap:wrap;gap:4px 9px;margin-top:8px;font-size:12px;
  color:var(--text-faint);align-items:center}
.ent-meta b{color:var(--parchment);font-weight:600}
.ent-meta .dot{color:var(--text-dim)}
.ent-meta .edit{font-style:italic;color:var(--text-dim)}
/* An administrator's name carries their group's colour here, the same as it
   does everywhere else in the UCP. */
.ent-meta .who,.note-h .who{color:var(--tone-text);font-weight:600}
.acts{display:flex;flex-wrap:wrap;gap:14px;margin-top:9px}
.acts button{background:none;border:none;padding:0;font-family:inherit;font-size:11.5px;
  font-weight:700;color:var(--text-dim);cursor:pointer;transition:color .14s}
.acts button:hover{color:var(--gold)}
.acts button.dg:hover{color:#e08d7c}
.acts button:focus-visible{outline:2px solid var(--gold);outline-offset:3px;border-radius:3px}

/* ---- a row being changed ----
   A tint and a coloured left rule rather than a bordered box: it is the same
   row in a different mode, not a new thing beside it. */
.ent.editing{background:rgba(226,182,92,.05);border-color:rgba(226,182,92,.34);
  box-shadow:inset 3px 0 0 rgba(226,182,92,.5)}
.ent.deleting,.pnote.deleting{background:rgba(193,85,63,.07);
  border-color:rgba(193,85,63,.4);box-shadow:inset 3px 0 0 rgba(193,85,63,.6)}
.modehead{display:flex;align-items:center;gap:9px;margin-top:12px;font-size:11px;font-weight:800;
  letter-spacing:.09em;text-transform:uppercase;color:var(--gold)}
.deleting .modehead{color:#e0917f}
.modehead svg{width:13px;height:13px;stroke-width:2.2}
.fld{display:block;width:100%;margin-top:10px;padding:11px 13px;border-radius:10px;
  border:1px solid var(--border);background:var(--charcoal);color:var(--parchment);
  font-family:inherit;font-size:13.5px;line-height:1.6;resize:vertical}
.fld:focus{outline:none;border-color:rgba(226,182,92,.45)}
.deleting .fld:focus{border-color:rgba(193,85,63,.5)}
.fldnote{font-size:11.5px;color:var(--text-dim);margin-top:8px;line-height:1.6;text-wrap:pretty}
.warnbox{font-size:12.5px;color:#dfa294;line-height:1.6;margin-top:10px;text-wrap:pretty}
.warnbox b{color:#eab3a6;font-weight:700}
.rowbtns{display:flex;gap:9px;flex-wrap:wrap;margin-top:13px;align-items:center}
.mini{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:9px;
  border:1px solid var(--border);background:var(--charcoal);color:var(--text-faint);
  font-family:inherit;font-size:12.5px;font-weight:700;cursor:pointer;transition:.14s}
.mini:hover{color:var(--parchment);border-color:var(--charcoal-4)}
.mini.gold{background:linear-gradient(145deg,var(--gold),var(--amber));color:#1a1206;border:none}
.mini.gold:hover{filter:brightness(1.06)}
.mini.red{background:rgba(193,85,63,.14);border-color:rgba(193,85,63,.45);color:#eab3a6}
.mini.red:hover:not([disabled]){background:rgba(193,85,63,.24);color:#f2c8bd}
.mini[disabled]{opacity:.4;cursor:not-allowed}
.confirmline{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--text-faint);
  margin-top:13px;cursor:pointer}
.confirmline input[type=checkbox]{width:16px;height:16px;accent-color:var(--danger);flex:none}

/* ---- nothing in a card ----
   The header already says "None on file"; this is the one line that
   completes it. An icon tile and a bold heading were two more elements
   saying the same thing. */
.secblank{padding:26px 0 12px;text-align:center;font-size:13px;color:var(--text-dim);
  line-height:1.6}

/* ---- the Scratchpad ---- */
.hidden-badge{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:700;
  color:#e3bd72;background:rgba(226,182,92,.1);border:1px solid rgba(226,182,92,.28);
  padding:4px 10px;border-radius:100px;white-space:nowrap}
.hidden-badge svg{width:12px;height:12px;stroke-width:2.2}
.padbox{margin-top:15px}
.padbox textarea{width:100%;min-height:80px;padding:12px 14px;border-radius:11px;
  border:1px solid var(--border);background:var(--charcoal);color:var(--parchment);
  font-family:inherit;font-size:13px;line-height:1.6;resize:vertical}
.padbox textarea:focus{outline:none;border-color:rgba(226,182,92,.42)}
.padfoot{display:flex;align-items:center;justify-content:space-between;gap:12px;
  flex-wrap:wrap;margin-top:10px}
.padfoot .hint{font-size:11.5px;color:var(--text-dim);flex:1 1 320px;line-height:1.55}
.notes{margin-top:8px}
/* `.note` is already a callout component on the lookup page, so the
   Scratchpad's own rows are `.pnote`. */
/* Each note is boxed. Hairline-separated rows are right for the record,
   where every entry has the same shape and the eye is scanning a column —
   but a note is a paragraph of somebody's prose, and a run of them with only
   a rule between was one wall of text with names in it. */
.pnote{padding:12px 15px 13px;border-radius:11px;
  background:var(--charcoal-3);border:1px solid var(--border-soft)}
.pnote + .pnote{margin-top:9px}

.note-h{display:flex;align-items:center;gap:9px;flex-wrap:wrap;font-size:12px;
  color:var(--text-faint);font-variant-numeric:tabular-nums}
.note-h b{color:var(--parchment);font-weight:700;font-size:12.5px}
.note-h .rk{font-size:10.5px;font-weight:700;color:var(--tone-text)}
.note-h .dot{color:var(--text-dim)}
.note-h .del{margin-left:auto;background:none;border:none;font-family:inherit;font-size:11.5px;
  font-weight:700;color:var(--text-dim);cursor:pointer}
.note-h .del:hover{color:#e08d7c}
.note-b{font-size:13.5px;line-height:1.65;margin-top:7px;color:var(--parchment);
  overflow-wrap:anywhere;text-wrap:pretty}

/* ---- pagination ----
   Three numbers and two arrows, at every length. A pager that grows a button
   per page is a control whose size depends on the data — it looks different
   on every account and turns into a wall on a long record. */
.pager{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
  margin-top:17px;padding-top:15px;border-top:1px solid var(--rule)}
.pcount{font-size:12px;color:var(--text-faint);font-variant-numeric:tabular-nums}
.pcount b{color:var(--parchment);font-weight:600}
.pnav{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
.pnav button{min-width:33px;height:33px;padding:0 9px;border-radius:9px;border:1px solid var(--rule);
  background:var(--charcoal);color:var(--text-faint);font-family:inherit;font-size:12.5px;
  font-weight:600;cursor:pointer;display:grid;place-items:center;
  font-variant-numeric:tabular-nums;transition:.14s}
.pnav button:hover:not([disabled]){color:var(--parchment);border-color:var(--charcoal-4)}
.pnav button[aria-current="true"]{background:var(--charcoal-4);color:var(--parchment);
  border-color:rgba(226,182,92,.38)}
.pnav button[disabled]{opacity:.3;cursor:default}
.pnav .arrow{min-width:33px;padding:0}
.pnav .arrow svg{width:15px;height:15px;stroke-width:2.3}

/* Everything on this tab draws its own SVGs; one rule saves repeating the
   two attributes on each of them. */
#p-record svg{fill:none;stroke:currentColor}

/* Every lede on this tab is a caption above a list, not a paragraph somebody
   settles in to read. The 80-character measure that suits prose elsewhere
   left a third of each row empty and cost a line on all four cards. */
#p-record .card-lede{max-width:none}

@media (max-width:980px){
  .cert-stats{grid-template-columns:repeat(2,1fr)}
  .cert-stats > div{border-left:none !important;border-top:1px solid var(--rule)}
  .cert-stats > div:nth-child(2n){border-left:1px solid var(--rule) !important}
}
@media (max-width:680px){
  .cert-stats{grid-template-columns:1fr}
  .cert-stats > div:nth-child(2n){border-left:none !important}
  .state{margin-left:0;width:100%;justify-content:flex-start}
  .pager{flex-direction:column;align-items:stretch;gap:12px}
  .pnav{justify-content:center}
  .rowbtns .mini{flex:1 1 auto;justify-content:center}
  .aprow{grid-template-columns:40px minmax(0,1fr) auto;gap:0 12px;padding:12px 13px}
  .aprow .go{display:none}
}
/* The old pager and the screenshot stamp lived here. The pager is now
   declared once, above, in the record block; the stamp was replaced by
   the strap line inside the summary panel. */
.blank{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;
  padding:44px 24px 40px}
.blank .ei{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;
  background:rgba(127,160,90,.1);border:1px solid rgba(127,160,90,.28);color:var(--ok)}
.blank .ei svg{width:24px;height:24px;stroke-width:1.7}
.blank h4{font-size:15.5px;font-weight:700}
.blank p{font-size:13px;color:var(--text-faint);max-width:46ch;line-height:1.65}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width:1140px){
  .rec-row{grid-template-columns:104px 104px minmax(0,1fr) 128px}
  .rec-row .st{grid-column:1 / -1;padding-top:2px}
  .pgrid{grid-template-columns:1fr}
  .pgrid > .col-r{position:static}
  .pgrid > .col-r > .card{margin-top:20px}
  .codes{grid-template-columns:repeat(3,1fr)}
}
@media (max-width:900px){
  .row{flex-direction:column;align-items:stretch;gap:12px}
  .row-r{padding-top:0}
  .row-r .btn{flex:1}
  .tfa-detail .k{width:auto;flex-basis:100%}
}
@media (max-width:760px){
  .sidebar{position:fixed;left:0;top:0;height:100dvh;transform:translateX(-100%);
    transition:transform .26s ease}
  .side-inner{position:static;height:100dvh}
  body.nav-open .sidebar{transform:translateX(0);box-shadow:0 0 60px rgba(0,0,0,.6)}
  .hamburger{display:grid}
  .topbar{padding:0 14px;gap:10px}
  .divider{display:none}
  .page-title h1{font-size:15px}
  .account-btn{min-width:0;padding:9px 11px;gap:6px}
  .account-meta{display:none}
  .account-btn .acct-ico{display:block;width:18px;height:18px;color:var(--text-faint)}
  .account-menu{width:220px;right:-4px}
  .content{padding:18px 14px 60px}
  .idcard{padding:18px}
  .nameline h2{font-size:23px}
  .card-h,.card-b,.tfa-head,.tfa-body,.tfa-foot{padding-left:16px;padding-right:16px}
  .faq{margin-left:-16px;margin-right:-16px}
  .faq summary,.faq .a{padding-left:16px;padding-right:16px}
  /* four tabs won't share 390px — let the bar scroll instead of squeezing
     "Administrative record" into two cramped lines */
  .tabbar{width:100%;justify-content:flex-start}
  .tab{padding:10px 13px;font-size:13px}
  .tally{grid-template-columns:1fr}
  .tally > div + div{border-left:none;padding-left:0;border-top:1px solid var(--rule);padding-top:16px}
  /* Left over from the four-across segmented bar. The row hugs its content
     and wraps on its own now; on a phone it should fill the width rather
     than sit in a short box beside empty space. */
  .filters{display:flex;width:100%}
  .filters button{flex:1 1 auto;justify-content:center;padding:9px 10px}
  .rec-head{display:none}
  .rec-row{grid-template-columns:1fr;gap:6px;padding:16px 0}
  .rec-row .date{order:-1}
  .rec-row .date .tm{display:inline;margin:0 0 0 6px}
  /* stacked, the name and rank run together — give them a separator */
  .rec-row .by b{display:inline}
  .rec-row .by b::after{content:" · ";font-weight:400;color:var(--stone)}
  .pager{flex-direction:column;align-items:stretch}
  .pnav{justify-content:center}
  .blank{padding:32px 12px}
  .tfa-foot .btn{flex:1}
  .qrbox{margin:0 auto}
  .scan{gap:20px}
  .codes{grid-template-columns:repeat(2,1fr)}
  .link-row{flex-wrap:wrap}
  .link-row .row-r{width:100%}
  .link-row .row-r .btn,.link-row .row-r .locked{flex:1;justify-content:center}
}
@media (prefers-reduced-motion:reduce){
  *{animation-duration:.001ms!important;transition-duration:.001ms!important}
}

  /* The record's own styles all live in the ADMINISTRATIVE RECORD block
     above. Nothing about it is declared down here any more. */
</style>
<link rel="stylesheet" href="/assets/css/tones.css?v=2.6.1">
</head>

HTML;
require __DIR__ . '/partials/shell-top.php';
?>


    <!-- Page-level messages: the result of an email-change link, a
         mandatory-2FA prompt, or a hard load failure. Injected by the script. -->
    <div id="flash"></div>

    <!-- ============ IDENTITY ============ -->
    <div class="card">
      <div class="idcard">
        <div class="who">
          <div class="nameline">
            <h2 id="idName">&nbsp;</h2>
            <span class="rank tone-0" id="idRank" hidden></span>
          </div>
          <div class="idmeta" id="idMeta"></div>
        </div>
        <div class="idchips" id="idChips"></div>
      </div>
    </div>

    <!-- ============ TABS ============ -->
    <div class="tabbar" role="tablist" aria-label="Profile sections">
      <button class="tab" role="tab" id="t-profile" aria-controls="p-profile" aria-selected="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M5 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/></svg>
        Profile
      </button>
      <button class="tab" role="tab" id="t-settings" aria-controls="p-settings" aria-selected="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg>
        Settings
      </button>
      <button class="tab" role="tab" id="t-security" aria-controls="p-security" aria-selected="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
        Security
      </button>
      <button class="tab" role="tab" id="t-record" aria-controls="p-record" aria-selected="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/></svg>
        Administrative Record
      </button>
    </div>

    <!-- ================================================================
         PROFILE
         ================================================================ -->
    <section class="tabpanel" role="tabpanel" id="p-profile" aria-labelledby="t-profile">
      <div class="pgrid">

        <div class="col-l">

          <!-- Profiles are staff-only, so there is no visibility setting to make.
               This slot shows the thing a player actually comes here to check. -->
          <!-- FEATURE-GATED: no character tables yet. -->
          <div class="card">
            <div class="card-h">
              <h3>Characters</h3>
              <span class="aside" id="charsAside"></span>
            </div>
            <div class="card-b" id="charsHost"></div>
          </div>

          <div class="card">
            <div class="card-h"><h3>Linked accounts</h3></div>
            <div class="card-b">
              <p class="card-lede">Your UCP is the account everything else hangs off. Your game
                accounts are read from the server the moment you connect — you never type them in.</p>

              <div class="rows">

                <div class="link-row">
                  <span class="link-mark forum">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v11H8l-4 3z"/></svg>
                  </span>
                  <div class="link-body">
                    <div class="n">BlaineSide Forums</div>
                    <div class="s" id="forumStatus"></div>
                  </div>
                  <div class="row-r">
                    <a class="btn" id="forumOpen" href="https://forum.blaineside.com" target="_blank" rel="noopener" hidden>Open the forums</a>
                    <span class="locked" id="forumWait" hidden>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                      Links on first sign-in
                    </span>
                  </div>
                </div>

                <div class="link-row" id="discordRow" hidden>
                  <span class="link-mark discord">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 11a1 1 0 1 0 0-.01M16 11a1 1 0 1 0 0-.01"/><path d="M8.5 17c-2 0-3.5-1.2-4-3.5C4 10 5 6.8 6.5 5.5 7.4 5 8.6 4.7 9.5 4.6l.6 1.2a12 12 0 0 1 3.8 0l.6-1.2c.9.1 2.1.4 3 .9C19 6.8 20 10 19.5 13.5c-.5 2.3-2 3.5-4 3.5l-.9-1.5"/><path d="M8.5 17l-1 2.5M15.5 17l1 2.5"/></svg>
                  </span>
                  <div class="link-body">
                    <div class="n">Discord</div>
                    <div class="s" id="discordStatus"></div>
                  </div>
                  <div class="row-r">
                    <button class="btn" id="discordBtn" hidden>Link Discord</button>
                    <button class="btn danger" id="discordUnlink" hidden>Unlink</button>
                    <span class="locked" id="discordSoon" hidden>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                      Linking not available yet
                    </span>
                  </div>
                </div>

                <div class="link-row">
                  <span class="link-mark game">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="7" width="20" height="11" rx="4"/><path d="M7 11v3M5.5 12.5h3"/><circle cx="16" cy="12" r="1"/><circle cx="18.5" cy="14" r="1"/></svg>
                  </span>
                  <div class="link-body">
                    <div class="n">Game accounts</div>
                    <div class="s none">Your Rockstar Social Club and FiveM identifiers are captured
                      the first time you connect to the server, and are what staff use in reports and
                      appeals. Nothing shows here until the game server is linked to the UCP.</div>
                  </div>
                  <div class="row-r">
                    <span class="locked">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                      Set automatically
                    </span>
                  </div>
                </div>

              </div>

            </div>
          </div>

        </div>

        <div class="col-r">
          <div class="card">
            <div class="card-h"><h3>Your account</h3></div>
            <div class="card-b">
              <div class="rows" id="acctRows"></div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ================================================================
         SETTINGS
         ================================================================ -->
    <section class="tabpanel" role="tabpanel" id="p-settings" aria-labelledby="t-settings" hidden>

      <div class="card">
        <div class="card-h"><h3>Sign-in details</h3></div>
        <div class="card-b">
          <p class="card-lede">Each of these asks for your password on its own, at the moment you
            change it — so you always know which change you just approved.</p>

          <div class="rows">

            <!-- UCP name -->
            <div class="row">
              <div class="row-l">
                <div class="row-t">UCP name</div>
                <div class="row-v" id="rowNameValue"></div>
                <div class="row-d" id="rowNameNote">The name you sign in with, and the name staff and
                  other players know you by.</div>
              </div>
              <div class="row-r"><button class="btn" id="btnNameOpen" data-expand="exName">Change</button></div>
            </div>
            <div class="expand" id="exName">
              <div class="form">
                <div class="form-grid">
                  <div class="fld chk" id="fldName">
                    <label for="newName">New UCP name</label>
                    <div class="ctl">
                      <input id="newName" type="text" placeholder="Choose a new name" autocomplete="off"
                             spellcheck="false" autocapitalize="off">
                      <span class="ind" id="indName"></span>
                    </div>
                    <div class="hint" id="hintName">3–20 characters. Letters, numbers and underscores.</div>
                  </div>
                  <div class="fld chk" id="fldNamePw">
                    <label for="namePw">Your password</label>
                    <div class="ctl">
                      <input id="namePw" type="password" placeholder="Confirm it's you" autocomplete="current-password">
                      <span class="ind" id="indNamePw"></span>
                    </div>
                    <div class="hint" id="hintNamePw"></div>
                  </div>
                </div>
                <div class="note amber">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                  <div id="nameCooldownNote"></div>
                </div>
                <div class="form-acts">
                  <button class="btn primary" id="btnName">Change UCP name</button>
                  <button class="btn" data-cancel="exName">Cancel</button>
                </div>
              </div>
            </div>

            <!-- Email -->
            <div class="row">
              <div class="row-l">
                <div class="row-t">Email address</div>
                <div class="row-v" id="rowEmailValue"></div>
                <div class="row-d">Used for password resets and anything staff need to reach you
                  about. Never shown to other players.</div>
              </div>
              <div class="row-r"><button class="btn" data-expand="exEmail">Change</button></div>
            </div>
            <div class="note" id="emailPending" style="margin:0 0 4px" hidden></div>
            <div class="expand" id="exEmail">
              <div class="form">
                <div class="form-grid">
                  <div class="fld chk" id="fldEmail">
                    <label for="newEmail">New email address</label>
                    <div class="ctl">
                      <input id="newEmail" type="email" placeholder="you@example.com" autocomplete="email"
                             spellcheck="false">
                      <span class="ind" id="indEmail"></span>
                    </div>
                    <div class="hint" id="hintEmail">We check the address is usable when you submit.</div>
                  </div>
                  <div class="fld chk" id="fldEmailPw">
                    <label for="emailPw">Your password</label>
                    <div class="ctl">
                      <input id="emailPw" type="password" placeholder="Confirm it's you" autocomplete="current-password">
                      <span class="ind" id="indEmailPw"></span>
                    </div>
                    <div class="hint" id="hintEmailPw"></div>
                  </div>
                </div>
                <div class="note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
                  <div>We'll send a link to the new address. <b>Your current address keeps working
                    until you open it</b>, so a typo can't lock you out. The link lasts two hours.</div>
                </div>
                <div class="form-acts">
                  <button class="btn primary" id="btnEmail">Send verification link</button>
                  <button class="btn" data-cancel="exEmail">Cancel</button>
                </div>
              </div>
            </div>

            <!-- Password -->
            <div class="row">
              <div class="row-l">
                <div class="row-t">Password</div>
                <div class="row-v" id="rowPwValue"></div>
                <div class="row-d">Use something you don't use anywhere else. A password manager is
                  worth more than any rule we could write here.</div>
              </div>
              <div class="row-r"><button class="btn" data-expand="exPw">Change</button></div>
            </div>
            <div class="expand" id="exPw">
              <div class="form">
                <div class="form-grid">
                  <div class="fld chk" id="fldCurPw">
                    <label for="curPw">Current password</label>
                    <div class="ctl">
                      <input id="curPw" type="password" placeholder="Your current password" autocomplete="current-password">
                      <span class="ind" id="indCurPw"></span>
                    </div>
                    <div class="hint" id="hintCurPw"></div>
                  </div>
                  <div class="fld chk" id="fldNp">
                    <label for="np">New password</label>
                    <div class="ctl">
                      <input id="np" type="password" placeholder="Create a new password" autocomplete="new-password">
                      <span class="ind" id="indNp"></span>
                    </div>
                    <div class="meter" id="meter"><i></i><i></i><i></i><i></i></div>
                    <div class="reqs">
                      <span class="req" id="rLen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg> 8+ characters</span>
                      <span class="req" id="rUpp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg> Uppercase</span>
                      <span class="req" id="rNum"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg> Number</span>
                    </div>
                  </div>
                  <div class="fld chk" id="fldNp2">
                    <label for="np2">Confirm new password</label>
                    <div class="ctl">
                      <input id="np2" type="password" placeholder="Re-enter your new password" autocomplete="new-password">
                      <span class="ind" id="indNp2"></span>
                    </div>
                    <div class="hint" id="matchHint"></div>
                  </div>
                </div>
                <div class="note amber">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                  <div><b>Changing your password signs you out on every other device</b>, and cancels
                    any email change still waiting on its link. Two-step verification stays on — a
                    password change doesn't touch it.</div>
                </div>
                <div class="form-acts">
                  <button class="btn primary" id="btnPassword">Update password</button>
                  <button class="btn" data-cancel="exPw">Cancel</button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="card danger">
        <div class="card-h"><h3>Danger zone</h3></div>
        <div class="card-b">
          <div class="rows">

            <div class="row">
              <div class="row-l">
                <div class="row-t">Sign out everywhere</div>
                <div class="row-d">Ends every session except this one, including "remember me" on
                  devices you no longer have. Your password and two-step verification stay as they are.</div>
              </div>
              <div class="row-r"><button class="btn danger" data-expand="exSignout">Sign out all devices</button></div>
            </div>
            <div class="expand" id="exSignout">
              <div class="form">
                <div class="form-grid">
                  <div class="fld chk" id="fldSignoutPw">
                    <label for="signoutPw">Confirm with your password</label>
                    <div class="ctl">
                      <input id="signoutPw" type="password" placeholder="Your UCP password" autocomplete="current-password">
                      <span class="ind" id="indSignoutPw"></span>
                    </div>
                    <div class="hint" id="hintSignoutPw"></div>
                  </div>
                </div>
                <div class="note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
                  <div>You stay signed in here. Everywhere else — including the forums — has to sign
                    in again. If you think someone has your password, change it instead: that does
                    this as well.</div>
                </div>
                <div class="form-acts">
                  <button class="btn danger" id="btnSignout">Sign out all other devices</button>
                  <button class="btn" data-cancel="exSignout">Cancel</button>
                </div>
              </div>
            </div>

            <!-- FEATURE-GATED: deletion is off unless security.allow_self_delete
                 is set in config.php, and the server also refuses while there is
                 anything on the administrative record. -->
            <div class="row">
              <div class="row-l">
                <div class="row-t">Delete your UCP</div>
                <div class="row-d">Permanently removes this account, your characters, your
                  properties and your forum posts. Money and vehicles are not refunded, and the
                  name is released for someone else. This cannot be undone.</div>
              </div>
              <div class="row-r">
                <button class="btn danger" id="delBtn" data-expand="exDelete" hidden>Delete my UCP</button>
                <span class="locked" id="delLocked" hidden>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                  Not available
                </span>
              </div>
            </div>

            <div class="expand" id="exDelete">
              <div class="form">

                <div id="delBlocked" hidden>
                  <div class="note red" style="margin-top:0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                    <div><b>Deleting your UCP isn't available yet.</b> Deletion has to erase the
                      administrative record along with the account, so it stays closed until that
                      system is built — otherwise a punished player could wipe their history and
                      start again.</div>
                  </div>
                  <p class="card-lede" style="padding-top:14px">If you want to stop playing in the
                    meantime, sign out everywhere and stop using the account. Ask a Founder if you
                    need it closed sooner.</p>
                  <div class="form-acts">
                    <button class="btn" data-cancel="exDelete">Close</button>
                  </div>
                </div>

                <div id="delAllowed" hidden>
                  <div class="note red" style="margin-top:0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                    <div><b>This is permanent.</b> There is no restore, no grace period and no way
                      for staff to bring it back afterwards.</div>
                  </div>

                  <p class="card-lede" style="padding-top:14px">Deleting removes, immediately and
                    for good:</p>
                  <ul class="need loss" style="margin-top:12px">
                    <li>Every character on this account, with their money, vehicles and properties.
                      None of it is refunded or transferred.</li>
                    <li>Your <b>forum account and every post on it</b>.</li>
                    <li id="delLosesName">Your UCP name is released for anyone else to take.</li>
                  </ul>

                  <div class="form-grid" style="margin-top:20px">
                    <div class="fld chk" id="fldDelPw">
                      <label for="delPw">Confirm with your password</label>
                      <div class="ctl">
                        <input id="delPw" type="password" placeholder="Your UCP password" autocomplete="current-password">
                        <span class="ind" id="indDelPw"></span>
                      </div>
                      <div class="hint" id="hintDelPw"></div>
                    </div>
                  </div>

                  <label class="ack" id="delAck">
                    <span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg></span>
                    <span>I understand my characters, money, properties and forum posts are deleted
                      permanently and cannot be restored.</span>
                  </label>

                  <div class="form-acts">
                    <button class="btn danger" id="delConfirm" aria-disabled="true">Delete my UCP permanently</button>
                    <button class="btn" data-cancel="exDelete">Cancel</button>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    </section>

    <!-- ================================================================
         SECURITY
         ================================================================ -->
    <section class="tabpanel" role="tabpanel" id="p-security" aria-labelledby="t-security" hidden>

      <!-- ---------- STATE: OFF ---------- -->
      <div class="card" data-state="off">
        <div class="tfa-head">
          <span class="tfa-shield">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/></svg>
          </span>
          <div class="t">
            <h4>Two-step verification <span class="tfa-state">Off</span></h4>
            <p>Right now your password is the only thing between someone and your account — here and
              on the forums, since the forums sign you in through your UCP. Turn this on and a stolen
              password isn't enough on its own.</p>
            <ul class="need">
              <li>A phone with an <b>authenticator app</b> — Google Authenticator, Authy, 1Password,
                Aegis or Ente all work.</li>
              <li>Somewhere to keep <b>ten recovery codes</b>. A password manager, or printed and put away.</li>
              <li>About <b>two minutes</b>.</li>
            </ul>
          </div>
        </div>
        <div class="tfa-foot">
          <button class="btn primary lg" data-step-go="1">Turn on two-step verification</button>
        </div>
      </div>

      <!-- ---------- STATE: ON ---------- -->
      <div class="card" data-state="on" hidden>
        <div class="tfa-head">
          <span class="tfa-shield">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
          </span>
          <div class="t">
            <h4>Two-step verification <span class="tfa-state">On</span></h4>
            <p>Every sign-in asks for a code from your authenticator app — on the UCP and on the forums.</p>
          </div>
        </div>
        <div class="tfa-body">
          <div class="tfa-detail">
            <div>
              <span class="k">Method</span>
              <span class="v">Authenticator app
                <span class="sm">A time-based code, generated on your phone. Nothing is sent to you.</span></span>
            </div>
            <div>
              <span class="k">Recovery codes</span>
              <span class="v">
                <span class="codes-left">
                  <span class="pips" data-pips></span>
                  <span data-codes-left>—</span>
                </span>
                <span class="sm">Each works once, in place of a code from your app.</span>
              </span>
            </div>
          </div>
        </div>
        <div class="tfa-foot">
          <button class="btn" data-tfa-action="codes">Generate new recovery codes</button>
          <button class="btn danger" data-tfa-action="disable" data-disable-2fa>Turn off two-step verification</button>
        </div>
      </div>

      <!-- ---------- STATE: ON, RECOVERY CODES RUNNING OUT ---------- -->
      <div class="card" data-state="low" hidden>
        <div class="tfa-head">
          <span class="tfa-shield">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
          </span>
          <div class="t">
            <h4>Two-step verification <span class="tfa-state">On</span></h4>
            <p>Every sign-in asks for a code from your authenticator app — on the UCP and on the forums.</p>
          </div>
        </div>
        <div class="tfa-body">
          <div class="note amber" style="margin-top:0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
            <div id="lowCodesNote"></div>
          </div>
          <div class="tfa-detail">
            <div>
              <span class="k">Method</span>
              <span class="v">Authenticator app
                <span class="sm">A time-based code, generated on your phone. Nothing is sent to you.</span></span>
            </div>
            <div>
              <span class="k">Recovery codes</span>
              <span class="v low">
                <span class="codes-left">
                  <span class="pips" data-pips></span>
                  <span data-codes-left>—</span>
                </span>
                <span class="sm" id="codesUsedNote"></span>
              </span>
            </div>
          </div>
        </div>
        <div class="tfa-foot">
          <button class="btn primary" data-tfa-action="codes">Generate new recovery codes</button>
          <button class="btn danger" data-tfa-action="disable" data-disable-2fa>Turn off two-step verification</button>
        </div>
      </div>

      <!-- ---------- STATE: CONFIRM (turn off / regenerate codes) ----------
           Both changes ask for the password AND a current code: a session left
           open on a shared machine must not be enough to strip the second
           factor off, or the second factor was never worth having. -->
      <div class="card" data-state="action" hidden>
        <div class="tfa-body" style="padding-top:22px">
          <h4 id="tfaActionTitle" style="font-size:16.5px;font-weight:700;letter-spacing:-.015em"></h4>
          <p class="card-lede" style="padding-top:8px" id="tfaActionLede"></p>

          <div class="form-grid" style="margin-top:18px">
            <div class="fld chk" id="fldTfaPw">
              <label for="tfaPw">Your password</label>
              <div class="ctl">
                <input id="tfaPw" type="password" placeholder="Your UCP password" autocomplete="current-password">
                <span class="ind" id="indTfaPw"></span>
              </div>
              <div class="hint" id="hintTfaPw"></div>
            </div>
            <div class="fld chk" id="fldTfaCode">
              <label for="tfaCode">Code from your app</label>
              <div class="ctl">
                <input id="tfaCode" type="text" inputmode="text" placeholder="000000" autocomplete="one-time-code"
                       spellcheck="false" autocapitalize="characters">
                <span class="ind" id="indTfaCode"></span>
              </div>
              <div class="hint" id="hintTfaCode">A code from your app, or one of your recovery codes.</div>
            </div>
          </div>

          <div class="form-acts">
            <button class="btn primary" id="btnTfaAction">Continue</button>
            <button class="btn" id="btnTfaCancel">Cancel</button>
          </div>
        </div>
      </div>

      <!-- ---------- STATE: SETUP ---------- -->
      <div class="card" data-state="setup" hidden>
        <div class="tfa-body" style="padding-top:22px">

          <div class="steps" id="tfaSteps">
            <span class="step" data-step="1"><span class="n">1</span> Confirm password</span>
            <span class="step-line"></span>
            <span class="step" data-step="2"><span class="n">2</span> Scan the code</span>
            <span class="step-line"></span>
            <span class="step" data-step="3"><span class="n">3</span> Save recovery codes</span>
          </div>

          <div class="setup-step" data-s="1">
            <h4 style="font-size:16.5px;font-weight:700;letter-spacing:-.015em">Confirm your password</h4>
            <p class="card-lede" style="padding-top:8px">Before changing how your account is
              protected, we check it's really you at the keyboard.</p>
            <div class="form-grid" style="margin-top:18px">
              <div class="fld chk" id="fldSetupPw">
                <label for="setupPw">Your password</label>
                <div class="ctl">
                  <input id="setupPw" type="password" placeholder="Your UCP password" autocomplete="current-password">
                  <span class="ind" id="indSetupPw"></span>
                </div>
                <div class="hint" id="hintSetupPw"></div>
              </div>
            </div>
            <div class="form-acts">
              <button class="btn primary" id="btnSetupNext" data-step-go="2">Continue</button>
              <button class="btn" data-step-go="off">Cancel</button>
            </div>
          </div>

          <div class="setup-step" data-s="2" hidden>
            <h4 style="font-size:16.5px;font-weight:700;letter-spacing:-.015em">Scan this code</h4>
            <p class="card-lede" style="padding-top:8px">Open your authenticator app, choose "add
              account", and point it at this. If the camera won't cooperate, type the key in by hand
              instead — the entry type is <b style="color:var(--parchment)">time-based</b>.</p>

            <div class="scan" style="margin-top:20px">
              <div class="qrbox" id="qrBox"></div>
              <div class="scan-side">
                <div class="fld">
                  <label>Setup key</label>
                  <div class="keyval" id="keyVal" title="Click to copy"></div>
                  <div class="hint" id="keyHint">Click to copy.</div>
                </div>
                <div class="fld chk codeinput" id="fldSetCode" style="margin-top:18px">
                  <label for="setCode">Enter the 6-digit code your app shows</label>
                  <div class="ctl">
                    <input id="setCode" type="text" inputmode="numeric" maxlength="6" placeholder="000000" autocomplete="one-time-code">
                    <span class="ind" id="indSetCode"></span>
                  </div>
                  <div class="hint" id="hintSetCode">The code changes every 30 seconds. If it keeps being
                    rejected, check your phone's date and time are set to automatic.</div>
                </div>
              </div>
            </div>

            <div class="form-acts">
              <button class="btn primary" id="btnConfirm2fa">Turn on two-step verification</button>
              <button class="btn" data-step-go="off">Cancel</button>
            </div>
          </div>

          <div class="setup-step" data-s="3" hidden>
            <div class="card-h" style="border:0;padding:0 0 8px">
              <h4 id="codesTitle" style="font-size:16.5px;font-weight:700;letter-spacing:-.015em">Save your recovery codes</h4>
              <span class="aside" id="codesEyebrow"></span>
            </div>
            <p class="card-lede">These are the only way back into your account if you lose your
              phone. Each one works once.</p>

            <div class="note amber">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
              <div><b>This is the only time they are shown.</b> They can't be looked up later — you
                can only replace the whole set.</div>
            </div>

            <div class="codes" id="codeList"></div>

            <div class="form-acts" style="margin-top:16px">
              <button class="btn" id="btnCopyCodes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg> Copy</button>
              <button class="btn" id="btnDownloadCodes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 4v11M8 11l4 4 4-4M4 20h16"/></svg> Download</button>
              <button class="btn" id="btnPrintCodes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 9V4h10v5M7 19h10v-5H7z"/><path d="M5 9h14a2 2 0 0 1 2 2v4h-4M7 15H3v-4a2 2 0 0 1 2-2"/></svg> Print</button>
            </div>

            <label class="ack" id="ack">
              <span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg></span>
              <span>I have saved these recovery codes somewhere I'll still have access to if I lose my phone.</span>
            </label>

            <div class="form-acts">
              <button class="btn primary" id="ackDone" aria-disabled="true">Done</button>
            </div>
          </div>

        </div>
      </div>

      <div class="card">
        <div class="card-h"><h3>How two-step verification works</h3></div>
        <div class="card-b">
          <p class="card-lede">Worth two minutes before you turn it on — especially the part about
            losing your phone.</p>

          <div class="faq">
            <details name="tfa-faq">
              <summary>Which apps can I use?
                <svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></summary>
              <div class="a">
                <p>Any of them. This uses the standard every authenticator speaks, so
                  <b>Google Authenticator</b>, <b>Authy</b>, <b>1Password</b>, <b>Bitwarden</b>,
                  <b>Aegis</b> and <b>Ente Auth</b> all work identically. We don't have an app of our
                  own and never will — that's the point of a standard.</p>
                <p>If you already use a password manager, put the code in there: it syncs across your
                  devices, so a lost phone stops being a crisis.</p>
              </div>
            </details>
            <details name="tfa-faq">
              <summary>Does this protect my forum account too?
                <svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></summary>
              <div class="a">
                <p>Yes. The forums don't have their own password — signing in there hands you to the
                  UCP, and the UCP is what asks for your code. So turning it on here covers
                  <b>forum.blaineside.com</b> in the same move.</p>
                <p>The forum software has its own separate 2FA setting in its profile area. You don't
                  need it, and turning it on means entering two codes for one sign-in.</p>
              </div>
            </details>
            <details name="tfa-faq">
              <summary>What happens if I lose my phone?
                <svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></summary>
              <div class="a">
                <p>You use a recovery code. At the sign-in prompt, click <b>"Lost your phone? Use a
                  recovery code"</b> and enter one of the ten you saved. It signs you in exactly like
                  an app code does, then gets marked used.</p>
                <p>Once you're in, turn two-step off and set it up again on your new phone.</p>
                <p>If you've lost the phone <b>and</b> the recovery codes, there is no self-service
                  way back in — that is what makes it worth having. Open a ticket and a Founder will
                  clear it after verifying who you are. Expect that to take a day or two, and expect
                  to be asked questions only you can answer.</p>
              </div>
            </details>
            <details name="tfa-faq">
              <summary>What exactly are recovery codes?
                <svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></summary>
              <div class="a">
                <p>Ten one-time passwords, generated when you switch two-step on. Each is accepted
                  once at the sign-in prompt instead of a code from your app, and is then dead forever.</p>
                <p>They're shown once and stored scrambled, so nobody — staff included — can look
                  yours up afterwards. If you lose the list, generate a new set from this page: it
                  replaces all ten and the old ones stop working immediately.</p>
              </div>
            </details>
            <details name="tfa-faq">
              <summary>Can I get past it by resetting my password?
                <svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></summary>
              <div class="a">
                <p>No, deliberately. A password reset only proves someone can read your email — if
                  that were enough to strip two-step off, then anyone who got into your inbox would
                  own your account, and the second step would be decoration.</p>
                <p>So a reset changes your password and leaves two-step exactly as it was. Your
                  recovery codes are the way through, which is why saving them matters.</p>
              </div>
            </details>
            <details name="tfa-faq">
              <summary>I think someone has my password. What now?
                <svg class="cv" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></summary>
              <div class="a">
                <p>In this order: change your password from the <b>Settings</b> tab — that signs out
                  every other device on its own — then turn on two-step verification if it isn't
                  already, then check <b>Where you're signed in</b> below for anything you don't
                  recognise.</p>
                <p>If you see a sign-in that wasn't you, tell staff in Discord with the date and time.
                  We keep sign-in records and can check them against what you report.</p>
              </div>
            </details>
          </div>
        </div>
      </div>

      <!-- FEATURE-GATED: one row per session doesn't exist yet, so the server
           reports sessions:false and this renders an honest empty state. -->
      <div class="card">
        <div class="card-h">
          <h3>Where you're signed in</h3>
          <span class="aside" id="sessAside"></span>
        </div>
        <div class="card-b">
          <p class="card-lede" id="sessLede">Anything you don't recognise, sign it out and change your password.</p>
          <div id="sessHost"></div>
        </div>
      </div>

      <!-- FEATURE-GATED: no security-events table yet. -->
      <div class="card">
        <div class="card-h">
          <h3>Recent security activity</h3>
          <span class="aside" id="logAside"></span>
        </div>
        <div class="card-b">
          <p class="card-lede">Sign-ins and changes to how your account is protected.</p>
          <div id="logHost"></div>
        </div>
      </div>

    </section>

    <!-- ================================================================
         ADMINISTRATIVE RECORD

         Standalone summary panel, the collapsed appeal history, the staff
         Scratchpad, then one card per kind. All of it is drawn by
         renderRecord() from api/_punish.php's record_for(), so the staff
         view and the player's own view cannot disagree about an account.
         ================================================================ -->
    <section class="tabpanel" role="tabpanel" id="p-record" aria-labelledby="t-record" hidden>

      <!-- FEATURE-GATED: shown instead of everything else when the
           punishment tables have not been migrated. -->
      <div class="card" id="recGate" hidden>
        <div class="card-b"><div id="recGateBody"></div></div>
      </div>

      <div id="recLive" hidden>

        <!-- the panel that gets screenshotted -->
        <div class="cert" id="cert">
          <div class="cert-top">
            <span class="cert-dot"></span>
            <p class="cert-line"><b id="certH"></b><span id="certS"></span></p>
          </div>
          <div class="cert-stats" id="certStats"></div>
          <div class="cert-foot" id="certFoot"></div>
        </div>

        <!-- past appeals, closed by default: context, not the record -->
        <div class="card fold" id="appealCard">
          <button class="foldh" type="button" id="appealToggle" aria-expanded="false"
                  aria-controls="appealBody">
            <h3>Previous appeals</h3>
            <span class="r">
              <span id="appealCount"></span>
              <svg class="cv" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
            </span>
          </button>
          <div class="card-b" id="appealBody">
            <p class="card-lede" id="appealLede"></p>
            <div id="appealList"></div>
            <div id="appealPager"></div>
          </div>
        </div>

        <!-- STAFF ONLY. api/profile.php never sends this block, so there is
             no route by which the player's own page could draw it. -->
        <div class="card" id="padCard" hidden>
          <div class="card-h">
            <h3>Scratchpad</h3>
            <span class="aside">
              <span class="hidden-badge">
                <svg viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 5.1A9.9 9.9 0 0 1 12 5c6 0 10 7 10 7a17 17 0 0 1-2.6 3.4M6.6 6.6A17 17 0 0 0 2 12s4 7 10 7a9.6 9.6 0 0 0 4.4-1.1"/></svg>
                Never shown to the player
              </span>
              <span id="padCount"></span>
            </span>
          </div>
          <div class="card-b">
            <p class="card-lede">Working notes on this account, for staff only. Nothing here counts
              towards the record and nothing here can be appealed — it is for the things that are
              true but are not punishments: a word had in voice, a pattern somebody noticed,
              another name this person plays under. Newest first.</p>

            <div class="padbox" id="padWrite">
              <textarea id="padInput" placeholder="Add a note about this account…" maxlength="1000"></textarea>
              <div class="padfoot">
                <span class="hint">Your name, rank and the UTC time are stamped on it. Notes
                  cannot be edited after posting — add a follow-up instead.</span>
                <button class="mini gold" type="button" id="padAdd">Add note</button>
              </div>
            </div>

            <div class="notes" id="padNotes"></div>
            <div id="padPager"></div>
          </div>
        </div>

        <!-- one card per kind, built from punish_cards() -->
        <div id="recCards"></div>

      </div>
    </section>

  </main>
</div>

<script src="/assets/js/ucp.js?v=3.0.2"></script>
<script src="/assets/js/qrcode.js" defer></script>
<script>
/* =====================================================================
   BlaineSide UCP — profile page

   Settings and Security are live. Everything they show comes from
   api/profile.php and everything they change goes through
   api/settings-*.php or api/2fa-*.php.

   Characters, the administrative record, the session list and the activity
   log are FEATURE-GATED: api/profile.php reports whether each has a backend,
   and anything false renders "not available yet". The server decides, so this
   page can never drift into showing convincing sample data to a real player.
   ===================================================================== */
(function(){
  'use strict';
  var $ = function(id){ return document.getElementById(id); };
  var esc = function(s){ return String(s).replace(/[&<>"']/g, function(c){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };

  var DATA = null;                       // whatever api/profile.php last returned
  var LOGIN = '/login?return=' + encodeURIComponent('/profile');

  /* ---- small formatting helpers ---- */
  var MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  var MONTH = ['January','February','March','April','May','June','July',
               'August','September','October','November','December'];

  /** "2019-04-20 12:00:00" (server time, UTC) -> Date, or null. */
  function parseUTC(sqlDate){
    if(!sqlDate) return null;
    var m = String(sqlDate).match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
    if(!m) return null;
    return new Date(Date.UTC(+m[1], +m[2]-1, +m[3], +m[4], +m[5], +m[6]));
  }
  function longDate(d){ return d ? d.getUTCDate()+' '+MONTH[d.getUTCMonth()]+' '+d.getUTCFullYear() : '—'; }
  function shortDate(d){ return d ? d.getUTCDate()+' '+MON[d.getUTCMonth()]+' '+d.getUTCFullYear() : '—'; }
  function hhmm(d){
    if(!d) return '';
    var p = function(n){ return String(n).padStart(2,'0'); };
    return p(d.getUTCHours())+':'+p(d.getUTCMinutes())+' UTC';
  }
  /** "2 hours ago" / "yesterday" / "3 months ago" from a Date. */
  function ago(d){
    if(!d) return 'never';
    var s = Math.floor((Date.now() - d.getTime())/1000);
    if(s < 60) return 'just now';
    if(s < 3600){ var mi = Math.floor(s/60); return mi+(mi===1?' minute ago':' minutes ago'); }
    if(s < 86400){ var h = Math.floor(s/3600); return h+(h===1?' hour ago':' hours ago'); }
    var dd = Math.floor(s/86400);
    if(dd === 1) return 'yesterday';
    if(dd < 30) return dd+' days ago';
    var mo = Math.floor(dd/30);
    if(mo < 12) return mo+(mo===1?' month ago':' months ago');
    var y = Math.floor(dd/365);
    return y+(y===1?' year ago':' years ago');
  }
  function n(x){ return Number(x||0).toLocaleString('en-GB'); }

  /* =====================================================================
     FIELD STATE
     One helper for every validated field: the border carries the state, a
     mark sits inside the field, and the hint underneath becomes the message.
     ===================================================================== */
  var ICO = {
    ok:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg>',
    err: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 7l10 10M17 7L7 17"/></svg>',
    busy:'<span class="spin"></span>'
  };
  function setField(fldId, indId, hintId, state, msg){
    var f=$(fldId), h=$(hintId), i=$(indId);
    if(!f) return;
    f.classList.remove('ok','err','busy');
    if(state) f.classList.add(state);
    if(i) i.innerHTML = ICO[state] || '';
    if(h){
      h.className = 'hint' + (state==='ok' ? ' ok' : state==='err' ? ' err' : '');
      if(msg !== undefined) h.innerHTML = msg;
    }
  }
  function busyBtn(btn, on, label){
    if(!btn) return;
    btn.setAttribute('aria-disabled', on ? 'true' : 'false');
    if(on){ btn.dataset.label = btn.innerHTML; btn.innerHTML = label || 'Working…'; }
    else if(btn.dataset.label){ btn.innerHTML = btn.dataset.label; }
  }

  /** Anything that means "your session is gone" sends you back to sign in. */
  function bounceIfSignedOut(res){
    var d = res && res.data ? res.data : res;
    if(d && d.authenticated === false){ window.location.replace(LOGIN); return true; }
    return false;
  }

  /* =====================================================================
     PAGE-LEVEL FLASH
     ===================================================================== */
  var ICONS_NOTE = {
    info:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
    warn:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>',
    ok:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg>'
  };
  function flash(kind, html){
    var cls = kind === 'ok' ? 'green' : kind === 'err' ? 'red' : kind === 'warn' ? 'amber' : '';
    var ico = kind === 'err' || kind === 'warn' ? ICONS_NOTE.warn
            : kind === 'ok' ? ICONS_NOTE.ok : ICONS_NOTE.info;
    $('flash').innerHTML =
      '<div class="note '+cls+'" style="margin:0 0 18px">'+ico+'<div>'+html+'</div></div>';
  }

  /* =====================================================================
     SIDEBAR + TOPBAR (identical config to the dashboard)
     ===================================================================== */
  /* The sidebar lives in assets/js/ucp.js — one copy for every page. It
     used to be pasted into all eleven, which is eleven things to forget
     when one of them changes; adding a menu item is now an edit to NAV in
     that file and nothing else. Any page with <nav id="nav"> gets it, drawn
     from the cached rank on load and again when api/session.php answers.

     The IS_* flags below stay: this page uses them for its own UI. */
  var IS_MANAGER = false, IS_FOUNDER = false, IS_ADMINISTRATOR = false;
  var MY_RANK = 0, MY_TEAMS = [];   // the ladder rung, and sub-group keys
  /* Seed the menu gates from the last known session so the FIRST paint is
     right. Without this every navigation drew the sidebar twice — once with
     no Administration section, once with it — which is the flicker. The
     server confirms it a moment later, and every page and endpoint checks
     the rank with the server on every request regardless. */
  (function(){
    var me = window.UCP && UCP.me;
    if(!me) return;
    IS_ADMINISTRATOR = me.rank >= 3;
    IS_MANAGER       = me.rank >= 8;
    IS_FOUNDER       = me.rank >= 9;
    MY_RANK          = me.rank | 0;
    MY_TEAMS         = me.teams || [];
  })();

  var accBtn=$('acctBtn'), accMenu=$('acctMenu');
  accMenu.style.display='none';
  accBtn.addEventListener('click', function(e){ e.stopPropagation();
    accMenu.style.display = accMenu.style.display==='none' ? 'block' : 'none'; });
  accMenu.addEventListener('click', function(e){ e.stopPropagation(); });
  document.addEventListener('click', function(){ accMenu.style.display='none'; });

  /* Log out through fetch, so the browser never lands on the endpoint's raw
     JSON. The href stays a working no-JS fallback: ?next=/login makes
     logout.php redirect after its POST bridge instead of answering in JSON. */
  $('logoutBtn').addEventListener('click', function(e){
    e.preventDefault();
    var a = this;
    a.style.pointerEvents = 'none';
    /* Forget the cached identity — the next person at this computer starts
       blank rather than briefly wearing the last one's name and menu. */
    if(window.UCP && UCP.forgetMe) UCP.forgetMe();
    UCP.post('logout.php', {}).then(function(res){
      var d = res && res.data ? res.data : {};
      window.location.replace(d.redirect || '/login');
    }).catch(function(){
      window.location.href = '/api/logout.php?next=/login';
    });
  });

  var scrim=$('scrim');
  $('menuToggle').addEventListener('click', function(e){ e.stopPropagation();
    document.body.classList.toggle('nav-open');
    scrim.classList.toggle('show', document.body.classList.contains('nav-open')); });
  scrim.addEventListener('click', function(){
    document.body.classList.remove('nav-open'); scrim.classList.remove('show'); });

  var DAYS=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  /* The clock, the build number and the status line are drawn by
     assets/js/ucp.js — one copy for every page. */

  /* =====================================================================
     TABS — hash-addressable, so /profile#security is linkable and survives
     a refresh. The account menu links straight to it.
     ===================================================================== */
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab'));
  function selectTab(name, push){
    tabs.forEach(function(t){
      var on = t.id === 't-'+name;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.setAttribute('tabindex', on ? '0' : '-1');
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    });
    if(push && history.replaceState) history.replaceState(null,'','#'+name);
  }
  tabs.forEach(function(t, i){
    t.addEventListener('click', function(){ selectTab(t.id.slice(2), true); });
    t.addEventListener('keydown', function(e){
      var d = e.key==='ArrowRight' ? 1 : e.key==='ArrowLeft' ? -1 : 0;
      if(!d) return;
      e.preventDefault();
      var next = tabs[(i + d + tabs.length) % tabs.length];
      next.focus(); selectTab(next.id.slice(2), true);
    });
  });
  document.addEventListener('click', function(e){
    var g = e.target.closest && e.target.closest('[data-goto]');
    if(!g) return;
    e.preventDefault();
    selectTab(g.getAttribute('data-goto'), true);
    window.scrollTo({top:0, behavior:'smooth'});
  });
  var initialTab = (location.hash||'').replace('#','');
  selectTab(['profile','settings','security','record'].indexOf(initialTab) > -1 ? initialTab : 'profile', false);

  /* =====================================================================
     EXPANDING SETTINGS ROWS
     One open at a time: two half-filled password forms on screen is how
     people approve the wrong change.
     ===================================================================== */
  function closeExpands(){
    Array.prototype.forEach.call(document.querySelectorAll('.expand.on'), function(x){
      x.classList.remove('on');
    });
  }
  document.addEventListener('click', function(e){
    var open = e.target.closest && e.target.closest('[data-expand]');
    var shut = e.target.closest && e.target.closest('[data-cancel]');
    if(open && !open.hasAttribute('disabled')){
      var el = $(open.getAttribute('data-expand'));
      if(!el) return;
      var already = el.classList.contains('on');
      closeExpands();
      if(!already){
        el.classList.add('on');
        var f = el.querySelector('input');
        if(f) f.focus();
      }
    }
    if(shut) $(shut.getAttribute('data-cancel')).classList.remove('on');
  });

  /* Accordions: one open at a time. <details name> does this natively in
     current browsers; this is the fallback for the ones that ignore it. */
  Array.prototype.forEach.call(document.querySelectorAll('details[name]'), function(d){
    d.addEventListener('toggle', function(){
      if(!d.open) return;
      var g = d.getAttribute('name');
      Array.prototype.forEach.call(document.querySelectorAll('details[name="'+g+'"]'), function(o){
        if(o !== d) o.open = false;
      });
    });
  });

  /* =====================================================================
     RENDER — everything below draws from DATA
     ===================================================================== */
  function render(d){
    DATA = d;

    /* ---- identity ---- */
    $('idName').textContent = d.name;
    var rank = $('idRank');
    rank.hidden = false;
    rank.textContent = d.role || 'Member';
    /* The badge carries the tier's own colour, the same construction the
       group chips use, so a rank looks identical wherever it appears.
       tone-0 covers Member and anything off the end of the ladder. */
    rank.className = 'rank tone-' + ((d.rank|0) >= 0 && (d.rank|0) <= 9 ? (d.rank|0) : 0);

    var created = parseUTC(d.created_at), last = parseUTC(d.last_login);
    $('idMeta').innerHTML =
      '<span>Account <b>#'+d.id+'</b></span>' +
      (created ? '<span class="sep">·</span><span>Joined <b>'+longDate(created)+'</b></span>' : '') +
      (last ? '<span class="sep">·</span><span>Last log in <b>'+ago(last)+'</b></span>' : '');

    var chips = [];
    chips.push(d.twofa.enabled
      ? '<span class="chip good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>Two-step on</span>'
      : '<span class="chip off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/></svg>Two-step off</span>');
    if(d.forum.linked){
      chips.push('<span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v11H8l-4 3z"/></svg>Forum linked</span>');
    }
    /* Only shown for a verified link — the name someone typed at sign-up
       proves nothing and must not earn a badge up here. */
    if(d.discord && d.discord.linked){
      chips.push('<span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
        '<path d="M8 11a1 1 0 1 0 0-.01M16 11a1 1 0 1 0 0-.01"/>' +
        '<path d="M8.5 17c-2 0-3.5-1.2-4-3.5C4 10 5 6.8 6.5 5.5 7.4 5 8.6 4.7 9.5 4.6l.6 1.2a12 12 0 0 1 3.8 0l.6-1.2c.9.1 2.1.4 3 .9C19 6.8 20 10 19.5 13.5c-.5 2.3-2 3.5-4 3.5l-.9-1.5"/>' +
        '<path d="M8.5 17l-1 2.5M15.5 17l1 2.5"/></svg>Discord linked</span>');
    }
    $('idChips').innerHTML = chips.join('');

    /* ---- account menu ---- */
    $('menuName').textContent = d.name;
    $('menuRole').textContent = d.role || 'Member';
    $('acctName').textContent = d.name;
    $('acctRole').textContent = d.role || 'Member';
    /* Keep it for the next page load — this is what stops the flicker. */
    if(window.UCP && UCP.rememberMe) UCP.rememberMe(d);

    /* Redraw only when a gate actually changed — repainting identical HTML
       is what the eye reads as a flash. Sub-groups are part of the signature
       now: Staff Management opens a menu item on its own, so a change to it
       has to redraw even though every rank flag stayed put. */
    var navWas = [IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join();
    IS_ADMINISTRATOR = d.rank >= 3;
    IS_MANAGER       = d.rank >= 8;
    IS_FOUNDER       = d.rank >= 9;
    MY_RANK          = d.rank | 0;
    MY_TEAMS         = d.teams || [];
    if([IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join() !== navWas) renderSidebar();

    renderProfileTab(d);
    renderSettings(d);
    renderSecurity(d);
    renderRecord(d, false);

    if(d.twofa.required && !d.twofa.enabled){
      flash('warn', '<b>Two-step verification is required for your staff rank.</b> ' +
        'Set it up on the <a data-goto="security" href="#security">Security</a> tab to keep using your UCP.');
    }
  }

  /* ---------------------------------------------------------------- PROFILE */
  function renderProfileTab(d){
    /* FEATURE-GATED: characters */
    if(!d.features.characters){
      $('charsHost').innerHTML = blank('user',
        'Characters aren\'t here yet',
        'Once the game server is linked to the UCP your characters will appear here, with their ' +
        'playtime, faction and last session.');
      $('charsAside').textContent = '';
    }

    /* Linked accounts. The member number means nothing to the person reading
       it, so show the name they post under. */
    $('forumStatus').innerHTML = d.forum.linked
      ? 'Linked as <b>'+esc(d.forum.name || DATA.name)+'</b>'
      : '<span class="none">Not linked yet — it links itself the first time you ' +
        'sign in to the forums</span>';
    $('forumOpen').href = d.forum.profile_url || d.forum.url;
    $('forumOpen').textContent = d.forum.profile_url ? 'Open forum profile' : 'Open the forums';
    $('forumOpen').hidden = !d.forum.linked;
    $('forumWait').hidden = d.forum.linked;

    /* Discord. Three states: verified link, an unverified name they typed at
       sign-up, or nothing — and the button only appears once the server says
       an application is configured. */
    var dis = d.discord || {};
    $('discordRow').hidden = false;
    if(dis.linked){
      var since = dis.linked_at ? new Date(dis.linked_at*1000) : null;
      $('discordStatus').innerHTML = 'Linked as <b>'+esc(dis.username)+'</b>' +
        (since ? ' · since '+esc(shortDate(since)) : '');
    } else if(dis.given){
      $('discordStatus').innerHTML = 'Given at sign-up as <b>'+esc(dis.given)+'</b> — ' +
        '<span class="none">not verified</span>';
    } else {
      $('discordStatus').innerHTML = '<span class="none">Not linked</span>';
    }
    $('discordBtn').hidden  = !(d.features.discord_link && !dis.linked);
    $('discordUnlink').hidden = !dis.linked;
    $('discordSoon').hidden = d.features.discord_link || dis.linked;

    /* Your account */
    var created = parseUTC(d.created_at), last = parseUTC(d.last_login);
    var rows = [
      ['Account ID', '#'+d.id, null, true],
      ['Group', d.role || 'Member', null],
      ['Member since', created ? shortDate(created) : '—',
        d.member_days != null ? n(d.member_days)+' days' : null],
      ['Last log in', last ? hhmm(last) : '—', last ? ago(last) : null]
    ];

    if(d.forum.linked){
      /* Prefer the URL IPS gave us. Without it — no API key configured, or
         the forum didn't answer — build the standard IPS profile path from
         the member id; the slug is cosmetic and IPS corrects a wrong one. */
      var fname = d.forum.name || DATA.name;
      /* index.php? is not optional here: the forum runs without friendly
         URLs, so /profile/1-name is a 404 and only the query form resolves.
         A profile_url from the API already carries whatever form the forum
         itself uses, so it wins when we have one. */
      var furl  = d.forum.profile_url || (d.forum.url + '/index.php?/profile/' + d.forum.member_id +
                  '-' + String(fname).toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'') + '/');
      rows.push(['Forum account',
        '<a class="ext" href="'+esc(furl)+'" target="_blank" rel="noopener">'+esc(fname)+
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 4h6v6"/>' +
        '<path d="M20 4l-8 8"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg></a>',
        null, false, true]);
    }

    $('acctRows').innerHTML = rows.map(function(r){
      /* r[4] marks a value that is already markup — only the forum link
         uses it, and it is built from escaped parts below. */
      return '<div class="kv"><span class="k">'+esc(r[0])+'</span>'+
        '<span class="v">'+(r[4] ? r[1] : esc(r[1]))+
        (r[2] ? ' <span class="sm">'+esc(r[2])+'</span>' : '')+
        (r[3] ? ' <button class="copy" id="copyId" title="Copy account ID" aria-label="Copy account ID">'+
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg></button>' : '')+
        '</span></div>';
    }).join('');
    var cp = $('copyId');
    if(cp) cp.addEventListener('click', function(){
      if(navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(String(d.id));
    });
  }

  /** The shared "there's nothing here yet" block. */
  function blank(icon, title, body){
    var paths = {
      user:'<circle cx="12" cy="8" r="4"/><path d="M5 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/>',
      doc:'<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/>',
      screen:'<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
      clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'
    };
    return '<div class="blank soon"><span class="ei">'+
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">'+(paths[icon]||paths.doc)+'</svg>'+
      '</span><h4>'+esc(title)+'</h4><p>'+esc(body)+'</p></div>';
  }

  /* --------------------------------------------------------------- SETTINGS */
  function renderSettings(d){
    /* UCP name */
    $('rowNameValue').innerHTML = '<b>'+esc(d.name)+'</b>';
    var nc = d.name_change;
    var nameBtn = $('btnNameOpen');
    if(nc.allowed){
      nameBtn.removeAttribute('disabled');
      nameBtn.textContent = 'Change';
      $('rowNameNote').innerHTML = 'The name you sign in with, and the name staff and other ' +
        'players know you by.' + (nc.cooldown_days > 0
          ? ' You can change it once every '+nc.cooldown_days+' days.' : '');
    } else {
      nameBtn.setAttribute('disabled','disabled');
      nameBtn.textContent = 'Locked';
      $('rowNameNote').innerHTML = 'You changed your UCP name recently. You can change it again ' +
        'in <b>'+nc.days_left+' '+(nc.days_left===1?'day':'days')+'</b>.';
      $('exName').classList.remove('on');
    }
    $('nameCooldownNote').innerHTML = (nc.cooldown_days > 0
        ? '<b>You can change this once every '+nc.cooldown_days+' days.</b> ' : '') +
      '<b>Your forum display name changes with it</b>, and your old name is released for ' +
      'someone else to take.';

    /* Email */
    $('rowEmailValue').innerHTML = '<b>'+esc(d.email)+'</b>';
    var pend = $('emailPending');
    if(d.email_change){
      pend.hidden = false;
      pend.innerHTML = ICONS_NOTE.info +
        '<div>A change to <b>'+esc(d.email_change.masked)+'</b> is waiting on the link we sent ' +
        'there. Until it\'s opened, this address stays on the account. Changing your password ' +
        'cancels it.</div>';
    } else {
      pend.hidden = true;
    }

    /* Password */
    var pw = d.password_changed_at ? new Date(d.password_changed_at*1000) : null;
    $('rowPwValue').innerHTML = pw
      ? 'Last changed <b>'+ago(pw)+'</b>'
      : '<span style="color:var(--stone)">We don\'t have a record of when this was last changed</span>';

    /* Delete — FEATURE-GATED on security.allow_self_delete */
    var canDelete = d.features.self_delete;
    $('delBtn').hidden    = !canDelete;
    $('delLocked').hidden = canDelete;
    $('delBlocked').hidden = canDelete;
    $('delAllowed').hidden = !canDelete;
    if(!canDelete){
      $('delLocked').setAttribute('data-expand','exDelete');
      $('delLocked').style.cursor = 'pointer';
    }
    $('delLosesName').innerHTML = 'Your UCP name — <b>'+esc(d.name)+'</b> is released for anyone else to take.';
  }

  /* --------------------------------------------------------------- SECURITY */
  function renderSecurity(d){
    var t = d.twofa;
    var state = t.enabled ? (t.backup_remaining <= 2 ? 'low' : 'on') : 'off';
    showTfa(state);

    if(t.enabled){
      var left = t.backup_remaining, total = t.backup_total;
      Array.prototype.forEach.call(document.querySelectorAll('[data-codes-left]'), function(el){
        el.textContent = left+' of '+total+' left';
      });
      Array.prototype.forEach.call(document.querySelectorAll('[data-pips]'), function(el){
        var out = '';
        for(var i=0;i<total;i++) out += '<i'+(i<left?'':' class="spent"')+'></i>';
        el.innerHTML = out;
      });
      $('lowCodesNote').innerHTML =
        '<b>You have '+left+' recovery code'+(left===1?'':'s')+' left.</b> If you lose your phone ' +
        'with none left, only a Founder can get you back in. Generate a new set now — it takes a ' +
        'few seconds and replaces all ten.';
      $('codesUsedNote').textContent = (total-left)+' have been used. Used codes never come back.';

      /* Staff who must have 2FA can't switch it off; the server refuses too. */
      Array.prototype.forEach.call(document.querySelectorAll('[data-disable-2fa]'), function(b){
        b.hidden = !!t.required;
      });
    }

    if(t.misconfigured){
      flash('err', '<b>Two-step verification is on but the server can\'t read your stored secret</b>, ' +
        'so no code will work. Contact a Founder — this needs fixing on the server, not on your phone.');
    }

    renderSessions(d);
    renderActivity(d);
  }

  /* ------------------------------------------------------------------ paging */
  /* Both lists below grow without limit — a signed-in device is added every
     time somebody opens the UCP from a new browser, and the activity log gets
     a line per sign-in. Left unpaged they turned the Security tab into a
     several-thousand-pixel scroll where the two cards underneath were
     unreachable in practice. Same pager as the record, same 8 a page, so the
     control means one thing everywhere on the page. */
  var PAGE_SIZE = 8;
  var PAGES = {};

  /**
   * @param key    a name for this list, used to remember which page it is on
   * @param items  the whole array
   * @param host   element to draw into
   * @param draw   function(slice) -> HTML for the rows
   */
  function paged(key, items, host, draw){
    if(PAGES[key] === undefined) PAGES[key] = 1;
    var total = items.length;
    var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if(PAGES[key] > pages) PAGES[key] = pages;
    var page  = PAGES[key];
    var first = (page-1)*PAGE_SIZE;
    var slice = items.slice(first, first+PAGE_SIZE);

    var nav = '';
    if(pages > 1){
      var b = [];
      b.push('<button data-pg="'+key+':'+(page-1)+'"'+(page<=1?' disabled':'')+'>Previous</button>');
      for(var i=1;i<=pages;i++){
        b.push('<button data-pg="'+key+':'+i+'"'+(i===page?' aria-current="true"':'')+'>'+i+'</button>');
      }
      b.push('<button data-pg="'+key+':'+(page+1)+'"'+(page>=pages?' disabled':'')+'>Next</button>');
      nav = '<div class="pager">'+
              '<span class="pcount">Showing '+(first+1)+'–'+Math.min(first+PAGE_SIZE,total)+
                ' of '+total+'</span>'+
              '<div class="pnav">'+b.join('')+'</div>'+
            '</div>';
    }
    host.innerHTML = draw(slice) + nav;
  }

  /* One listener for every pager on the page. The redraw goes back through
     the same render function, so a page number can never disagree with the
     rows under it. */
  document.addEventListener('click', function(ev){
    var b = ev.target.closest && ev.target.closest('[data-pg]'); if(!b) return;
    ev.preventDefault();
    var bits = b.getAttribute('data-pg').split(':');
    PAGES[bits[0]] = parseInt(bits[1], 10);
    if(!DATA) return;
    if(bits[0] === 'sess') renderSessions(DATA); else renderActivity(DATA);
    var card = b.closest('.card');
    if(card) card.scrollIntoView({block:'start', behavior:'smooth'});
  });

  /* ------------------------------------------------------ where you're signed in */
  var DEV_ICO = {
    desktop:'<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
    phone:'<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18.5h2"/>',
    tablet:'<rect x="4" y="2" width="16" height="20" rx="2.5"/><path d="M10 19h4"/>'
  };
  function renderSessions(d){
    if(!d.features.sessions){
      $('sessHost').innerHTML = blank('screen',
        'Not available yet',
        'Your UCP hasn\'t had the session table added yet, so there is nothing to list. ' +
        'Ask a Founder to run the sessions migration.');
      $('sessAside').textContent = '';
      $('sessLede').textContent  = 'You can still end every other session from the Settings tab.';
      return;
    }

    var s = d.sessions || [];
    $('sessAside').textContent = s.length === 1 ? '1 active session' : s.length+' active sessions';
    $('sessLede').textContent  = 'Anything you don\'t recognise, sign it out and change your password.';

    /* The exact stamp AND the relative one. "last active 2 hours ago" is the
       one somebody reads at a glance; the UTC time is the one they quote to
       staff when they report a sign-in that wasn't them, and a report with a
       relative time in it is worthless by the time it is read. */
    paged('sess', s, $('sessHost'), function(slice){
    return '<div class="rows">' + slice.map(function(x){
      var seen = new Date(x.last_seen*1000);
      var when = x.current ? 'active now' : 'last active '+ago(seen);
      var meta = [esc(x.ip), when];
      if(x.remembered && !x.current) meta.push('remembered on this device');
      return '<div class="sess">' +
        '<span class="sess-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
          (DEV_ICO[x.kind] || DEV_ICO.desktop) + '</svg></span>' +
        '<div class="sess-body">' +
          '<div class="n">'+esc(x.device)+
            (x.current ? ' <span class="tagnow">This device</span>' : '')+'</div>' +
          '<div class="s">'+meta.join(' · ')+'</div>' +
          '<div class="s stamp-utc">'+esc(shortDate(seen))+', '+esc(hhmm(seen))+'</div>' +
        '</div>' +
        '<div class="row-r">' + (x.current
          ? '<button class="btn" disabled>Current</button>'
          : '<button class="btn danger" data-revoke="'+esc(x.id)+'">Sign out</button>') +
        '</div></div>';
    }).join('') + '</div>';
    });
  }

  /* One click, no confirmation. Ending a session is the safe direction — the
     worst case is signing yourself out of a device you still have. */
  document.addEventListener('click', function(e){
    var b = e.target.closest && e.target.closest('[data-revoke]');
    if(!b) return;
    e.preventDefault();
    busyBtn(b, true, 'Signing out…');
    UCP.post('session-revoke.php', {id: b.getAttribute('data-revoke')}).then(function(res){
      if(bounceIfSignedOut(res)) return;
      var r = res.data || {};
      busyBtn(b, false);
      if(!r.ok){ flash('err', esc(r.error || 'That device could not be signed out.')); return; }
      flash('ok', esc(r.message));
      reload();
    }).catch(function(){
      busyBtn(b, false);
      flash('err', 'Could not reach the server. Try again.');
    });
  });

  /* ------------------------------------------------------- security activity */
  /* The server sends an event name and a plain-English detail; the wording of
     the headline lives here so it can be changed without a migration. */
  var EVENTS = {
    signin:            'Signed in',
    signin_failed:     'Failed sign-in attempt',
    signout:           'Signed out',
    password_changed:  'Password changed',
    email_change_requested: 'Email change requested',
    email_changed:     'Email address changed',
    name_changed:      'UCP name changed',
    '2fa_enabled':     'Two-step verification turned on',
    '2fa_disabled':    'Two-step verification turned off',
    '2fa_codes':       'Recovery codes replaced',
    sessions_revoked:  'Signed out everywhere',
    session_revoked:   'A device was signed out',
    challenge_failed:  'Failed confirmation'
  };
  function renderActivity(d){
    if(!d.features.activity_log){
      $('logHost').innerHTML = blank('clock',
        'Not available yet',
        'Your UCP hasn\'t had the activity table added yet. Ask a Founder to run the ' +
        'sessions migration and this fills in from your next sign-in.');
      $('logAside').textContent = '';
      return;
    }

    var a = d.activity || [];
    $('logAside').textContent = 'Times in UTC';

    if(!a.length){
      $('logHost').innerHTML = blank('clock', 'Nothing recorded yet',
        'This fills in from your next sign-in, and every time your password, email or ' +
        'two-step settings change.');
      return;
    }

    paged('log', a, $('logHost'), function(slice){
    return '<div class="log">' + slice.map(function(x){
      var t = new Date(x.at*1000);
      var head = EVENTS[x.event] || 'Account activity';
      var stamp = shortDate(t)+', '+hhmm(t);
      return '<div class="log-row '+(x.level === 'good' ? 'good' : x.level === 'warn' ? 'warn' : '')+'">' +
        '<span class="log-dot"></span>' +
        '<div class="log-t"><b>'+esc(head)+'</b>'+(x.detail ? ' · '+esc(x.detail) : '')+'</div>' +
        '<div class="log-s">'+esc(stamp)+' · '+esc(x.device)+' · '+esc(x.ip)+'</div>' +
      '</div>';
    }).join('') + '</div>';
    });
  }

  /* ----------------------------------------------------------------- RECORD */
  /* =====================================================================
     THE ADMINISTRATIVE RECORD

     Everything below is driven by api/_punish.php's record_for(), plus the
     appeal list and — on the staff view only — the Scratchpad. The same
     code runs on profile.html and on dashboard/lookup.html; the differences
     between what a player sees and what an administrator sees come down as
     flags on the payload rather than being decided here.

     What the payload does NOT contain is the honest part: a player is never
     sent the issuing administrator's name, and api/profile.php never sends
     the Scratchpad at all. Hiding either in the page would only be hiding
     it from somebody who does not open dev tools.
     ===================================================================== */

  var RECS  = { entries: [], cards: [], counts: {}, summary: null };
  var RECPG = {};                       // page number per card
  var RECOPEN = null;                   // {id, mode:'edit'|'delete'}
  var REC_PER = 5;

  var APPEALS = [], APPAGE = 1, AP_PER = 4;
  var PADNOTES = [], PADPAGE = 1, PAD_PER = 3, PADDEL = null;

  var REC_STAFF = false;                // is this the read-only staff view
  var REC_WHO   = { name: '', id: 0 };  // whose record
  var REC_VIEWER = '';                  // who is looking, staff view only

  function recD(ts){ var d = new Date(ts*1000);
    return MON[d.getUTCMonth()]+' '+String(d.getUTCDate()).padStart(2,'0')+', '+d.getUTCFullYear(); }
  function recT(ts){ var d = new Date(ts*1000);
    return String(d.getUTCHours()).padStart(2,'0')+':'+String(d.getUTCMinutes()).padStart(2,'0')+' UTC'; }
  function recAgo(ts){ return ago(new Date(ts*1000)); }
  function recN(n, w){ return n + ' ' + w + (n === 1 ? '' : 's'); }
  function recEnt(n){ return n + (n === 1 ? ' entry' : ' entries'); }

  /* Two helpers this block owns rather than borrows: profile.html has
     recBusy() but no toneOf(), dashboard/lookup.html has toneOf() but no
     recBusy(), and the record renderer is the same code on both pages. */
  function recTone(r){ return 'tone-' + (r >= 0 && r <= 9 ? r : 0); }
  function recBusy(btn, on, label){
    if(!btn) return;
    if(on){ btn.dataset.was = btn.textContent; btn.textContent = label || 'Working…';
            btn.disabled = true; }
    else  { if(btn.dataset.was) btn.textContent = btn.dataset.was; btn.disabled = false; }
  }

  var CHEV_L = '<svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>';
  var CHEV_R = '<svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>';

  /* Three numbers and two arrows, at every length. A pager that grows a
     button per page is a control whose size depends on the data — it looks
     different on every account and becomes a wall on a long record. The
     window slides; the control does not change shape. */
  function recPager(scope, page, pages, total, per){
    if(pages < 2) return '';
    var first = (page-1)*per + 1, last = Math.min(page*per, total);
    var start = Math.min(Math.max(1, page - 1), Math.max(1, pages - 2));
    var b = ['<button type="button" class="arrow" data-pg="'+scope+':'+(page-1)+'"'+
             (page<=1?' disabled':'')+' aria-label="Previous page">'+CHEV_L+'</button>'];
    for(var n = start; n < start + 3 && n <= pages; n++){
      b.push('<button type="button" data-pg="'+scope+':'+n+'"'+
             (n===page?' aria-current="true"':'')+'>'+n+'</button>');
    }
    b.push('<button type="button" class="arrow" data-pg="'+scope+':'+(page+1)+'"'+
           (page>=pages?' disabled':'')+' aria-label="Next page">'+CHEV_R+'</button>');
    return '<div class="pager">'+
      '<span class="pcount">Showing <b>'+first+'–'+last+'</b> of <b>'+total+'</b>'+
      ' &nbsp;·&nbsp; page '+page+' of '+pages+'</span>'+
      '<div class="pnav">'+b.join('')+'</div></div>';
  }

  /* An administrator's name in their group's colour, the same as it is drawn
     everywhere else in the UCP. Ranks come down with the entry. */
  function recWho(name, rank){
    if(!name) return '<b>Unknown</b>';
    return '<b class="who '+recTone(rank|0)+'">'+esc(name)+'</b>';
  }

  /* -------------------------------------------------------- the panel */
  function renderCert(){
    var s = RECS.summary || {};
    var box = $('cert');
    box.className = 'cert ' + (s.level || 'good');
    $('certH').textContent = s.head || '';
    $('certS').textContent = s.note || '';

    var stats = [
      {k:'Entries total', v:s.total|0, cls:(s.total ? '' : 'zero'),
       p:(s.bans|0)+' bans · '+(s.warnings|0)+' warnings · '+(s.kicks|0)+' kicks'},
      {k:'Active now', v:s.active|0, cls:(s.active ? 'bad' : 'zero'),
       p:s.active ? (s.active === 1 ? 'Ban running' : 'Bans running') : 'No active bans'},
      {k:'Last 30 days', v:s.recent|0, cls:(s.recent ? 'hit' : 'zero'),
       p:s.recent ? 'Entries added recently' : 'Nothing added recently'},
      {k:'Last entry', v:s.last_at ? recD(s.last_at) : '—', sm:true,
       cls:(s.last_at ? '' : 'zero'),
       p:s.last_at ? recT(s.last_at)+' · '+recAgo(s.last_at) : 'No entries'},
      {k:'First entry', v:s.first_at ? recD(s.first_at) : '—', sm:true,
       cls:(s.first_at ? '' : 'zero'),
       p:s.first_at ? recT(s.first_at)+' · '+recAgo(s.first_at) : 'No entries'}
    ];
    $('certStats').innerHTML = stats.map(function(x){
      return '<div><div class="k">'+x.k+'</div>'+
             '<div class="v '+(x.cls||'')+(x.sm?' sm':'')+'">'+esc(x.v)+'</div>'+
             '<div class="p">'+x.p+'</div></div>';
    }).join('');

    /* The strap line. Without it a screenshot of this proves nothing about
       when it was true, and on the staff view nothing about who took it. */
    var now = new Date();
    $('certFoot').innerHTML =
      '<span>Record for <b>'+esc(REC_WHO.name)+'</b> · UCP <b>#'+(REC_WHO.id|0)+'</b></span>'+
      '<span class="dot">|</span>'+
      '<span>Captured <b>'+shortDate(now)+', '+hhmm(now)+'</b></span>'+
      (REC_STAFF && REC_VIEWER
        ? '<span class="dot">|</span><span>Viewed by <b>'+esc(REC_VIEWER)+'</b></span>' : '')+
      '<span class="dot">|</span><span>ucp.blaineside.com</span>';
  }

  /* ---------------------------------------------------- past appeals */
  var AP_LABEL = {pending:'Pending', accepted:'Accepted', rejected:'Rejected'};

  function renderAppeals(){
    var open = APPEALS.filter(function(a){ return a.status === 'pending'; }).length;
    $('appealCount').textContent = APPEALS.length
      ? recN(APPEALS.length, 'appeal') + (open ? ' · ' + open + ' outstanding' : '')
      : 'None';
    $('appealLede').textContent = REC_STAFF
      ? 'Every appeal this player has made, newest first. Open one to read it in full — the '
        + 'verdict, who gave it, and the staff-only notes on it.'
      : 'Every appeal you have made, and what happened to it. Open one to read the verdict and '
        + 'anything staff wrote on it.';

    if(!APPEALS.length){
      $('appealList').innerHTML = '<div class="secblank">'+
        (REC_STAFF ? 'This player has never appealed anything.'
                   : 'You have never appealed anything.')+'</div>';
      $('appealPager').innerHTML = '';
      return;
    }

    var pages = Math.max(1, Math.ceil(APPEALS.length / AP_PER));
    if(APPAGE > pages) APPAGE = pages;
    var slice = APPEALS.slice((APPAGE-1)*AP_PER, APPAGE*AP_PER);

    $('appealList').innerHTML = '<div class="aplist">' + slice.map(function(a){
      var sub = ['Submitted ' + recD(a.at) + ', ' + recT(a.at)];
      if(a.handler) sub.push('Handled by <b>'+esc(a.handler)+'</b>');
      if(a.note)    sub.push(esc(a.note));
      return '<div class="aprow" data-appeal="'+a.id+'" role="link" tabindex="0">'+
        '<span class="num">#'+a.id+'</span>'+
        '<span>'+
          '<span class="t">'+esc(a.against)+
            (a.what ? '<span class="vs">&nbsp; '+esc(a.what)+'</span>' : '')+'</span>'+
          '<span class="s">'+sub.join('<span class="dot">·</span>')+'</span>'+
        '</span>'+
        '<span class="ap '+esc(a.status)+'">'+(AP_LABEL[a.status] || esc(a.status))+'</span>'+
        '<svg class="go" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>'+
      '</div>';
    }).join('') + '</div>';

    $('appealPager').innerHTML = recPager('appeal', APPAGE, pages, APPEALS.length, AP_PER);
  }

  /* ------------------------------------------------------- one entry */
  function recEntry(e){
    var open = RECOPEN && RECOPEN.id === e.id ? RECOPEN.mode : null;

    /* Active or ended, and only where that means something. A warning or a
       kick does not run and then stop — it happened, and a status pill on
       one would be inventing a fact about it. */
    var state = '';
    if(e.stateful){
      state = (e.active ? '<span class="st live">Active</span>'
                        : '<span class="st done">Ended</span>')
            + (e.appeal
                ? '<a class="ap '+esc(e.appeal.status)+'" href="/dashboard/appeals?id='+
                  e.appeal.id+'">Appeal '+esc(e.appeal.status)+'</a>'
                : '<span class="ap none">Not appealed</span>');
    }

    var length = null;
    if(e.kind === 'ban'){
      length = e.permanent ? 'Permanent'
             : (e.expires_at ? Math.max(1, Math.round((e.expires_at - e.issued_at)/86400)) + ' days'
                             : 'Permanent');
      if(length === '1 days') length = '1 day';
    } else if(e.kind === 'user_lock'){
      length = 'Permanent';
    }

    var head =
      '<div class="ent-1">'+
        '<span class="kind '+esc(e.card)+'">'+esc(e.label)+'</span>'+
        '<span class="stampd">'+recD(e.issued_at)+'<span class="t">'+recT(e.issued_at)+'</span></span>'+
        '<span class="ref">#'+e.id+'</span>'+
        (state ? '<span class="state">'+state+'</span>' : '')+
      '</div>';

    var why = '<div class="ent-why'+(e.reason ? '' : ' empty')+'">'+
      (e.reason ? esc(e.reason) : 'No reason was recorded when this was issued.')+'</div>';

    if(open === 'edit'){
      return '<div class="ent editing" data-rid="'+e.id+'">'+ head +
        '<div class="modehead">'+
          '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>'+
          'Editing the reason on entry #'+e.id+
        '</div>'+
        '<textarea class="fld" rows="3" id="recEdit'+e.id+'" maxlength="400"></textarea>'+
        '<div class="fldnote">Only the wording changes. The kind, the date, the length and who '+
          'issued it are what the entry <i>is</i>, and cannot be edited here — if the '+
          'punishment itself was wrong it gets lifted or deleted, not rewritten into a different '+
          'one. The previous wording is kept in the punishment log, and the entry will say that '+
          'you changed it.</div>'+
        '<div class="rowbtns">'+
          '<button type="button" class="mini gold" data-resave="'+e.id+'">Save reason</button>'+
          '<button type="button" class="mini" data-recancel="1">Cancel</button>'+
        '</div></div>';
    }

    if(open === 'delete'){
      var extra = (e.kind === 'user_lock' && e.active)
        ? ' The lock comes off with it and the account will be able to sign in again.'
        : (e.active ? ' It is active right now — deleting it ends it.' : '');
      return '<div class="ent deleting" data-rid="'+e.id+'">'+ head + why +
        '<div class="modehead">'+
          '<svg viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L2.6 17a1.9 1.9 0 0 0 1.7 2.9h15.4a1.9 1.9 0 0 0 1.7-2.9L13.7 3.9a1.9 1.9 0 0 0-3.4 0z"/></svg>'+
          'Delete entry #'+e.id+
        '</div>'+
        '<div class="warnbox"><b>This cannot be undone.</b> The entry is removed from the record '+
          'completely — it disappears from the summary, from the player’s own view and '+
          'from every count on this page, as though it had never been issued.'+extra+
          ' A record of the deletion, with the entry inside it, is kept in the punishment log.</div>'+
        '<input class="fld" id="recWhy'+e.id+'" maxlength="200" '+
          'placeholder="Why is it being removed? (required)">'+
        '<label class="confirmline">'+
          '<input type="checkbox" data-reconfirm="'+e.id+'">'+
          'I understand this removes entry #'+e.id+' from the record permanently.'+
        '</label>'+
        '<div class="rowbtns">'+
          '<button type="button" class="mini red" data-redelgo="'+e.id+'" disabled>'+
            'Delete entry #'+e.id+'</button>'+
          '<button type="button" class="mini" data-recancel="1">Cancel</button>'+
        '</div></div>';
    }

    var meta = [];
    if(e.issued_by) meta.push('Issued by ' + recWho(e.issued_by, e.issued_rank));
    if(length) meta.push(esc(length));
    if(e.edited_at && REC_STAFF){
      meta.push('<span class="edit">reason edited by '+esc(e.edited_by || 'an administrator')+
                ' · '+recD(e.edited_at)+', '+recT(e.edited_at)+'</span>');
    }

    var acts = (e.can_edit || e.can_delete)
      ? '<div class="acts">'+
          (e.can_edit   ? '<button type="button" data-reedit="'+e.id+'">Edit reason</button>' : '')+
          (e.can_delete ? '<button type="button" class="dg" data-redel="'+e.id+'">Delete entry</button>' : '')+
        '</div>'
      : '';

    return '<div class="ent" data-rid="'+e.id+'">'+ head + why +
      (meta.length ? '<div class="ent-meta">'+meta.join('<span class="dot">·</span>')+'</div>' : '')+
      acts + '</div>';
  }

  /* ----------------------------------------------------- the four cards */
  function renderRecCards(){
    $('recCards').innerHTML = (RECS.cards || []).map(function(c){
      var list = RECS.entries.filter(function(e){ return e.card === c.key; });
      var pages = Math.max(1, Math.ceil(list.length / REC_PER));
      if(!RECPG[c.key] || RECPG[c.key] > pages) RECPG[c.key] = Math.min(RECPG[c.key] || 1, pages);
      var page = RECPG[c.key];
      var slice = list.slice((page-1)*REC_PER, page*REC_PER);
      /* Only bans and locks can be active, so only their headers say so. */
      var active = (c.key === 'ban' || c.key === 'lock')
        ? list.filter(function(e){ return e.active; }).length : 0;

      var body = list.length
        ? '<div class="list">'+slice.map(recEntry).join('')+'</div>'+
          recPager(c.key, page, pages, list.length, REC_PER)
        : '<div class="secblank">'+esc(c.blank)+'</div>';

      return '<div class="card">'+
        '<div class="card-h">'+
          '<h3><span class="dotk '+c.key+'"></span>'+esc(c.title)+'</h3>'+
          '<span class="aside"><span class="tally-pill'+(active?' hot':'')+'">'+
            (list.length ? recEnt(list.length) : 'None on file')+
            (active ? ' · '+active+' active' : '')+
          '</span></span>'+
        '</div>'+
        '<div class="card-b"><p class="card-lede">'+esc(c.lede)+'</p>'+ body +'</div>'+
      '</div>';
    }).join('');
  }

  /* -------------------------------------------------------- Scratchpad */
  function renderPad(){
    var pages = Math.max(1, Math.ceil(PADNOTES.length / PAD_PER));
    if(PADPAGE > pages) PADPAGE = pages;
    var slice = PADNOTES.slice((PADPAGE-1)*PAD_PER, PADPAGE*PAD_PER);

    $('padCount').textContent = PADNOTES.length ? recN(PADNOTES.length, 'note') : 'No notes';

    if(!PADNOTES.length){
      $('padNotes').innerHTML = '<div class="secblank">Nothing has been noted on this account '+
        'yet. Anything staff want on file that is not a punishment goes here.</div>';
      $('padPager').innerHTML = '';
      return;
    }

    $('padNotes').innerHTML = slice.map(function(n){
      if(PADDEL === n.id){
        return '<div class="pnote deleting">'+
          '<div class="note-h"><b>'+esc(n.by)+'</b>'+
            '<span class="rk '+recTone(n.rank|0)+'">'+esc(n.rank_name)+'</span>'+
            '<span class="dot">·</span><span>'+recD(n.at)+', '+recT(n.at)+'</span></div>'+
          '<div class="note-b">'+esc(n.body)+'</div>'+
          '<div class="modehead">'+
            '<svg viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L2.6 17a1.9 1.9 0 0 0 1.7 2.9h15.4a1.9 1.9 0 0 0 1.7-2.9L13.7 3.9a1.9 1.9 0 0 0-3.4 0z"/></svg>'+
            'Delete this note'+
          '</div>'+
          '<div class="warnbox">Deleting a note removes it for everyone. Notes are how the next '+
            'administrator finds out what the last one already knew, so remove one when it is '+
            'wrong — not when it is old.</div>'+
          '<div class="rowbtns">'+
            '<button type="button" class="mini red" data-npdelgo="'+n.id+'">Yes, delete this note</button>'+
            '<button type="button" class="mini" data-npcancel="1">Cancel</button>'+
          '</div></div>';
      }
      return '<div class="pnote">'+
        '<div class="note-h">'+
          '<b>'+esc(n.by)+'</b>'+
          '<span class="rk '+recTone(n.rank|0)+'">'+esc(n.rank_name)+'</span>'+
          '<span class="dot">·</span><span>'+recD(n.at)+', '+recT(n.at)+'</span>'+
          '<span class="dot">·</span><span>'+recAgo(n.at)+'</span>'+
          (n.can_delete ? '<button type="button" class="del" data-npdel="'+n.id+'">Delete</button>' : '')+
        '</div>'+
        '<div class="note-b">'+esc(n.body)+'</div>'+
      '</div>';
    }).join('');

    $('padPager').innerHTML = recPager('pad', PADPAGE, pages, PADNOTES.length, PAD_PER);
  }

  /**
   * @param d      the whole payload from profile.php or admin-account.php
   * @param staff  true on the read-only administrative view
   */
  function renderRecord(d, staff){
    REC_STAFF = !!staff;
    REC_WHO   = { name: d.name || '', id: d.id || 0 };
    REC_VIEWER = staff && d.viewer
      ? (d.viewer.name || '') + (d.viewer.role ? ' (' + d.viewer.role + ')' : '')
      : '';

    var rec = d.record || {};
    if(!rec.available){
      $('recGate').hidden = false;
      $('recLive').hidden = true;
      $('recGateBody').innerHTML = blank('doc', 'Not available yet',
        'The punishment tables haven’t been added to this UCP yet. Ask a Founder to run ' +
        'docs/migration-appeals.sql and this fills in — bans, warnings, kicks and user ' +
        'locks all appear here as soon as they do.');
      return;
    }

    $('recGate').hidden = true;
    $('recLive').hidden = false;

    RECS = { entries: rec.entries || [], cards: rec.cards || [],
             counts: rec.counts || {}, summary: rec.summary || {} };
    APPEALS = d.appeals || [];

    var pad = d.scratchpad;
    $('padCard').hidden = !(staff && pad);
    if(staff && pad){
      PADNOTES = pad.notes || [];
      $('padWrite').hidden = !pad.available;
      if(!pad.available){
        $('padNotes').innerHTML = '<div class="secblank">The Scratchpad isn’t set up on ' +
          'this server yet — docs/migration-scratchpad.sql hasn’t been run.</div>';
        $('padPager').innerHTML = '';
        $('padCount').textContent = '';
      }
    }

    renderCert();
    renderAppeals();
    renderRecCards();
    if(staff && pad && pad.available) renderPad();
  }

  /* ============================ interactions ============================ */

  document.addEventListener('click', function(ev){
    var t = ev.target; if(!t.closest) return;
    var hit;

    if(t.closest('#appealToggle')){
      var card = $('appealCard');
      var now = !card.classList.contains('open');
      card.classList.toggle('open', now);
      $('appealToggle').setAttribute('aria-expanded', now ? 'true' : 'false');
      return;
    }
    if((hit = t.closest('[data-appeal]'))){
      window.location.href = '/dashboard/appeals?id=' + hit.getAttribute('data-appeal');
      return;
    }
    if((hit = t.closest('[data-pg]'))){
      var bits = hit.getAttribute('data-pg').split(':');
      if(bits[0] === 'appeal'){ APPAGE = +bits[1]; renderAppeals(); }
      else if(bits[0] === 'pad'){ PADPAGE = +bits[1]; PADDEL = null; renderPad(); }
      else { RECPG[bits[0]] = +bits[1]; RECOPEN = null; renderRecCards(); }
      return;
    }

    /* ---- record entry: edit ---- */
    if((hit = t.closest('[data-reedit]'))){
      var id = +hit.getAttribute('data-reedit');
      RECOPEN = {id: id, mode: 'edit'};
      renderRecCards();
      var f = $('recEdit'+id), e = recById(id);
      if(f){ f.value = (e && e.reason) || ''; f.focus();
             f.setSelectionRange(f.value.length, f.value.length); }
      return;
    }
    if((hit = t.closest('[data-redel]'))){
      RECOPEN = {id: +hit.getAttribute('data-redel'), mode: 'delete'};
      renderRecCards();
      var w = $('recWhy'+RECOPEN.id); if(w) w.focus();
      return;
    }
    if(t.closest('[data-recancel]')){ RECOPEN = null; renderRecCards(); return; }

    if((hit = t.closest('[data-resave]'))){
      var sid = +hit.getAttribute('data-resave');
      var box = $('recEdit'+sid);
      var val = box ? box.value.trim() : '';
      if(!val){
        flash('err', 'Write the reason. An entry with no reason on it is worse than a badly '+
                     'worded one.');
        return;
      }
      recBusy(hit, true, 'Saving…');
      UCP.post('punishment-edit.php', {id: sid, reason: val}).then(function(res){
        var r = res.data || {};
        recBusy(hit, false);
        if(!r.ok){ flash('err', esc(r.error || 'That could not be saved.')); return; }
        var e = recById(sid);
        if(e){ e.reason = r.reason; e.edited_at = r.edited_at || null;
               e.edited_by = r.edited_by || null; }
        RECOPEN = null; renderRecCards();
        flash('ok', esc(r.message || 'Reason updated.'));
      }).catch(function(){
        recBusy(hit, false);
        flash('err', 'Could not reach the server. Try again.');
      });
      return;
    }

    if((hit = t.closest('[data-redelgo]'))){
      var did = +hit.getAttribute('data-redelgo');
      var wf = $('recWhy'+did);
      var why = wf ? wf.value.trim() : '';
      if(!why){ flash('err', 'Say why it is being removed.'); if(wf) wf.focus(); return; }
      recBusy(hit, true, 'Deleting…');
      UCP.post('punishment-delete.php', {id: did, why: why}).then(function(res){
        var r = res.data || {};
        recBusy(hit, false);
        if(!r.ok){ flash('err', esc(r.error || 'That could not be deleted.')); return; }
        flash('ok', esc(r.message || 'Entry deleted.'));
        /* The summary, the counts and the account's own status all move when
           an entry goes, so the page is rebuilt from the server rather than
           patched here. */
        RECOPEN = null; reload();
      }).catch(function(){
        recBusy(hit, false);
        flash('err', 'Could not reach the server. Try again.');
      });
      return;
    }

    /* ---- scratchpad ---- */
    if(t.closest('#padAdd')){
      var ta = $('padInput');
      var body = ta.value.trim();
      if(!body){ ta.focus(); return; }
      var btn = t.closest('#padAdd');
      recBusy(btn, true, 'Adding…');
      UCP.post('scratchpad-add.php', {account_id: REC_WHO.id, body: body}).then(function(res){
        var r = res.data || {};
        recBusy(btn, false);
        if(!r.ok){ flash('err', esc(r.error || 'That note could not be added.')); return; }
        PADNOTES.unshift(r.note);
        ta.value = ''; PADPAGE = 1; renderPad();
      }).catch(function(){
        recBusy(btn, false);
        flash('err', 'Could not reach the server. Try again.');
      });
      return;
    }
    if((hit = t.closest('[data-npdel]'))){ PADDEL = +hit.getAttribute('data-npdel'); renderPad(); return; }
    if(t.closest('[data-npcancel]')){ PADDEL = null; renderPad(); return; }
    if((hit = t.closest('[data-npdelgo]'))){
      var nid = +hit.getAttribute('data-npdelgo');
      recBusy(hit, true, 'Deleting…');
      UCP.post('scratchpad-delete.php', {id: nid}).then(function(res){
        var r = res.data || {};
        recBusy(hit, false);
        if(!r.ok){ flash('err', esc(r.error || 'That note could not be removed.')); return; }
        PADNOTES = PADNOTES.filter(function(x){ return x.id !== nid; });
        PADDEL = null; renderPad();
      }).catch(function(){
        recBusy(hit, false);
        flash('err', 'Could not reach the server. Try again.');
      });
      return;
    }
  });

  /* The delete button stays dead until the box is ticked. Deleting an entry
     is not undoable and the button sits inside a list, which is exactly
     where a mis-click happens. */
  document.addEventListener('change', function(ev){
    var c = ev.target.closest && ev.target.closest('[data-reconfirm]');
    if(!c) return;
    var b = document.querySelector('[data-redelgo="'+c.getAttribute('data-reconfirm')+'"]');
    if(b) b.disabled = !c.checked;
  });

  /* A row that behaves like a link should behave like one on a keyboard. */
  document.addEventListener('keydown', function(ev){
    if(ev.key !== 'Enter' && ev.key !== ' ') return;
    var r = ev.target.closest && ev.target.closest('[data-appeal]');
    if(!r) return;
    ev.preventDefault();
    window.location.href = '/dashboard/appeals?id=' + r.getAttribute('data-appeal');
  });

  function recById(id){
    for(var i = 0; i < RECS.entries.length; i++){
      if(+RECS.entries[i].id === +id) return RECS.entries[i];
    }
    return null;
  }

  /* =====================================================================
     TWO-FACTOR — panel switching
     ===================================================================== */
  var tfaPanels = {};
  Array.prototype.forEach.call(document.querySelectorAll('[data-state]'), function(el){
    tfaPanels[el.getAttribute('data-state')] = el;
  });
  function showTfa(name){
    var step = name.indexOf('setup')===0 ? +name.slice(5) : 0;
    var key  = step ? 'setup' : name;
    Object.keys(tfaPanels).forEach(function(k){ tfaPanels[k].hidden = (k !== key); });
    if(step){
      Array.prototype.forEach.call(tfaPanels.setup.querySelectorAll('.setup-step'), function(s){
        s.hidden = +s.getAttribute('data-s') !== step;
      });
      Array.prototype.forEach.call(tfaPanels.setup.querySelectorAll('.step'), function(s){
        var i = +s.getAttribute('data-step');
        s.classList.toggle('on',   i === step);
        s.classList.toggle('done', i <  step);
      });
    }
  }

  /* step 1 -> ask for the password, which is also what mints the secret */
  document.addEventListener('click', function(e){
    var b = e.target.closest && e.target.closest('[data-step-go]'); if(!b) return;
    e.preventDefault();
    var go = b.getAttribute('data-step-go');
    if(go === 'off'){
      setField('fldSetupPw','indSetupPw','hintSetupPw',null,'');
      $('setupPw').value = '';
      showTfa(DATA && DATA.twofa.enabled ? (DATA.twofa.backup_remaining<=2?'low':'on') : 'off');
      return;
    }
    if(go === '1'){ setField('fldSetupPw','indSetupPw','hintSetupPw',null,''); $('setupPw').value=''; showTfa('setup1'); return; }
    if(go === '2'){ startSetup(); return; }
  });

  /** POST /api/2fa-setup.php — password in, secret + otpauth URI back. */
  function startSetup(){
    var pw = $('setupPw').value;
    if(!pw){
      setField('fldSetupPw','indSetupPw','hintSetupPw','err','Enter your password to continue.');
      $('setupPw').focus(); return;
    }
    var btn = $('btnSetupNext');
    busyBtn(btn, true, 'Checking…');
    UCP.post('2fa-setup.php', {password: pw}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        setField('fldSetupPw','indSetupPw','hintSetupPw','err', esc(d.error || 'That did not work.'));
        $('setupPw').select(); return;
      }
      $('keyVal').textContent = d.secret_pretty || d.secret;
      $('keyVal').dataset.raw = d.secret || '';
      drawQR(d.uri);
      $('setCode').value = '';
      setField('fldSetCode','indSetCode','hintSetCode',null,
        'The code changes every 30 seconds. If it keeps being rejected, check your phone\'s date and time are set to automatic.');
      showTfa('setup2');
      $('setCode').focus();
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldSetupPw','indSetupPw','hintSetupPw','err','Could not reach the server. Try again.');
    });
  }

  function drawQR(uri){
    var box = $('qrBox');
    box.innerHTML = '';
    box.style.background = '#fff';
    try{
      var qr = window.qrcode(0,'M');
      qr.addData(uri); qr.make();
      box.innerHTML = qr.createSvgTag({cellSize:4, margin:2, scalable:true});
    }catch(err){
      box.style.background = 'none';
      box.innerHTML = '<div style="color:var(--stone);font-size:12px;line-height:1.5;padding:8px">' +
        'Couldn\'t draw the QR code — enter the key beside it by hand instead.</div>';
    }
  }

  /** POST /api/2fa-confirm.php — the code proves the app has the secret. */
  function confirmSetup(){
    var code = $('setCode').value.replace(/\D/g,'');
    if(code.length !== 6){
      setField('fldSetCode','indSetCode','hintSetCode','err','Enter the 6-digit code from your app.');
      $('setCode').focus(); return;
    }
    var btn = $('btnConfirm2fa');
    busyBtn(btn, true, 'Checking…');
    UCP.post('2fa-confirm.php', {code: code}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        setField('fldSetCode','indSetCode','hintSetCode','err', esc(d.error || 'That code did not match.'));
        $('setCode').value = ''; $('setCode').focus();
        if(d.restart) setTimeout(function(){ showTfa('off'); reload(); }, 2500);
        return;
      }
      showCodes(d.backup_codes || [], true);
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldSetCode','indSetCode','hintSetCode','err','Could not reach the server. Try again.');
    });
  }
  $('setCode').addEventListener('input', function(){
    var v = this.value.replace(/\D/g,'').slice(0,6);
    this.value = v;
    if(v.length === 6) confirmSetup();
  });
  $('setCode').addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();confirmSetup();} });
  $('btnConfirm2fa').addEventListener('click', function(e){ e.preventDefault(); confirmSetup(); });
  $('btnSetupNext').addEventListener('click', function(e){ e.preventDefault(); startSetup(); });
  $('setupPw').addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();startSetup();} });
  $('setupPw').addEventListener('input', function(){ setField('fldSetupPw','indSetupPw','hintSetupPw',null,''); });

  /* ---- recovery codes ---- */
  var currentCodes = [];
  function showCodes(codes, firstTime){
    currentCodes = codes;
    $('codeList').innerHTML = codes.map(function(c){ return '<span>'+esc(c)+'</span>'; }).join('');
    $('codesTitle').textContent   = firstTime ? 'Save your recovery codes' : 'Your new recovery codes';
    $('codesEyebrow').textContent = firstTime ? '' : 'Replaces your previous set';
    var st=$('tfaSteps'); if(st) st.hidden = !firstTime;
    $('ack').classList.remove('on');
    $('ackDone').setAttribute('aria-disabled','true');
    showTfa('setup3');
    window.scrollTo({top:0, behavior:'smooth'});
  }
  function codesText(){
    return 'BlaineSide UCP — recovery codes\r\n' +
      'Account: ' + (DATA ? DATA.name : '') + '\r\n' +
      'Issued: ' + new Date().toISOString().slice(0,10) + '\r\n' +
      'Each code can be used once, in place of your authenticator code.\r\n\r\n' +
      currentCodes.join('\r\n') + '\r\n';
  }
  $('btnCopyCodes').addEventListener('click', function(){
    var btn = this, txt = codesText();
    var done = function(){
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg> Copied';
      setTimeout(function(){ btn.innerHTML = btn.dataset.orig; }, 1800);
    };
    if(!btn.dataset.orig) btn.dataset.orig = btn.innerHTML;
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done, fallback); }
    else fallback();
    function fallback(){
      var ta = document.createElement('textarea');
      ta.value = txt; ta.style.position='fixed'; ta.style.opacity='0';
      document.body.appendChild(ta); ta.select();
      try{ document.execCommand('copy'); done(); }catch(e){}
      document.body.removeChild(ta);
    }
  });
  $('btnDownloadCodes').addEventListener('click', function(){
    var blob = new Blob([codesText()], {type:'text/plain'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'blaineside-recovery-codes.txt';
    document.body.appendChild(a); a.click();
    setTimeout(function(){ URL.revokeObjectURL(a.href); a.remove(); }, 0);
  });
  $('btnPrintCodes').addEventListener('click', function(){ window.print(); });
  $('ack').addEventListener('click', function(){
    this.classList.toggle('on');
    $('ackDone').setAttribute('aria-disabled', this.classList.contains('on') ? 'false' : 'true');
  });
  $('ackDone').addEventListener('click', function(e){
    e.preventDefault();
    if(this.getAttribute('aria-disabled') === 'true') return;
    currentCodes = []; $('codeList').innerHTML = '';
    reload();
  });
  $('keyVal').addEventListener('click', function(){
    var raw = this.dataset.raw || this.textContent.replace(/\s+/g,'');
    if(navigator.clipboard && window.isSecureContext){
      var h = $('keyHint');
      navigator.clipboard.writeText(raw).then(function(){
        h.textContent = 'Copied to clipboard.';
        setTimeout(function(){ h.textContent = 'Click to copy.'; }, 1800);
      }, function(){});
    }
  });

  /* ---- regenerate / disable: password + code, same form ---- */
  var tfaIntent = null;
  document.addEventListener('click', function(e){
    var b = e.target.closest && e.target.closest('[data-tfa-action]'); if(!b) return;
    e.preventDefault();
    tfaIntent = b.getAttribute('data-tfa-action');
    $('tfaActionTitle').textContent = tfaIntent === 'disable'
      ? 'Turn off two-step verification' : 'Generate new recovery codes';
    $('tfaActionLede').textContent = tfaIntent === 'disable'
      ? 'Your account goes back to being protected by your password alone, and your current recovery codes are deleted. Confirm both factors to continue.'
      : 'Your existing recovery codes stop working immediately and are replaced with a new set of ten.';
    $('btnTfaAction').textContent = tfaIntent === 'disable' ? 'Turn off two-step verification' : 'Generate new codes';
    $('btnTfaAction').classList.toggle('danger', tfaIntent === 'disable');
    $('btnTfaAction').classList.toggle('primary', tfaIntent !== 'disable');
    $('tfaPw').value = ''; $('tfaCode').value = '';
    setField('fldTfaPw','indTfaPw','hintTfaPw',null,'');
    setField('fldTfaCode','indTfaCode','hintTfaCode',null,'A code from your app, or one of your recovery codes.');
    showTfa('action');
  });
  $('btnTfaCancel').addEventListener('click', function(e){
    e.preventDefault();
    showTfa(DATA && DATA.twofa.backup_remaining <= 2 ? 'low' : 'on');
  });
  $('btnTfaAction').addEventListener('click', function(e){
    e.preventDefault();
    var pw = $('tfaPw').value, code = $('tfaCode').value.trim();
    if(!pw){ setField('fldTfaPw','indTfaPw','hintTfaPw','err','Enter your password.'); $('tfaPw').focus(); return; }
    if(!code){ setField('fldTfaCode','indTfaCode','hintTfaCode','err','Enter a code from your app, or a recovery code.'); $('tfaCode').focus(); return; }
    var btn = this;
    busyBtn(btn, true, 'Checking…');
    UCP.post(tfaIntent === 'disable' ? '2fa-disable.php' : '2fa-codes.php',
             {password: pw, code: code}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        var f = d.field === 'code' ? ['fldTfaCode','indTfaCode','hintTfaCode', $('tfaCode')]
                                   : ['fldTfaPw','indTfaPw','hintTfaPw', $('tfaPw')];
        var msg = d.locked && d.locked_for
          ? 'Too many attempts. Try again in '+UCP.fmtSecs(d.locked_for)+'.'
          : esc(d.error || 'That did not work.');
        setField(f[0], f[1], f[2], 'err', msg);
        f[3].focus(); f[3].select && f[3].select();
        return;
      }
      if(tfaIntent === 'disable'){
        flash('ok', 'Two-step verification is off. Your recovery codes have been deleted.');
        reload();
      } else {
        showCodes(d.backup_codes || [], false);
      }
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldTfaPw','indTfaPw','hintTfaPw','err','Could not reach the server. Try again.');
    });
  });
  $('tfaCode').addEventListener('input', function(){
    var v = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
    this.value = /^\d*$/.test(v) ? v.slice(0,6) : v.slice(0,12).replace(/(.{4})(?=.)/g,'$1-');
    setField('fldTfaCode','indTfaCode','hintTfaCode',null,'A code from your app, or one of your recovery codes.');
  });
  $('tfaPw').addEventListener('input', function(){ setField('fldTfaPw','indTfaPw','hintTfaPw',null,''); });

  /* =====================================================================
     SETTINGS — UCP name
     Live availability against api/check.php, which is the same endpoint the
     sign-up form uses, so the two can never disagree about what is free.
     ===================================================================== */
  var nameTimer = null, nameOK = false;
  $('newName').addEventListener('input', function(){
    var v = this.value.trim();
    nameOK = false;
    clearTimeout(nameTimer);

    if(v === ''){
      setField('fldName','indName','hintName',null,'3–20 characters. Letters, numbers and underscores.');
      return;
    }
    if(DATA && v.toLowerCase() === DATA.name.toLowerCase()){
      setField('fldName','indName','hintName','err','That is already your name.'); return;
    }
    if(v.length < 3){ setField('fldName','indName','hintName','err','Too short — 3 characters minimum.'); return; }
    if(v.length > 20){ setField('fldName','indName','hintName','err','Too long — 20 characters maximum.'); return; }
    if(!/^[A-Za-z0-9_]+$/.test(v)){
      setField('fldName','indName','hintName','err','Letters, numbers and underscores only — no spaces or punctuation.');
      return;
    }

    setField('fldName','indName','hintName','busy','Checking availability…');
    nameTimer = setTimeout(function(){
      UCP.get('check.php?username=' + encodeURIComponent(v)).then(function(d){
        if($('newName').value.trim() !== v) return;      // they kept typing
        if(!d || d.ok !== true){
          setField('fldName','indName','hintName',null,'We\'ll check this when you submit.');
          return;
        }
        if(d.available){
          nameOK = true;
          setField('fldName','indName','hintName','ok','“'+esc(v)+'” is available.');
        } else {
          setField('fldName','indName','hintName','err',
            d.reason === 'reserved' ? 'That name is reserved and can\'t be used.'
                                    : 'That name is already taken.');
        }
      });
    }, 420);
  });

  $('btnName').addEventListener('click', function(e){
    e.preventDefault();
    var name = $('newName').value.trim(), pw = $('namePw').value;
    if(!name){ setField('fldName','indName','hintName','err','Enter the new name.'); $('newName').focus(); return; }
    if(!pw){ setField('fldNamePw','indNamePw','hintNamePw','err','Enter your password to confirm this change.'); $('namePw').focus(); return; }
    var btn = this;
    busyBtn(btn, true, 'Changing…');
    UCP.post('settings-name.php', {username:name, password:pw}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        var f = d.field === 'username' ? ['fldName','indName','hintName', $('newName')]
                                       : ['fldNamePw','indNamePw','hintNamePw', $('namePw')];
        var msg = d.locked && d.locked_for
          ? 'Too many attempts. Try again in '+UCP.fmtSecs(d.locked_for)+'.'
          : esc(d.error || 'That did not work.');
        setField(f[0], f[1], f[2], 'err', msg);
        f[3].focus(); f[3].select && f[3].select();
        return;
      }
      closeExpands();
      flash('ok', 'Your UCP name is now <b>'+esc(d.name)+'</b>. It was <b>'+esc(d.previous)+'</b>.');
      try{ localStorage.setItem('bs-last-user', d.name); }catch(err){}
      reload();
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldNamePw','indNamePw','hintNamePw','err','Could not reach the server. Try again.');
    });
  });
  $('namePw').addEventListener('input', function(){ setField('fldNamePw','indNamePw','hintNamePw',null,''); });

  /* ---------------------------------------------------------- email change */
  $('newEmail').addEventListener('input', function(){
    var v = this.value.trim();
    if(v === ''){
      setField('fldEmail','indEmail','hintEmail',null,'We check the address is usable when you submit.');
      return;
    }
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)){
      setField('fldEmail','indEmail','hintEmail','err','That doesn\'t look like an email address.');
      return;
    }
    /* Deliberately not checked live. api/check.php refuses to say whether an
       address already has an account — answering hands anyone a free "does
       this person play here?" lookup and undoes the same non-disclosure in
       reset.php. A real collision comes back on submit. */
    setField('fldEmail','indEmail','hintEmail','ok','Looks right. We confirm it is free when you submit.');
  });

  $('btnEmail').addEventListener('click', function(e){
    e.preventDefault();
    var email = $('newEmail').value.trim(), pw = $('emailPw').value;
    if(!email){ setField('fldEmail','indEmail','hintEmail','err','Enter the new address.'); $('newEmail').focus(); return; }
    if(!pw){ setField('fldEmailPw','indEmailPw','hintEmailPw','err','Enter your password to confirm this change.'); $('emailPw').focus(); return; }
    var btn = this;
    busyBtn(btn, true, 'Sending…');
    UCP.post('settings-email.php', {email:email, password:pw}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        var f = d.field === 'email' ? ['fldEmail','indEmail','hintEmail', $('newEmail')]
                                    : ['fldEmailPw','indEmailPw','hintEmailPw', $('emailPw')];
        var msg = d.locked && d.locked_for
          ? 'Too many attempts. Try again in '+UCP.fmtSecs(d.locked_for)+'.'
          : esc(d.error || 'That did not work.');
        setField(f[0], f[1], f[2], 'err', msg);
        f[3].focus(); f[3].select && f[3].select();
        return;
      }
      closeExpands();
      flash('ok', esc(d.message));
      reload();
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldEmailPw','indEmailPw','hintEmailPw','err','Could not reach the server. Try again.');
    });
  });
  $('emailPw').addEventListener('input', function(){ setField('fldEmailPw','indEmailPw','hintEmailPw',null,''); });

  /* ------------------------------------------------------- password change */
  var np=$('np'), np2=$('np2'), meter=$('meter');
  function pwSync(){
    var v=np.value, len=v.length>=8, upp=/[A-Z]/.test(v), num=/[0-9]/.test(v);
    $('rLen').classList.toggle('met',len);
    $('rUpp').classList.toggle('met',upp);
    $('rNum').classList.toggle('met',num);
    var score = 0;
    if(len)score++; if(upp)score++; if(num)score++;
    if(v.length>=12 || /[^A-Za-z0-9]/.test(v))score++;
    if(!v)score=0;
    meter.className = 'meter' + (score ? ' s'+score : '');

    var strong = len && upp && num;
    $('fldNp').classList.remove('ok','err');
    $('indNp').innerHTML = '';
    if(v){
      $('fldNp').classList.add(strong ? 'ok' : 'err');
      $('indNp').innerHTML = strong ? ICO.ok : ICO.err;
    }
    if(!np2.value) setField('fldNp2','indNp2','matchHint',null,'');
    else if(np2.value===v) setField('fldNp2','indNp2','matchHint','ok','Passwords match.');
    else setField('fldNp2','indNp2','matchHint','err',"Passwords don't match.");
  }
  np.addEventListener('input', pwSync);
  np2.addEventListener('input', pwSync);
  $('curPw').addEventListener('input', function(){ setField('fldCurPw','indCurPw','hintCurPw',null,''); });

  $('btnPassword').addEventListener('click', function(e){
    e.preventDefault();
    var cur=$('curPw').value, a=np.value, b=np2.value;
    if(!cur){ setField('fldCurPw','indCurPw','hintCurPw','err','Enter your current password.'); $('curPw').focus(); return; }
    if(a.length<8 || !/[A-Z]/.test(a) || !/[0-9]/.test(a)){
      setField('fldNp','indNp','matchHint','err','Password needs 8+ characters, an uppercase letter and a number.');
      np.focus(); return;
    }
    if(a !== b){ setField('fldNp2','indNp2','matchHint','err',"Passwords don't match."); np2.focus(); return; }
    var btn = this;
    busyBtn(btn, true, 'Updating…');
    UCP.post('settings-password.php', {current:cur, password:a}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        var msg = d.locked && d.locked_for
          ? 'Too many attempts. Try again in '+UCP.fmtSecs(d.locked_for)+'.'
          : esc(d.error || 'That did not work.');
        /* The server reports both the current-password failure and the new
           -password rules under field:"password"; the wording tells them
           apart, so put a rules failure on the new field and the rest on the
           current one. */
        if(/8\+ characters|too long|already your password/i.test(d.error||'')){
          setField('fldNp','indNp','matchHint','err', msg); np.focus();
        } else {
          setField('fldCurPw','indCurPw','hintCurPw','err', msg); $('curPw').select();
        }
        return;
      }
      closeExpands();
      np.value=''; np2.value=''; $('curPw').value=''; pwSync();
      flash('ok', esc(d.message));
      reload();
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldCurPw','indCurPw','hintCurPw','err','Could not reach the server. Try again.');
    });
  });

  /* ------------------------------------------------- sign out everywhere */
  $('btnSignout').addEventListener('click', function(e){
    e.preventDefault();
    var pw = $('signoutPw').value;
    if(!pw){ setField('fldSignoutPw','indSignoutPw','hintSignoutPw','err','Enter your password to confirm.'); $('signoutPw').focus(); return; }
    var btn = this;
    busyBtn(btn, true, 'Signing out…');
    UCP.post('settings-signout.php', {password: pw}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(!d.ok){
        var msg = d.locked && d.locked_for
          ? 'Too many attempts. Try again in '+UCP.fmtSecs(d.locked_for)+'.'
          : esc(d.error || 'That did not work.');
        setField('fldSignoutPw','indSignoutPw','hintSignoutPw','err', msg);
        $('signoutPw').select(); return;
      }
      closeExpands();
      $('signoutPw').value = '';
      flash('ok', esc(d.message));
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldSignoutPw','indSignoutPw','hintSignoutPw','err','Could not reach the server. Try again.');
    });
  });
  $('signoutPw').addEventListener('input', function(){ setField('fldSignoutPw','indSignoutPw','hintSignoutPw',null,''); });

  /* ------------------------------------------------------------- delete */
  var delAck=$('delAck'), delConfirm=$('delConfirm'), delPw=$('delPw');
  function delSync(){
    delConfirm.setAttribute('aria-disabled',
      (delAck.classList.contains('on') && delPw.value.length > 0) ? 'false' : 'true');
  }
  delAck.addEventListener('click', function(){ this.classList.toggle('on'); delSync(); });
  delPw.addEventListener('input', function(){ setField('fldDelPw','indDelPw','hintDelPw',null,''); delSync(); });
  delConfirm.addEventListener('click', function(e){
    e.preventDefault();
    if(this.getAttribute('aria-disabled') === 'true') return;
    var btn = this;
    busyBtn(btn, true, 'Deleting…');
    UCP.post('settings-delete.php', {password: delPw.value}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var d = res.data || {};
      if(d.ok && d.deleted){ window.location.replace(d.redirect || '/login'); return; }
      setField('fldDelPw','indDelPw','hintDelPw','err', esc(d.error || 'That did not work.'));
    }).catch(function(){
      busyBtn(btn, false);
      setField('fldDelPw','indDelPw','hintDelPw','err','Could not reach the server. Try again.');
    });
  });

  /* =====================================================================
     LOAD
     ===================================================================== */
  function reload(){
    return UCP.get('profile.php').then(function(d){
      if(!d || d.ok !== true){
        if(d && d.authenticated === false){ window.location.replace(LOGIN); return; }
        flash('err', 'Couldn\'t load your account. Refresh the page, and tell staff if it keeps happening.');
        return;
      }
      render(d);
    }).then(reveal, reveal);
  }

  /* Reveals the page after the first answer from the server — see the
     .content rule in the stylesheet. Safe to call repeatedly. */
  function reveal(){ document.body.classList.add('ready'); }

  /* If the server never answers, show the page anyway rather than leaving
     someone staring at an empty frame with no way to tell it has failed. */
  setTimeout(reveal, 4000);

  /* Link Discord — a full page navigation, not a fetch. The whole point is
     to leave the site and come back, which a background request can't do. */
  $('discordBtn').addEventListener('click', function(e){
    e.preventDefault();
    window.location.href = '/api/discord-link.php';
  });

  $('discordUnlink').addEventListener('click', function(e){
    e.preventDefault();
    var btn = this;
    busyBtn(btn, true, 'Unlinking…');
    UCP.post('discord-unlink.php', {}).then(function(res){
      busyBtn(btn, false);
      if(bounceIfSignedOut(res)) return;
      var r = res.data || {};
      if(!r.ok){ flash('err', esc(r.error || 'That did not work.')); return; }
      flash('ok', esc(r.message));
      reload();
    }).catch(function(){
      busyBtn(btn, false);
      flash('err', 'Could not reach the server. Try again.');
    });
  });

  /* Messages carried back from the email-confirmation link. */
  (function(){
    var q = new URLSearchParams(window.location.search).get('email');
    if(!q) return;
    var msgs = {
      changed: ['ok',   'Your email address has been changed. We\'ve told the old address too.'],
      expired: ['warn', 'That confirmation link had expired — they last two hours. Start the change again.'],
      used:    ['warn', 'That confirmation link has already been used, or the change was cancelled.'],
      taken:   ['err',  'That address was claimed by another account while the link sat unopened. Try a different one.'],
      invalid: ['err',  'That confirmation link was not valid.']
    };
    var m = msgs[q];
    if(m) flash(m[0], m[1]);
    if(history.replaceState) history.replaceState(null, '', '/profile#settings');
    selectTab('settings', false);
  })();

  /* …and from the Discord round trip. */
  (function(){
    var q = new URLSearchParams(window.location.search).get('discord');
    if(!q) return;
    var msgs = {
      linked:      ['ok',   'Your Discord account is linked and verified.'],
      already:     ['info', 'A Discord account is already linked. Unlink it first to attach a different one.'],
      denied:      ['warn', 'Discord linking was cancelled — nothing was changed.'],
      taken:       ['err',  'That Discord account is already linked to another UCP. If it is yours, unlink it there first, or open a report.'],
      state:       ['warn', 'That linking attempt expired or didn\'t start here. Press Link Discord again.'],
      failed:      ['err',  'Discord didn\'t complete the link. Try again, and tell staff if it keeps failing.'],
      unavailable: ['warn', 'Discord linking isn\'t switched on yet.']
    };
    var m = msgs[q];
    if(m) flash(m[0], m[1]);
    if(history.replaceState) history.replaceState(null, '', '/profile');
  })();

  reload();
})();
</script>
</body>
</html>
