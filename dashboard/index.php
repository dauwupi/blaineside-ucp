<?php
/**
 * Dashboard.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'BlaineSide — UCP';
$PAGE_HEADING = 'Dashboard';
$PAGE_HEAD = <<<'HTML'
<style>
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
    --danger:#c1553f;
    --ok:#7fa05a;
    --warn:#e2b65c;
    --sidebar-w:256px;
    --header-h:66px;
    /* content sits on a slightly different tone so the header shell reads as separate */
    --content-bg:#100f0e;
  }
  *{box-sizing:border-box;margin:0;padding:0}
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
  ::-webkit-scrollbar{width:9px}
  ::-webkit-scrollbar-track{background:transparent}
  ::-webkit-scrollbar-thumb{background:var(--charcoal-4);border-radius:6px}

  /* ================= SIDEBAR ================= */
  /* The sidebar column stretches the full document height (flex stretch),
     while its inner content stays pinned to the viewport as you scroll. */
  .sidebar{
    width:var(--sidebar-w);flex:none;position:relative;
    background:var(--charcoal-2);border-right:1px solid var(--border-soft);
    z-index:50;
  }
  .side-inner{position:sticky;top:0;height:100vh;display:flex;flex-direction:column;
    padding-bottom:66px} /* keep nav clear of the anchored footer */
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
  a.nav-item{text-decoration:none}
  .nav-item .chev{width:15px;height:15px;opacity:.5;transition:transform .2s;flex:none;stroke-width:2}
  .nav-group.open > .nav-item .chev{transform:rotate(90deg)}

  .sub{max-height:0;overflow:hidden;transition:max-height .26s ease;margin-left:9px;
    border-left:1px solid var(--border);padding-left:8px}
  .nav-group.open .sub{max-height:340px}
  .sub a{display:block;padding:9px 12px;border-radius:8px;font-size:13px;font-weight:500;
    color:var(--text-dim);transition:.13s;margin:1px 0}
  .sub a:hover{background:var(--charcoal-3);color:var(--parchment)}
  .sub a.on{color:var(--parchment)}
  .sub a.slot-empty{color:var(--text-dim);font-style:italic;cursor:default}
  .sub a.slot-empty:hover{background:transparent;color:var(--text-dim)}

  .nav-heading{font-size:10.5px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;
    color:var(--text-dim);padding:18px 12px 8px}

  .side-foot{position:absolute;left:0;right:0;bottom:0;background:var(--charcoal-2);
    padding:13px 20px 15px;border-top:1px solid var(--border-soft);
    display:flex;flex-direction:column;gap:5px}
  .foot-line{font-size:11px;color:var(--text-dim);font-variant-numeric:tabular-nums;
    display:flex;align-items:center;gap:5px;flex-wrap:wrap;line-height:1.5}
  .foot-line .fv{color:var(--text-faint);font-weight:600}
  .foot-line .st{display:inline-flex;align-items:center;gap:6px}
  .foot-line .st .d{width:6px;height:6px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}

  /* ================= MAIN ================= */
  /* Custom warm-brown background — no images, pure CSS depth.
     Base is a diagonal warm gradient (dark amber-brown → near-black),
     with layered glow zones and a faint contour-line texture. */
  .main{flex:1;min-width:0;display:flex;flex-direction:column;position:relative;
    background:transparent}

  /* ============================================================
     BACKDROP — the sign-in page's scene, carried through the UCP.

     Four fixed layers: the Sandy Shores photo, the time-of-day tint,
     a scrim that buys back the contrast the photo costs, and the
     diagonal hairlines from the sign-in page.

     The tint is driven by assets/js/ucp.js, which looks for `.stage`
     and swaps a tod-* class onto it — the same code the sign-in page
     runs, so the two can't drift apart.
     ============================================================ */
  .bg-stage{position:fixed;inset:0;top:var(--header-h);left:var(--sidebar-w);z-index:0;pointer-events:none;
    overflow:hidden;background:#0b0a08}
  .bg-stage .scene{position:absolute;inset:0;
    background:url('/assets/img/bg-sandy.jpg') center/cover no-repeat;
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

  .topbar, .content{position:relative;z-index:1}

  /* ---- HEADER SHELL (persistent across every page) ---- */
  .topbar{height:var(--header-h);flex:none;display:flex;align-items:center;gap:16px;
    padding:0 26px;background:var(--charcoal-2);
    border-bottom:1px solid var(--border);
    box-shadow:0 1px 0 rgba(0,0,0,.4), 0 6px 18px -12px rgba(0,0,0,.7);
    position:sticky;top:0;z-index:45}
  .page-title h1{font-size:16px;font-weight:700;letter-spacing:-.01em}
  .topbar .spacer{flex:1}

  .searchbox{display:flex;align-items:center;gap:9px;height:38px;padding:0 14px;width:280px;
    background:var(--charcoal);border:1px solid var(--border);border-radius:10px;color:var(--text-dim)}
  .searchbox svg{width:15px;height:15px;flex:none;stroke-width:2}
  .searchbox input{background:none;border:none;outline:none;color:var(--parchment);font-family:inherit;
    font-size:13.5px;width:100%}
  .searchbox input::placeholder{color:var(--text-dim)}

  .icon-btn{width:38px;height:38px;flex:none;display:grid;place-items:center;border-radius:10px;
    background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint);cursor:pointer;
    transition:.14s;position:relative}
  .icon-btn:hover{color:var(--parchment);background:var(--charcoal-3)}
  .icon-btn svg{width:18px;height:18px;stroke-width:1.9}
  .icon-btn .dot{position:absolute;top:9px;right:10px;width:7px;height:7px;border-radius:50%;
    background:var(--danger);border:2px solid var(--charcoal-2)}

  .divider{width:1px;height:30px;background:var(--border);flex:none}

  /* account — no avatar, name + role stacked */
  .account{position:relative}
  .account-btn{display:flex;align-items:center;gap:12px;padding:6px 12px;border-radius:10px;
    background:var(--charcoal);border:1px solid var(--border);cursor:pointer;transition:.14s;min-width:170px}
  .account-btn:hover{background:var(--charcoal-3)}
  .account-meta{display:flex;flex-direction:column;line-height:1.3;flex:1;min-width:0;text-align:left}
  .account-meta .u{font-size:13.5px;font-weight:600;color:var(--parchment);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .account-meta .r{font-size:11px;color:var(--amber);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .account-meta .r.player{color:var(--text-dim)}
  .account-btn .caret{width:15px;height:15px;color:var(--text-dim);stroke-width:2;flex:none}
  .account-btn .acct-ico{display:none}

  /* ===== LOCKED =====
     What a locked account gets instead of a dashboard. Everything else is
     removed rather than disabled: a greyed-out sidebar invites clicking, and
     a page full of things that don't respond reads as broken software at
     exactly the moment somebody needs to trust what it says. */
  .lockpage{max-width:640px;margin:0 auto;padding:8px 0 40px}
  .lockhero{background:var(--charcoal-2);border:1px solid rgba(193,85,63,.34);border-radius:16px;
    padding:30px 30px 26px;box-shadow:0 24px 60px -28px rgba(0,0,0,.9)}
  .lockhero .mark{width:52px;height:52px;border-radius:15px;display:grid;place-items:center;
    background:rgba(193,85,63,.14);border:1px solid rgba(193,85,63,.3);color:#e0a99b;margin-bottom:18px}
  .lockhero .mark svg{width:24px;height:24px;stroke-width:1.9}
  .lockhero h1{font-size:23px;font-weight:700;letter-spacing:-.02em;margin-bottom:9px}
  .lockhero .lede{font-size:14px;color:var(--text-faint);line-height:1.65}
  .lockwhy{margin-top:20px;padding:15px 16px;border-radius:12px;background:var(--charcoal);
    border:1px solid var(--border)}
  .lockwhy .k{font-size:9.5px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
    color:var(--text-dim);margin-bottom:8px}
  .lockwhy .v{font-size:14px;color:var(--parchment);line-height:1.6}
  .lockwhy .m{font-size:12px;color:var(--text-dim);margin-top:10px}
  .locksteps{margin-top:22px;padding-top:20px;border-top:1px solid var(--border-soft)}
  .locksteps h3{font-size:12.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
    color:var(--text-dim);margin-bottom:13px}
  .lockstep{display:flex;gap:12px;padding:9px 0;font-size:13.5px;color:var(--text-faint);line-height:1.55}
  .lockstep .n{width:22px;height:22px;flex:none;border-radius:7px;display:grid;place-items:center;
    background:var(--charcoal-3);border:1px solid var(--border);font-size:11px;font-weight:800;
    color:var(--text-dim)}
  .lockstep b{color:var(--parchment);font-weight:600}
  .lockacts{display:flex;gap:10px;margin-top:24px;flex-wrap:wrap}
  /* This page has no .btn of its own — every other button on the dashboard
     is a bespoke component — so the lock screen brings its own. */
  .lockacts .btn{flex:1;min-width:150px;display:inline-flex;align-items:center;justify-content:center;
    gap:8px;padding:12px 18px;border-radius:11px;font-family:inherit;font-size:13.5px;font-weight:700;
    cursor:pointer;border:1px solid var(--border);background:var(--charcoal-2);color:var(--parchment);
    transition:.15s}
  .lockacts .btn:hover:not(:disabled){background:var(--charcoal-3);border-color:var(--charcoal-4)}
  /* Flat. This was a gradient plus a lift plus a glow on hover, three things
     at once on a page whose only job is to be read calmly. */
  .lockacts .btn.primary{background:var(--gold);border-color:var(--gold);color:#1a1206;
    font-weight:700}
  .lockacts .btn.primary:hover{background:#e8c06a;border-color:#e8c06a;color:#1a1206}
  .lockacts .btn:disabled{opacity:.42;cursor:not-allowed}
  .lockfoot{text-align:center;font-size:12px;color:var(--text-dim);margin-top:20px;line-height:1.6}

  .account-menu{position:absolute;right:0;top:calc(100% + 10px);width:230px;
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:13px;
    box-shadow:0 24px 50px -18px rgba(0,0,0,.8);padding:8px;z-index:60}
  .account-menu .mhead{padding:8px 10px 12px;border-bottom:1px solid var(--border-soft);margin-bottom:6px}
  .account-menu .mhead .n{font-size:14px;font-weight:700}
  .account-menu .mhead .rr{font-size:12px;color:var(--amber);font-weight:600;margin-top:1px}
  .menu-item{display:flex;align-items:center;gap:12px;padding:10px;border-radius:9px;
    font-size:13.5px;font-weight:500;color:var(--text-faint);cursor:pointer;transition:.13s}
  .menu-item svg{width:16px;height:16px;stroke-width:1.9}
  .menu-item:hover{background:var(--charcoal-3);color:var(--parchment)}
  .menu-item.danger{color:#d98a78}
  .menu-item.danger:hover{background:rgba(193,85,63,.12);color:#eab3a6}
  .menu-sep{height:1px;background:var(--border-soft);margin:6px 4px}

  /* ---- CONTENT ---- */
  .content{padding:28px 30px 40px;max-width:1260px;width:100%;margin:0 auto}
  .page{display:flex;flex-direction:column;gap:22px}
  .ann{margin-bottom:4px}

  .hello h2{font-size:23px;font-weight:700;letter-spacing:-.02em;margin-bottom:3px}
  .hello h2 b{color:var(--gold);font-weight:700}
  .hello p{font-size:14px;color:var(--text-faint)}

  /* STAT STRIP — 5 metrics, no percentages */
  .stats{display:grid;grid-template-columns:repeat(5,1fr);
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;overflow:hidden}
  .stat{padding:20px 22px;position:relative}
  .stat + .stat{border-left:1px solid var(--border-soft)}
  .stat .lab{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;
    color:var(--text-faint);margin-bottom:12px}
  .stat .lab svg{width:15px;height:15px;color:var(--stone);stroke-width:1.9;flex:none}
  .stat .num{font-size:26px;font-weight:700;letter-spacing:-.02em;line-height:1;
    font-variant-numeric:tabular-nums;color:var(--parchment)}

  /* THIN CAPACITY BAR under stats */
  .capacity{display:flex;align-items:center;gap:18px;
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:12px;padding:14px 20px}
  .capacity .ct{display:flex;align-items:center;gap:9px;flex:none}
  .capacity .ct .d{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}
  .capacity .ct .t{font-size:12.5px;font-weight:600;color:var(--text-faint)}
  .capacity .pv{font-size:13.5px;font-weight:700;font-variant-numeric:tabular-nums;flex:none}
  .capacity .pv small{color:var(--text-faint);font-weight:600}
  .capacity .bar{flex:1;height:7px;border-radius:5px;background:var(--charcoal);overflow:hidden;border:1px solid var(--border-soft)}
  .capacity .bar > i{display:block;height:100%;border-radius:5px;background:linear-gradient(90deg,var(--amber),var(--gold))}
  .capacity .meta{font-size:12px;color:var(--text-dim);font-weight:500;flex:none;font-variant-numeric:tabular-nums}
  .capacity .meta b{color:var(--gold);font-weight:700}

  /* body grid — aligned columns, equal rhythm */
  .body-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:22px;align-items:stretch}
  .col{display:flex;flex-direction:column;gap:22px}

  .panel{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;overflow:hidden;
    display:flex;flex-direction:column}
  .panel-head{display:flex;align-items:center;justify-content:space-between;padding:15px 20px;
    border-bottom:1px solid var(--border-soft);flex:none}
  .panel-head h3{font-size:14px;font-weight:700;letter-spacing:-.01em}
  .panel-head .link{font-size:12.5px;font-weight:600;color:var(--text-faint);cursor:pointer}
  .panel-head .link:hover{color:var(--gold)}
  .panel-head .live{display:flex;align-items:center;gap:7px;font-size:11.5px;font-weight:600;color:var(--text-faint)}
  .panel-head .live .d{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}

  /* NEWS */
  .news-track{position:relative;flex:1;min-height:250px}
  .slide{position:absolute;inset:0;opacity:0;transition:opacity .55s;display:flex;align-items:flex-end}
  .slide.on{opacity:1}
  .slide .bg{position:absolute;inset:0}
  .slide .bg img{width:100%;height:100%;object-fit:cover;display:block}
  .slide.s1 .bg{background:linear-gradient(120deg,#3a2a16,#191510)}
  .slide.s2 .bg{background:linear-gradient(120deg,#2c2617,#181712)}
  .slide.s3 .bg{background:linear-gradient(120deg,#33211a,#191310)}
  .slide .bg::after{content:"";position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(12,11,10,.1),rgba(12,11,10,.55) 55%,rgba(12,11,10,.95))}
  /* Over a photograph the image is faded out behind the caption rather
     than merely darkened. The fade lives on .cap, so it starts exactly
     where the text does however tall the caption happens to be — a
     two-line title pushes the fade up with it. */
  .slide.has-img .bg::after{background:
    linear-gradient(180deg,rgba(10,9,8,.30) 0%,rgba(10,9,8,.08) 38%,rgba(10,9,8,.30) 100%)}
  .slide.has-img .cap{padding-top:54px;
    background:linear-gradient(to top,
      rgba(10,9,8,.97) 0%, rgba(10,9,8,.95) 48%, rgba(10,9,8,.78) 72%, rgba(10,9,8,0) 100%)}
  .slide .cap{position:relative;padding:24px;width:100%}
  .slide .tag{display:inline-block;font-size:10.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
    color:#1a1206;background:var(--gold);padding:3px 9px;border-radius:100px;margin-bottom:12px}
  .slide .tag.evt{background:var(--danger);color:#fff}
  .slide .tag.upd{background:var(--stone);color:#141210}
  .slide h4{font-size:19px;font-weight:700;letter-spacing:-.01em;margin-bottom:6px;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  /* Two lines, never more — a long description over a photo turns the slide
     into wallpaper with words on it. */
  .slide p{font-size:13px;line-height:1.55;color:#c9bea9;max-width:56ch;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .slide.has-img h4{text-shadow:0 1px 12px rgba(0,0,0,.55)}
  .slide.has-img p{color:#ddd3c2;text-shadow:0 1px 10px rgba(0,0,0,.5)}
  .slide.has-img .meta{color:#b6ab99}
  .slide .meta{font-size:11.5px;color:var(--text-faint);margin-top:10px;font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .slide.has-link{cursor:pointer}
  .slide.has-link .cap{transition:transform .55s ease, opacity .55s ease}
  .slide.has-link:hover h4{color:var(--gold)}
  .slide-link{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#1a1206;
    background:linear-gradient(145deg,var(--gold),var(--amber));padding:3px 9px;border-radius:100px}
  .slide-link svg{width:11px;height:11px}
  .slide.has-link:focus-visible{outline:2px solid var(--gold);outline-offset:-3px;border-radius:14px}
  .news-nav{display:flex;align-items:center;justify-content:center;gap:14px;
    padding:12px 20px;border-top:1px solid var(--border-soft);flex:none}
  .news-arrow{width:30px;height:30px;flex:none;display:grid;place-items:center;border-radius:8px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-faint);
    cursor:pointer;transition:.14s}
  .news-arrow:hover{color:var(--parchment);background:var(--charcoal-4);border-color:var(--charcoal-4)}
  .news-arrow svg{width:16px;height:16px}
  .news-dots{display:flex;gap:7px;align-items:center}
  .news-dots .d{width:7px;height:7px;border-radius:50%;background:var(--charcoal-4);cursor:pointer;transition:.22s}
  .news-dots .d.on{background:var(--gold);width:22px;border-radius:100px}

  /* SERVICE STATUS */
  .status-list{padding:7px}
  .status-row{display:flex;align-items:center;gap:13px;padding:13px 12px}
  .status-row + .status-row{border-top:1px solid var(--border-soft)}
  .status-row .led{width:9px;height:9px;border-radius:50%;flex:none}
  .status-row.up .led{background:var(--ok);box-shadow:0 0 7px var(--ok)}
  .status-row.deg .led{background:var(--warn);box-shadow:0 0 7px var(--warn)}
  .status-row.down .led{background:var(--danger);box-shadow:0 0 7px var(--danger)}
  .status-row .nm{font-size:13.5px;font-weight:600;flex:1}
  .status-row .val{font-size:12.5px;font-weight:600}
  .status-row.up .val{color:var(--ok)}
  .status-row.deg .val{color:var(--warn)}
  .status-row.down .val{color:var(--danger)}
  .status-row .sub{font-size:11px;color:var(--text-dim);min-width:70px;text-align:right;font-variant-numeric:tabular-nums}

  /* DISCORD — muted slate-blue to sit calmly in the warm palette */
  :root{--dc:#6b74a8;--dc-soft:#4c5379}
  .discord{position:relative;overflow:hidden}
  .discord::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,var(--dc-soft),var(--dc))}
  .discord-body{padding:22px;display:flex;flex-direction:column;flex:1}
  .discord-top{display:flex;align-items:center;gap:13px;margin-bottom:16px}
  .discord-mark{width:44px;height:44px;border-radius:11px;flex:none;display:grid;place-items:center;
    background:rgba(107,116,168,.16);border:1px solid rgba(107,116,168,.3)}
  .discord-mark svg{width:24px;height:24px;color:#a8afd4}
  .discord-top .nm{font-size:15px;font-weight:700}
  .discord-top .sub{font-size:12px;color:var(--text-faint);display:flex;align-items:center;gap:6px;margin-top:2px}
  .discord-top .sub .g{width:7px;height:7px;border-radius:50%;background:var(--ok)}
  .discord-stat{display:flex;align-items:baseline;gap:8px;margin-bottom:3px}
  .discord-stat .big{font-size:25px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
  .discord-stat .small{font-size:13px;color:var(--text-faint);font-weight:500}
  .discord-line{font-size:12.5px;color:var(--text-dim);margin-bottom:auto;padding-bottom:16px}
  .discord-btn{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;padding:12px;
    border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:none;
    background:rgba(107,116,168,.14);color:#b9bfdd;border:1px solid rgba(107,116,168,.32);transition:.15s}
  .discord-btn:hover{background:rgba(107,116,168,.22);color:#cdd2ea}
  .discord-btn svg{width:18px;height:18px}

  /* =====================================================================
     ANNOUNCEMENT STRIP

     Reads left to right like a filed notice: a colour stamp, then the
     type and when it was posted, then the headline, then the detail.
     Nothing is centred — centred text has no left edge to scan down, which
     is why the old one turned into a paragraph floating in a box.

     One --ann colour per type drives the rail, the stamp and the border,
     so adding a type later is one variable, not six rules.
     ===================================================================== */
  .ann{--ann:var(--gold);--ann-ink:#1a1206;
    display:flex;align-items:center;gap:15px;padding:13px 15px;position:relative;
    border:1px solid color-mix(in srgb, var(--ann) 34%, transparent);
    border-radius:13px;overflow:hidden;
    background:linear-gradient(90deg,
      color-mix(in srgb, var(--ann) 13%, transparent),
      color-mix(in srgb, var(--ann) 4%, transparent) 42%,
      transparent 78%), var(--charcoal-2)}
  .ann::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--ann)}
  .ann-stamp{flex:none;width:36px;height:36px;border-radius:10px;display:grid;place-items:center;
    background:var(--ann);color:var(--ann-ink);box-shadow:0 6px 16px -8px var(--ann)}
  .ann-stamp svg{width:18px;height:18px;stroke-width:2}
  .ann-main{flex:1;min-width:0}
  .ann-eyebrow{display:flex;align-items:center;gap:7px;font-size:10px;font-weight:800;
    letter-spacing:.14em;text-transform:uppercase;color:var(--ann);margin-bottom:3px}
  .ann-eyebrow .sep{opacity:.45}
  .ann-eyebrow .ago{color:var(--text-faint);font-weight:700;letter-spacing:.08em}
  .ann-head{font-size:14px;font-weight:700;color:var(--parchment);line-height:1.35;
    letter-spacing:-.01em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  /* The detail wraps onto a second line and stops there — long enough to
     say the thing, short enough that the strip keeps a fixed height. */
  .ann-detail{font-size:12.5px;color:var(--text-dim);line-height:1.5;margin-top:3px;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .ann-acts{flex:none;display:flex;align-items:center;gap:8px}
  .ann-link{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;
    padding:7px 12px;border-radius:100px;white-space:nowrap;
    color:var(--ann);border:1px solid color-mix(in srgb, var(--ann) 40%, transparent);
    background:color-mix(in srgb, var(--ann) 10%, transparent);transition:.16s}
  .ann-link:hover{background:var(--ann);color:var(--ann-ink)}
  .ann-link svg{width:12px;height:12px;stroke-width:2.4}
  .ann-x{flex:none;width:28px;height:28px;display:grid;place-items:center;border-radius:8px;
    background:transparent;border:none;color:var(--text-faint);cursor:pointer;transition:.14s}
  .ann-x:hover{background:rgba(255,255,255,.06);color:var(--parchment)}
  .ann-x svg{width:14px;height:14px;stroke-width:2.2}

  .ann.t-notice     {--ann:var(--gold);--ann-ink:#1a1206}
  .ann.t-maintenance{--ann:#7ea7d4;--ann-ink:#0b131d}
  .ann.t-warning    {--ann:var(--amber);--ann-ink:#1a1206}
  .ann.t-critical   {--ann:#d0644d;--ann-ink:#fff}
  .ann.t-success    {--ann:#8fb463;--ann-ink:#0f1408}

  @media (max-width:760px){
    .ann{align-items:flex-start;gap:12px;padding:12px 13px}
    .ann-head{white-space:normal;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
    .ann-detail{-webkit-line-clamp:3}
    .ann-acts{flex-direction:column;align-items:flex-end;gap:6px}
    .ann-link{padding:6px 10px}
  }

  .hello h2{font-size:23px;font-weight:700;letter-spacing:-.02em;margin-bottom:3px}
  .hello h2 b{color:var(--gold);font-weight:700}
  .hello p{font-size:14px;color:var(--text-faint)}

  /* STAT STRIP — 5 metrics, no percentages */
  .stats{display:grid;grid-template-columns:repeat(5,1fr);
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;overflow:hidden}
  .stat{padding:20px 22px;position:relative}
  .stat + .stat{border-left:1px solid var(--border-soft)}
  .stat .lab{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;
    color:var(--text-faint);margin-bottom:12px}
  .stat .lab svg{width:15px;height:15px;color:var(--stone);stroke-width:1.9;flex:none}
  .stat .num{font-size:26px;font-weight:700;letter-spacing:-.02em;line-height:1;
    font-variant-numeric:tabular-nums;color:var(--parchment)}

  /* THIN CAPACITY BAR under stats */
  .capacity{display:flex;align-items:center;gap:18px;
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:12px;padding:14px 20px}
  .capacity .ct{display:flex;align-items:center;gap:9px;flex:none}
  .capacity .ct .d{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}
  .capacity .ct .t{font-size:12.5px;font-weight:600;color:var(--text-faint)}
  .capacity .pv{font-size:13.5px;font-weight:700;font-variant-numeric:tabular-nums;flex:none}
  .capacity .pv small{color:var(--text-faint);font-weight:600}
  .capacity .bar{flex:1;height:7px;border-radius:5px;background:var(--charcoal);overflow:hidden;border:1px solid var(--border-soft)}
  .capacity .bar > i{display:block;height:100%;border-radius:5px;background:linear-gradient(90deg,var(--amber),var(--gold))}
  .capacity .meta{font-size:12px;color:var(--text-dim);font-weight:500;flex:none;font-variant-numeric:tabular-nums}
  .capacity .meta b{color:var(--gold);font-weight:700}

  /* body grid — aligned columns, equal rhythm */
  .body-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:22px;align-items:stretch}
  .col{display:flex;flex-direction:column;gap:22px}

  .panel{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;overflow:hidden;
    display:flex;flex-direction:column}
  .panel-head{display:flex;align-items:center;justify-content:space-between;padding:15px 20px;
    border-bottom:1px solid var(--border-soft);flex:none}
  .panel-head h3{font-size:14px;font-weight:700;letter-spacing:-.01em}
  .panel-head .link{font-size:12.5px;font-weight:600;color:var(--text-faint);cursor:pointer}
  .panel-head .link:hover{color:var(--gold)}
  .panel-head .live{display:flex;align-items:center;gap:7px;font-size:11.5px;font-weight:600;color:var(--text-faint)}
  .panel-head .live .d{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}

  /* NEWS */
  .news-track{position:relative;flex:1;min-height:250px}
  .slide{position:absolute;inset:0;opacity:0;transition:opacity .55s;display:flex;align-items:flex-end}
  .slide.on{opacity:1}
  .slide .bg{position:absolute;inset:0}
  .slide .bg img{width:100%;height:100%;object-fit:cover;display:block}
  .slide.s1 .bg{background:linear-gradient(120deg,#3a2a16,#191510)}
  .slide.s2 .bg{background:linear-gradient(120deg,#2c2617,#181712)}
  .slide.s3 .bg{background:linear-gradient(120deg,#33211a,#191310)}
  .slide .bg::after{content:"";position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(12,11,10,.1),rgba(12,11,10,.55) 55%,rgba(12,11,10,.95))}
  /* Over a photograph the image is faded out behind the caption rather
     than merely darkened. The fade lives on .cap, so it starts exactly
     where the text does however tall the caption happens to be — a
     two-line title pushes the fade up with it. */
  .slide.has-img .bg::after{background:
    linear-gradient(180deg,rgba(10,9,8,.30) 0%,rgba(10,9,8,.08) 38%,rgba(10,9,8,.30) 100%)}
  .slide.has-img .cap{padding-top:54px;
    background:linear-gradient(to top,
      rgba(10,9,8,.97) 0%, rgba(10,9,8,.95) 48%, rgba(10,9,8,.78) 72%, rgba(10,9,8,0) 100%)}
  .slide .cap{position:relative;padding:24px;width:100%}
  .slide .tag{display:inline-block;font-size:10.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
    color:#1a1206;background:var(--gold);padding:3px 9px;border-radius:100px;margin-bottom:12px}
  .slide .tag.evt{background:var(--danger);color:#fff}
  .slide .tag.upd{background:var(--stone);color:#141210}
  .slide h4{font-size:19px;font-weight:700;letter-spacing:-.01em;margin-bottom:6px;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  /* Two lines, never more — a long description over a photo turns the slide
     into wallpaper with words on it. */
  .slide p{font-size:13px;line-height:1.55;color:#c9bea9;max-width:56ch;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .slide.has-img h4{text-shadow:0 1px 12px rgba(0,0,0,.55)}
  .slide.has-img p{color:#ddd3c2;text-shadow:0 1px 10px rgba(0,0,0,.5)}
  .slide.has-img .meta{color:#b6ab99}
  .slide .meta{font-size:11.5px;color:var(--text-faint);margin-top:10px;font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .slide.has-link{cursor:pointer}
  .slide.has-link .cap{transition:transform .55s ease, opacity .55s ease}
  .slide.has-link:hover h4{color:var(--gold)}
  .slide-link{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#1a1206;
    background:linear-gradient(145deg,var(--gold),var(--amber));padding:3px 9px;border-radius:100px}
  .slide-link svg{width:11px;height:11px}
  .slide.has-link:focus-visible{outline:2px solid var(--gold);outline-offset:-3px;border-radius:14px}
  .news-nav{display:flex;align-items:center;justify-content:center;gap:14px;
    padding:12px 20px;border-top:1px solid var(--border-soft);flex:none}
  .news-arrow{width:30px;height:30px;flex:none;display:grid;place-items:center;border-radius:8px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-faint);
    cursor:pointer;transition:.14s}
  .news-arrow:hover{color:var(--parchment);background:var(--charcoal-4);border-color:var(--charcoal-4)}
  .news-arrow svg{width:16px;height:16px}
  .news-dots{display:flex;gap:7px;align-items:center}
  .news-dots .d{width:7px;height:7px;border-radius:50%;background:var(--charcoal-4);cursor:pointer;transition:.22s}
  .news-dots .d.on{background:var(--gold);width:22px;border-radius:100px}

  /* SERVICE STATUS */
  .status-list{padding:7px}
  .status-row{display:flex;align-items:center;gap:13px;padding:13px 12px}
  .status-row + .status-row{border-top:1px solid var(--border-soft)}
  .status-row .led{width:9px;height:9px;border-radius:50%;flex:none}
  .status-row.up .led{background:var(--ok);box-shadow:0 0 7px var(--ok)}
  .status-row.deg .led{background:var(--warn);box-shadow:0 0 7px var(--warn)}
  .status-row.down .led{background:var(--danger);box-shadow:0 0 7px var(--danger)}
  .status-row .nm{font-size:13.5px;font-weight:600;flex:1}
  .status-row .val{font-size:12.5px;font-weight:600}
  .status-row.up .val{color:var(--ok)}
  .status-row.deg .val{color:var(--warn)}
  .status-row.down .val{color:var(--danger)}
  .status-row .sub{font-size:11px;color:var(--text-dim);min-width:70px;text-align:right;font-variant-numeric:tabular-nums}

  /* DISCORD — muted slate-blue to sit calmly in the warm palette */
  :root{--dc:#6b74a8;--dc-soft:#4c5379}
  .discord{position:relative;overflow:hidden}
  .discord::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,var(--dc-soft),var(--dc))}
  .discord-body{padding:22px;display:flex;flex-direction:column;flex:1}
  .discord-top{display:flex;align-items:center;gap:13px;margin-bottom:16px}
  .discord-mark{width:44px;height:44px;border-radius:11px;flex:none;display:grid;place-items:center;
    background:rgba(107,116,168,.16);border:1px solid rgba(107,116,168,.3)}
  .discord-mark svg{width:24px;height:24px;color:#a8afd4}
  .discord-top .nm{font-size:15px;font-weight:700}
  .discord-top .sub{font-size:12px;color:var(--text-faint);display:flex;align-items:center;gap:6px;margin-top:2px}
  .discord-top .sub .g{width:7px;height:7px;border-radius:50%;background:var(--ok)}
  .discord-stat{display:flex;align-items:baseline;gap:8px;margin-bottom:3px}
  .discord-stat .big{font-size:25px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
  .discord-stat .small{font-size:13px;color:var(--text-faint);font-weight:500}
  .discord-line{font-size:12.5px;color:var(--text-dim);margin-bottom:auto;padding-bottom:16px}
  .discord-btn{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;padding:12px;
    border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:none;
    background:rgba(107,116,168,.14);color:#b9bfdd;border:1px solid rgba(107,116,168,.32);transition:.15s}
  .discord-btn:hover{background:rgba(107,116,168,.22);color:#cdd2ea}
  .discord-btn svg{width:18px;height:18px}

  /* ANNOUNCEMENT BANNER — full-width, centered */

  /* The bell's panel is styled by assets/js/ucp.js, and the full list has
     its own page. Both used to be here — markup, styles and twelve
     hardcoded rows — which is why the panel and the page could disagree
     about what was unread. */

  /* mobile-only controls — hidden on desktop */
  .hamburger{display:none}

  /* ===== LOADING SKELETONS ===== */
  @keyframes shimmer{0%{background-position:-400px 0}100%{background-position:400px 0}}
  .skeleton{position:relative;color:transparent!important;border-radius:6px;
    background:linear-gradient(90deg,var(--charcoal-3) 0px,#2c2823 120px,var(--charcoal-3) 240px);
    background-size:600px 100%;animation:shimmer 1.3s infinite linear;pointer-events:none;user-select:none}
  .skeleton *{visibility:hidden}
  .sk-line{height:12px;border-radius:6px;background:linear-gradient(90deg,var(--charcoal-3),#2c2823,var(--charcoal-3));
    background-size:600px 100%;animation:shimmer 1.3s infinite linear;margin:6px 0}
  body.loading .reveal{opacity:0}
  .reveal{opacity:1;transition:opacity .5s ease, transform .5s ease}

  /* ===== EMPTY STATES ===== */
  .empty{display:flex;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;padding:44px 24px;gap:12px}
  .empty .ei{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-dim)}
  .empty .ei svg{width:24px;height:24px;stroke-width:1.7}
  .empty h4{font-size:15px;font-weight:700;color:var(--text-faint)}
  .empty p{font-size:13px;color:var(--text-dim);max-width:280px;line-height:1.5}

  /* ===== MICRO-INTERACTIONS ===== */
  .stat{transition:transform .16s ease, background .16s ease}
  .stat:hover{background:var(--charcoal-3)}
  .panel{transition:border-color .18s ease}
  .qbtn,.discord-btn,.np-clear,.np-filter,.news-arrow,.icon-btn,.account-btn{will-change:transform}
  .discord-btn:active,.news-arrow:active,.icon-btn:active{transform:scale(.96)}
  .ni-tick{transition:transform .14s ease, background .14s, border-color .14s, color .14s}
  .ni-tick:active{transform:scale(.9)}
  @keyframes tickPop{0%{transform:scale(1)}45%{transform:scale(1.28)}100%{transform:scale(1)}}
  .ni-tick.just-read{animation:tickPop .34s ease}
  @keyframes rowFade{from{opacity:.3}to{opacity:1}}
  .np-row{animation:rowFade .3s ease}
  /* bulletin slide: gentle rise as it becomes active */
  .slide{transition:opacity .55s ease}
  .slide .cap{transition:transform .55s ease, opacity .55s ease}
  .slide:not(.on) .cap{transform:translateY(8px);opacity:0}
  .slide.on .cap{transform:translateY(0);opacity:1}
  @media (prefers-reduced-motion:reduce){
    *{animation-duration:.001ms!important;transition-duration:.001ms!important}
  }
  .search-mini{display:none}
  .scrim{display:none;position:fixed;inset:0;background:rgba(8,7,6,.6);
    backdrop-filter:blur(2px);z-index:48;opacity:0;transition:opacity .22s}
  .scrim.show{display:block;opacity:1}

  /* ---- TABLET: single column body, 3-up stats ---- */
  @media (max-width:1080px){
    .content{padding:24px 22px 36px}
    .body-grid{grid-template-columns:1fr}
    .stats{grid-template-columns:repeat(3,1fr)}
    .stat:nth-child(3n+1){border-left:none}
    .stat:nth-child(n+4){border-top:1px solid var(--border-soft)}
  }

  /* ---- PHONE: drawer sidebar + fully stacked content ---- */
  @media (max-width:760px){
    /* sidebar becomes an off-canvas drawer */
    .sidebar{position:fixed;left:0;top:0;height:100dvh;transform:translateX(-100%);
      transition:transform .26s ease}
    .side-inner{position:static;height:100dvh}
    body.nav-open .sidebar{transform:translateX(0);box-shadow:0 0 60px rgba(0,0,0,.6)}
    .hamburger{display:grid}

    /* header: hide full search, show icon; tighten spacing */
    .topbar{padding:0 14px;gap:10px}
    .searchbox{display:none}
    .search-mini{display:grid}
    .divider{display:none}
    .page-title h1{font-size:15px}

    /* account collapses to a compact icon chip (name+role hidden on phone) */
    .account-btn{min-width:0;padding:9px 11px;gap:6px}
    .account-meta{display:none}
    .account-btn .acct-ico{display:block;width:18px;height:18px;color:var(--text-faint)}
    .account-menu{width:220px;right:-4px}

    /* content stacks */
    .content{padding:18px 14px 32px}
    .page{gap:16px}
    .hello h2{font-size:20px}
    .hello p{font-size:13px}

    /* stats: 2-up grid, reset all borders cleanly */
    .stats{grid-template-columns:repeat(2,1fr)}
    .stat{padding:16px 16px}
    .stat + .stat{border-left:none}
    .stat{border-top:1px solid var(--border-soft);border-left:1px solid var(--border-soft)}
    .stat:nth-child(odd){border-left:none}
    .stat:nth-child(1),.stat:nth-child(2){border-top:none}
    .stat .num{font-size:22px}
    .stat .lab{font-size:11.5px;margin-bottom:9px}

    /* capacity: wrap the bar to its own line */
    .capacity{flex-wrap:wrap;gap:x;gap:10px 14px;padding:14px 16px}
    .capacity .bar{flex-basis:100%;order:5}
    .capacity .meta{margin-left:auto}

    /* panels full width, comfortable news height */
    .news-track{min-height:210px}
    .slide h4{font-size:17px}
    .slide .cap{padding:20px}
  }

  @media (max-width:380px){
    .stats{grid-template-columns:1fr}
    .stat{border-left:none!important}
    .stat:nth-child(n+2){border-top:1px solid var(--border-soft)}
    .stat:nth-child(1){border-top:none}
  }

  /* Past appeals on the lock screen.
     A locked player's second question, after "why", is "what happened last
     time" — and the only alternative to answering it here is them asking
     staff to go and look it up. */
  .lockpast{margin-top:26px;padding-top:22px;border-top:1px solid var(--rule)}
  .lockpast h3{font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
    color:var(--text-dim);margin-bottom:12px}
  .lockpast .lp{display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:10px;
    background:var(--charcoal);border:1px solid var(--border);transition:.14s;
    text-decoration:none}
  .lockpast .lp + .lp{margin-top:7px}
  .lockpast .lp:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  .lockpast .lpst{flex:none;font-size:9.5px;font-weight:800;letter-spacing:.08em;
    text-transform:uppercase;padding:3px 8px;border-radius:100px;line-height:1.5}
  .lockpast .lpst.accepted{color:#9fae8d;background:rgba(127,160,90,.12);
    border:1px solid rgba(127,160,90,.3)}
  .lockpast .lpst.rejected{color:#d29b8d;background:rgba(193,85,63,.12);
    border:1px solid rgba(193,85,63,.32)}
  .lockpast .lpst.pending{color:#e3bd72;background:rgba(226,182,92,.1);
    border:1px solid rgba(226,182,92,.3)}
  .lockpast .lpm{flex:1;min-width:0;font-size:13px;font-weight:600;color:var(--parchment)}
  .lockpast .lpd{display:block;font-size:11.5px;font-weight:500;color:var(--text-faint);
    margin-top:2px}
  .lockpast .lpg{flex:none;color:var(--text-dim);font-size:17px;line-height:1}
  .lockpast .lp:hover .lpg{color:var(--parchment)}
  .lockpast .lpmore{font-size:11.5px;color:var(--text-dim);margin-top:9px}

  /* ---------------------------------------------------------------------
     APPLICATION NOTICE

     Deliberately a normal card with a 3px rail rather than a filled amber
     panel: the announcement board sits directly above it, and two loud
     boxes stacked on top of each other means neither is read.
     --------------------------------------------------------------------- */
  /* No margin-bottom. `.page` is a flex column with gap:22px, so a margin
     here ADDED to that gap and left more space under the card than above
     it. The gap alone spaces it, identically on both sides.

     No left rail either, and a warmer fill than a plain card: on a page
     built out of --charcoal-2 panels, a --charcoal-2 notice reads as one
     more panel. The tint is what separates it, not a stripe. */
  .appnote{display:flex;align-items:center;gap:15px;
    background:linear-gradient(90deg,rgba(212,146,58,.10),rgba(212,146,58,.045));
    border:1px solid rgba(212,146,58,.30);border-radius:14px;padding:15px 18px}
  .appnote .ic{width:34px;height:34px;flex:none;border-radius:10px;display:grid;place-items:center;
    background:rgba(212,146,58,.11);border:1px solid rgba(212,146,58,.26)}
  .appnote .ic svg{width:17px;height:17px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .appnote h4{font-size:13.5px;font-weight:700}
  .appnote p{font-size:12.5px;color:var(--text-faint);margin-top:2px;max-width:82ch}
  .appnote .act{margin-left:auto;flex:none;display:flex;align-items:center;gap:12px}
  .appnote .step{font-size:11.5px;color:var(--text-dim);white-space:nowrap}
  .appnote .step b{color:var(--gold);font-weight:700}
  .appnote .go{display:inline-flex;align-items:center;justify-content:center;padding:9px 15px;
    border-radius:10px;font-size:12.5px;font-weight:700;white-space:nowrap;
    background:linear-gradient(145deg,var(--amber),var(--gold));color:#1a1206;border:none;cursor:pointer}
  .appnote .go.quiet{background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-faint)}
  @media (max-width:760px){
    .appnote{flex-wrap:wrap}
    .appnote .act{margin-left:0;width:100%}
  }
</style>
<link rel="stylesheet" href="/assets/css/tones.css?v=2.5.0">
<script src="/assets/js/ucp.js?v=3.0.2"></script>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <!-- ===== DASHBOARD PAGE ===== -->
      <div class="page" id="page-dashboard">

      <!-- ANNOUNCEMENT BANNER — filled from api/announcements.php, hidden
           until there is something live to say. -->
      <div class="announce" id="announce" hidden></div>

      <div class="hello">
        <h2>Welcome back, <b id="welcomeName">&nbsp;</b></h2>
        <p>Blaine County is live. Here's the state of the server today.</p>
      </div>

      <!-- APPLICATION NOTICE — drawn by api/application-mine.php below.
           Two states only: not started, and waiting. Passing or being denied
           arrives as a notification and this card simply stops appearing,
           which is why there is no third state to write here. -->
      <div id="appNotice"></div>

      <!-- STATS: 5 metrics -->
      <div class="stats" id="statStrip">
        <div class="stat">
          <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>Registered users</div>
          <div class="num" id="statUsers" data-count="42318">42,318</div>
        </div>
        <div class="stat">
          <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20v-1a4 4 0 0 1 4-4h4M14 20v-1a4 4 0 0 1 4-4h0a4 4 0 0 1 4 4v1"/></svg>Created characters</div>
          <div class="num" data-count="18704">18,704</div>
        </div>
        <div class="stat">
          <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 3h9l3 3v15H6z"/><path d="M9 12h6M9 16h6M9 8h3"/></svg>User applications</div>
          <div class="num" id="statApplications" data-count="126">126</div>
        </div>
        <div class="stat">
          <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 16l1.5-5h11L19 16M5 16h14v3H5zM7 19v1M17 19v1"/><circle cx="8" cy="16" r="0"/></svg>Player vehicles</div>
          <div class="num" data-count="31592">31,592</div>
        </div>
        <div class="stat">
          <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 4l9 6.5V20H3zM9 20v-6h6v6"/></svg>Total properties</div>
          <div class="num" data-count="7845">7,845</div>
        </div>
      </div>

      <!-- THIN CAPACITY BAR -->
      <div class="capacity">
        <span class="ct"><span class="d"></span><span class="t">Server capacity</span></span>
        <span class="pv">142 <small>/ 200</small></span>
        <span class="bar"><i style="width:71%"></i></span>
        <span class="meta">58 slots open</span>
      </div>

      <!-- BODY -->
      <div class="body-grid">
        <div class="col">
          <div class="panel" style="flex:1">
            <div class="panel-head">
              <h3>County Bulletin</h3>
              <a class="link" id="bulletinManage" href="/dashboard/bulletin" hidden>Administrative Actions →</a>
            </div>
            <div class="news-track" id="newsTrack"></div>
            <div class="news-nav">
              <button class="news-arrow" id="newsPrev" aria-label="Previous">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
              </button>
              <div class="news-dots" id="newsDots"></div>
              <button class="news-arrow" id="newsNext" aria-label="Next">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
              </button>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="panel discord">
            <div class="discord-body">
              <div class="discord-top">
                <span class="discord-mark">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 5.3A17 17 0 0 0 15.3 4l-.2.4a13 13 0 0 1 3.7 1.2 12.7 12.7 0 0 0-11.6 0A13 13 0 0 1 10.9 4l-.2-.4A17 17 0 0 0 6.5 5.3C3.9 9.2 3.2 13 3.5 16.7a17 17 0 0 0 5.2 2.6l.6-1a11 11 0 0 1-1.9-.9l.5-.4a9 9 0 0 0 8.2 0l.5.4c-.6.4-1.2.7-1.9.9l.6 1a17 17 0 0 0 5.2-2.6c.4-4.3-.7-8-3-11.4zM9.5 14.5c-.9 0-1.7-.9-1.7-2s.7-2 1.7-2 1.7.9 1.7 2-.8 2-1.7 2zm5 0c-.9 0-1.7-.9-1.7-2s.8-2 1.7-2 1.7.9 1.7 2-.8 2-1.7 2z"/></svg>
                </span>
                <div>
                  <div class="nm">BlaineSide RP</div>
                  <div class="sub"><span class="g"></span>Official community server</div>
                </div>
              </div>
              <div class="discord-stat"><span class="big" id="dcOnline">3,241</span><span class="small">online now</span></div>
              <div class="discord-line" id="dcMembers">28,905 members total</div>
              <a class="discord-btn" href="https://discord.gg/8GUuTBcEsD" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 5.3A17 17 0 0 0 15.3 4l-.2.4a13 13 0 0 1 3.7 1.2 12.7 12.7 0 0 0-11.6 0A13 13 0 0 1 10.9 4l-.2-.4A17 17 0 0 0 6.5 5.3C3.9 9.2 3.2 13 3.5 16.7a17 17 0 0 0 5.2 2.6l.6-1a11 11 0 0 1-1.9-.9l.5-.4a9 9 0 0 0 8.2 0l.5.4c-.6.4-1.2.7-1.9.9l.6 1a17 17 0 0 0 5.2-2.6c.4-4.3-.7-8-3-11.4zM9.5 14.5c-.9 0-1.7-.9-1.7-2s.7-2 1.7-2 1.7.9 1.7 2-.8 2-1.7 2zm5 0c-.9 0-1.7-.9-1.7-2s.8-2 1.7-2 1.7.9 1.7 2-.8 2-1.7 2z"/></svg>
                Join the Discord
              </a>
            </div>
          </div>

          <div class="panel" style="flex:1">
            <div class="panel-head">
              <h3>Service status</h3>
              <span class="live"><span class="d"></span>Live</span>
            </div>
            <div class="status-list">
              <div class="status-row deg" id="svc-game"><span class="led"></span><span class="nm">Game server</span><span class="val">Not launched</span><span class="sub">coming soon</span></div>
              <div class="status-row" id="svc-forums"><span class="led"></span><span class="nm">Forums</span><span class="val">Checking…</span><span class="sub"></span></div>
              <div class="status-row" id="svc-ucp"><span class="led"></span><span class="nm">UCP</span><span class="val">Checking…</span><span class="sub"></span></div>
              <div class="status-row" id="svc-discord"><span class="led"></span><span class="nm">Discord</span><span class="val">Checking…</span><span class="sub"></span></div>
            </div>
          </div>
        </div>
      </div>

      </div><!-- /page-dashboard -->

      <!-- The full notifications list is its own page now:
           /dashboard/notifications. It lived here as a hash route with its
           own styles and its own copy of the rendering; one document is
           easier to keep true than two. -->

    </main>
  </div>

<script>
  /* =====================================================================
     CURRENT USER  —  the signed-in UCP account.
     On the live server, set these from the session (the backend injects the
     logged-in user's UCP name + staff rank). Players have no rank → leave
     UCP_ROLE empty ('') and the role line hides.
     ===================================================================== */
  let UCP_NAME = '';
  let UCP_RANK = 0;    // 0–9 (see the ladder below); replaced by session.php
  let UCP_ROLE = '';   // display name; blank until the server answers

  /* Staff rank ladder — admin_rank in the database (0–9):
       0 Member · 1 Support Staff · 2 Development Team · 3 Trainee Admin
       4 Admin Lvl 1 · 5 Admin Lvl 2 · 6 Senior Admin · 7 Lead Admin
       8 Management · 9 Founder
     Members (0) show no rank tag. The server sends both `rank` (number)
     and `role` (name); a future admin panel uses the number to gate features. */
  /* Escape anything that comes from the server or another user before it
     reaches innerHTML. Bulletin titles/bodies and notification text are
     author-supplied, so an unescaped tag here is stored XSS. */
  const esc = s => (window.UCP && UCP.esc) ? UCP.esc(s)
    : String(s == null ? '' : s).replace(/[&<>"']/g, c =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  const RANK_NAMES = ['Member','Support Staff','Development Team','Trainee Admin',
    'Admin Lvl 1','Admin Lvl 2','Senior Admin','Lead Admin','Management','Founder'];

  /* logout */
  (function(){
    const lb=document.getElementById('logoutBtn');
    if(lb) lb.addEventListener('click',()=>{
      lb.style.pointerEvents='none';
      /* Forget the cached identity — the next person at this computer starts
         blank rather than briefly wearing the last one's name and menu. */
      if(window.UCP && UCP.forgetMe) UCP.forgetMe();
      // Fetch this session's CSRF token, then send it with the logout POST.
      fetch('/api/csrf.php',{credentials:'include'})
        .then(r=>r.json()).catch(()=>({}))
        .then(t=>fetch('/api/logout.php',{
          method:'POST',
          credentials:'include',
          headers:{'Content-Type':'application/json','X-CSRF-Token':(t&&t.token)||''},
          body:'{}'
        }))
        .then(r=>r.json()).then(d=>{ window.location = (d && d.redirect) || '/login'; })
        .catch(()=>{ window.location = '/login'; });
    });
  })();

  /* =====================================================================
     LIVE SERVICE STATUS
     A browser can't read another site's response (CORS), but it CAN tell
     whether the site's favicon loads. If it loads → up; if it errors or
     times out → down. Good enough for an at-a-glance status board.
     Game server has no endpoint yet, so it stays a "coming soon" placeholder.
     ===================================================================== */
  function setStatus(id, state, val, sub){
    const row=document.getElementById(id); if(!row) return;
    row.classList.remove('up','deg','down');
    row.classList.add(state);
    row.querySelector('.val').textContent=val;
    row.querySelector('.sub').textContent=sub||'';
  }

  function probe(url, timeout=6000){
    return new Promise(resolve=>{
      const img=new Image();
      let done=false;
      const t0=performance.now();
      const finish=(up)=>{ if(done) return; done=true; clearTimeout(timer);
        resolve({up, ms: Math.round(performance.now()-t0)}); };
      const timer=setTimeout(()=>finish(false), timeout);
      img.onload=()=>finish(true);
      img.onerror=()=>finish(true); // an error response still proves the host answered
      // cache-bust so we re-check each time
      img.src = url + (url.includes('?')?'&':'?') + '_=' + Date.now();
    });
  }

  function checkServices(){
    // Forums
    setStatus('svc-forums','deg','Checking…','');
    probe('https://forum.blaineside.com/favicon.ico').then(r=>{
      r.up ? setStatus('svc-forums','up','Operational', r.ms+'ms')
           : setStatus('svc-forums','down','Unreachable','no response');
    });
    // UCP (this very site — will essentially always be up if you can see this)
    setStatus('svc-ucp','deg','Checking…','');
    probe('https://ucp.blaineside.com/favicon.ico').then(r=>{
      r.up ? setStatus('svc-ucp','up','Operational', r.ms+'ms')
           : setStatus('svc-ucp','down','Unreachable','no response');
    });
    // Discord — use the live widget/invite reachability
    setStatus('svc-discord','deg','Checking…','');
    probe('https://discord.com/assets/favicon.ico').then(r=>{
      r.up ? setStatus('svc-discord','up','Operational','')
           : setStatus('svc-discord','down','Unreachable','');
    });
    // Game server — placeholder until it launches
    setStatus('svc-game','deg','Not launched','coming soon');
  }
  checkServices();
  setInterval(checkServices, 60000); // re-check every minute

  /* Ask the server who's signed in. If nobody, bounce to the login page.
     Falls back to the demo values if the API isn't deployed yet. */
  /* =====================================================================
     LOCKED ACCOUNTS

     They are allowed this far and no further. api/login.php gives a locked
     sign-in a session with no 'uid' in it, so every authenticated endpoint
     already refuses them — this only decides what they SEE, which is an
     explanation rather than a bounce back to the sign-in page they just came
     from. Everything except Log out is removed from the document.
     ===================================================================== */
  /* Set before anything else touches the page. The dashboard's other
     loaders — bulletins, announcements, stats — are already in flight when
     this runs, and they write into elements the lock screen has replaced.
     They check this and stand down rather than throwing into a page the
     player is trying to read. */
  let UCP_LOCKED = false;

  function showLocked(d){
    UCP_LOCKED = true;
    /* finishLoading() never runs for a locked account, so the skeleton class
       has to come off here or the whole page stays in its loading state. */
    document.body.classList.remove('loading');
    document.querySelectorAll('.skeleton').forEach(el => el.classList.remove('skeleton'));
    const lock = d.lock || {};
    const when = lock.at ? new Date(lock.at * 1000) : null;
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    document.getElementById('nav').innerHTML = '';
    document.querySelectorAll('.topbar .icon-btn, .topbar .searchbox, .topbar .divider')
      .forEach(el => el.remove());
    const title = document.querySelector('.page-title h1');
    if(title) title.textContent = 'Account locked';

    const acctName = document.getElementById('acctName');
    const acctRole = document.getElementById('acctRole');
    if(acctName) acctName.textContent = d.name || '';
    if(acctRole){ acctRole.textContent = 'Locked';
      acctRole.style.setProperty('color', '#d29b8d', 'important'); }
    const menuName = document.getElementById('menuName'), menuRole = document.getElementById('menuRole');
    if(menuName) menuName.textContent = d.name || '';
    if(menuRole){ menuRole.textContent = 'Locked';
      menuRole.style.setProperty('color', '#d29b8d', 'important'); }
    /* The account menu goes too — My Profile behind it leads nowhere. */
    const mi = document.querySelectorAll('.account-menu .menu-item:not(.danger), .account-menu .menu-sep');
    mi.forEach(el => el.remove());

    document.querySelector('.content').innerHTML =
      '<div class="lockpage"><div class="lockhero">' +
        '<span class="mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
          '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></span>' +
        '<h1>Your account is locked</h1>' +
        '<p class="lede">A member of staff has put a hold on this UCP while something is looked ' +
          'into. You can still sign in to read this page, but nothing else is available until the ' +
          'lock is removed.</p>' +

        '<div class="lockwhy">' +
          '<div class="k">Reason given</div>' +
          '<div class="v">' + (lock.reason ? esc(lock.reason)
              : 'No reason was recorded. Ask staff on Discord and they can tell you.') + '</div>' +
          '<div class="m">' +
            (lock.by ? 'Locked by ' + esc(lock.by) : 'Locked') +
            (when ? ' · ' + when.getUTCDate() + ' ' +
              ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][when.getUTCMonth()] +
              ' ' + when.getUTCFullYear() : '') +
          '</div>' +
        '</div>' +

        '<div class="locksteps">' +
          '<h3>What happens now</h3>' +
          '<div class="lockstep"><span class="n">1</span><span>Staff review whatever prompted the ' +
            'lock. Most are resolved without any further action.</span></div>' +
          '<div class="lockstep"><span class="n">2</span><span>You can <b>appeal the lock</b> ' +
            'from here. A member of staff who did not issue it reads the appeal and decides it, ' +
            'and you are told the outcome with a reason either way.</span></div>' +
          '<div class="lockstep"><span class="n">3</span><span>If the lock is lifted, everything ' +
            'comes back exactly as you left it. <b>Nothing has been deleted.</b></span></div>' +
        '</div>' +

        /* The appeal is the one thing a locked account can still reach, so it
           is the primary action here and Log out is the quiet one. Which
           label it carries depends on whether they already have an appeal
           open — filled in by the eligibility call below, because a locked
           player clicking "Appeal" only to be told they already have one is
           a dead end from a page that has no other exits. */
        '<div id="lockPast"></div>' +

        '<div class="lockacts">' +
          '<a class="btn primary" id="lockAppeal" href="/dashboard/appeals#submit">' +
            'Appeal this lock</a>' +
          '<button class="btn" id="lockOut">Log out</button>' +
        '</div>' +
      '</div>' +
      '<p class="lockfoot">Your characters, money and property are untouched. A lock only stops ' +
        'access to the UCP.</p></div>';

    /* Do they already have one open? Asked after the page is drawn, so the
       hero never waits on it — the link works either way, and this only
       sharpens the label. appeal-eligibility.php accepts a locked session
       (see current_account_or_locked in api/_account.php). */
    fetch('/api/appeal-eligibility.php', { credentials:'include' })
      .then(function(r){ return r.json(); })
      .then(function(e){
        var a = document.getElementById('lockAppeal');
        if(!a || !e || e.ok !== true) return;
        /* Past appeals, on the one page a locked account can reach.
           Somebody locked for the second time wants to read what they were
           told the first time, and the alternative is asking staff to go and
           look it up. Compact — status, when, and a way in. */
        var past = (e.history || []).filter(function(h){ return h.id !== e.open; });
        if(past.length){
          document.getElementById('lockPast').innerHTML =
            '<div class="lockpast"><h3>Your past appeals</h3>' +
            past.slice(0, 6).map(function(h){
              var d = new Date(h.created_at * 1000);
              var M = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
              return '<a class="lp" href="/dashboard/appeals?id=' + h.id + '">' +
                '<span class="lpst ' + h.status + '">' + h.status + '</span>' +
                '<span class="lpm">Appeal #' + h.id + '<span class="lpd">' +
                  d.getUTCDate() + ' ' + M[d.getUTCMonth()] + ' ' + d.getUTCFullYear() +
                  (h.overruled ? ' · overturned' : '') + '</span></span>' +
                '<span class="lpg">›</span></a>';
            }).join('') +
            (past.length > 6 ? '<div class="lpmore">Showing the six most recent.</div>' : '') +
            '</div>';
        }

        if(e.open){
          a.textContent = 'View your appeal';
          a.setAttribute('href', '/dashboard/appeals?id=' + e.open);
        } else if(!e.may && e.why){
          /* Can't appeal — a cooldown, or the tables aren't migrated. Say so
             rather than sending them to a page that will refuse them. */
          a.textContent = 'Appeals unavailable';
          a.removeAttribute('href');
          a.classList.remove('primary');
          a.setAttribute('title', e.why);
          document.getElementById('lockOut').classList.add('primary');
        }
      }).catch(function(){});

    document.getElementById('lockOut').addEventListener('click', function(){
      this.disabled = true;
      if(window.UCP && UCP.forgetMe) UCP.forgetMe();
      fetch('/api/csrf.php',{credentials:'include'}).then(r=>r.json()).catch(()=>({}))
        .then(t=>fetch('/api/logout.php',{method:'POST',credentials:'include',
          headers:{'Content-Type':'application/json','X-CSRF-Token':(t&&t.token)||''},body:'{}'}))
        .then(()=>window.location.replace('/login'))
        .catch(()=>window.location.href='/api/logout.php?next=/login');
    });

    document.body.classList.add('ready');
  }

  (function loadSession(){
    fetch('/api/session.php',{credentials:'include'})
      .then(r=>r.json()).then(d=>{
        if(d && d.locked){ showLocked(d); return; }
        if(d && d.ok && d.authenticated){
          // Two-factor is mandatory for this rank and isn't on yet. The API
          // still answers normally — the gate is the UCP being unusable until
          // it's set up, not a half-broken session.
          if(d.twofa_setup_required){ window.location.replace('/security?setup=required'); return; }
          UCP_NAME = d.name || UCP_NAME;
          UCP_RANK = (typeof d.rank==='number') ? d.rank : 0;
          UCP_ROLE = d.role || 'Member';   // server maps rank→name (Member for rank 0)
          if(window.UCP && UCP.rememberMe) UCP.rememberMe(d);
          const was = [IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join();
          IS_ADMINISTRATOR = UCP_RANK >= 3;
          IS_MANAGER       = UCP_RANK >= 8;
          IS_FOUNDER       = UCP_RANK >= 9;
          MY_RANK          = UCP_RANK | 0;
          MY_TEAMS         = d.teams || [];
          /* Only redraw if the seed was wrong — redrawing identical HTML is
             what the eye reads as a flash. */
          if([IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join() !== was) renderSidebar(SIDEBAR);
          applyIdentity();
        } else if(d && d.authenticated===false){
          window.location = '/login';
        }
      }).catch(()=>{ /* API not up yet — keep demo values */ });
  })();

  function applyIdentity(){
    const set=(id,val)=>{const el=document.getElementById(id); if(el) el.textContent=val;};
    set('welcomeName', UCP_NAME);
    drawApplicationNotice();
    set('acctName', UCP_NAME);
    set('menuName', UCP_NAME);
    // rank name always shows now (Member included)
    const roleName = UCP_ROLE || 'Member';
    ['acctRole','menuRole'].forEach(id=>{
      const el=document.getElementById(id);
      if(!el) return;
      el.textContent = roleName;
      el.style.display = '';
    });
  }

  /* =====================================================================
     SIDEBAR CONFIG  —  edit this to change the menu. Nothing else needed.
     ---------------------------------------------------------------------
     Item shapes:
       { label, icon, href, active }                      → simple link
       { label, icon, children:[ {label,href}, ... ] }    → collapsible group
       { heading:'TEXT' }                                 → section label
     `icon` is one of the keys in ICONS below. Add new icons there.
     ===================================================================== */
  /* The sidebar lives in assets/js/ucp.js — one copy for every page.
     It used to be pasted into all eleven, which is eleven things to forget
     when one of them changes; adding a menu item is now an edit to NAV in
     that file and nothing else. Any page with <nav id="nav"> gets it, drawn
     from the cached rank on load and again when api/session.php answers. */


  function svg(name, cls){
    return `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor">${ICONS[name]||''}</svg>`;
  }

  /* Administration items appear only for Management and Founders. The pages
     behind them check the rank themselves; hiding the link is so nobody is
     shown a door that won't open. */
  let IS_MANAGER=false, IS_FOUNDER=false, IS_ADMINISTRATOR=false;
  let MY_RANK = 0, MY_TEAMS = [];   // the ladder rung, and sub-group keys
  /* Seed from the last known session so the FIRST paint is right. Without
     this every navigation drew the sidebar twice — once with no
     Administration section, once with it — which is the flicker.
     api/session.php confirms it below, and the pages and the endpoints check
     the rank with the server on every request regardless. */
  (function(){
    var me = window.UCP && UCP.me;
    if(!me) return;
    IS_ADMINISTRATOR = me.rank >= 3;
    IS_MANAGER       = me.rank >= 8;
    IS_FOUNDER       = me.rank >= 9;
    MY_RANK          = me.rank | 0;
    MY_TEAMS         = me.teams || [];
    UCP_RANK = me.rank;
    UCP_ROLE = me.role || 'Member';
    UCP_NAME = me.name || UCP_NAME;
  })();
  /* Menu gates.

     `min` is a rank on the ladder in api/_ranks.php. `team` is a sub-group
     key that opens the item on its own at ANY rank — which is how a Staff
     Management holder reaches the Staff Report Panel without being
     Management. A menu drawn from rank alone would be wrong for exactly the
     people the sub-group exists for.

     This decides what is DRAWN. Every page behind a link asks the server,
     and every endpoint checks again; nothing here is a permission. */

  renderSidebar(SIDEBAR);

  /* =====================================================================
     COUNTY BULLETIN

     Fed by api/bulletins.php — the bulletins Management has switched on,
     newest first, up to five. Nothing is hardcoded here any more: what
     staff publish on /dashboard/bulletin is what appears below.

     The "Administrative Actions" link to that page is hidden unless the
     server says this account may manage bulletins. That is a courtesy, not
     the control: the page and every endpoint behind it check the rank
     themselves.
     ===================================================================== */
  const TAGCLASS = { event:'evt', update:'upd', notice:'' };
  const TAGLABEL = { event:'Event', update:'Update', notice:'Notice' };
  const THEME_BY_TYPE = { event:1, update:2, notice:3 };

  /* "2 hours ago" from a unix timestamp — ucp.js has this, with a fallback
     for the moment it hasn't loaded. */
  function whenFrom(ts){
    if(window.UCP && UCP.relTime) return UCP.relTime(ts);
    const s=Math.floor(Date.now()/1000)-ts;
    if(s<3600) return Math.max(1,Math.floor(s/60))+' min ago';
    if(s<86400) return Math.floor(s/3600)+'h ago';
    return Math.floor(s/86400)+'d ago';
  }

  function renderBulletin(posts){
    const track=document.getElementById('newsTrack'), dotsWrap=document.getElementById('newsDots');
    const nav=document.querySelector('.news-nav');
    if(!posts.length){
      track.innerHTML=`<div class="empty">
        <span class="ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h13v14H4z"/><path d="M17 8h3v9a2 2 0 0 1-2 2M7 9h7M7 13h7M7 17h4"/></svg></span>
        <h4>No bulletins yet</h4>
        <p>County news and events will appear here once staff post them.</p>
      </div>`;
      dotsWrap.innerHTML=''; if(nav) nav.style.display='none';
      return { slides:[], dots:[] };
    }
    if(nav) nav.style.display='flex';
    track.innerHTML = posts.map((p,i)=>{
      const linkAttr = p.link ? ` data-link="${p.link.replace(/"/g,'&quot;')}" role="link" tabindex="0"` : '';
      const linkChip = p.link ? `<span class="slide-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>Read more</span>` : '';
      /* The image is a data: URL written by the editor, and the server only
         ever stores one that matches data:image/(png|jpeg|webp). */
      const bg = p.image
        ? `<div class="bg"><img src="${p.image}" alt="" style="object-position:center ${p.imgpos||50}%"></div>`
        : `<div class="bg"></div>`;
      return `
      <div class="slide s${THEME_BY_TYPE[p.type]||1} ${i===0?'on':''}${p.link?' has-link':''}${p.image?' has-img':''}"${linkAttr}>
        ${bg}
        <div class="cap">
          <span class="tag ${TAGCLASS[p.type]||''}">${esc(TAGLABEL[p.type]||'Notice')}</span>
          <h4>${esc(p.title)}</h4>
          <p>${esc(p.body)}</p>
          <div class="meta">${esc(whenFrom(p.at))} · ${esc(p.by)}${linkChip}</div>
        </div>
      </div>`;}).join('');
    dotsWrap.innerHTML = posts.map((_,i)=>`<span class="d ${i===0?'on':''}"></span>`).join('');
    track.querySelectorAll('.slide[data-link]').forEach(s=>{
      const url=s.dataset.link;
      if(!url) return;
      const open=()=>window.open(url,'_blank','noopener');
      s.addEventListener('click',open);
      s.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();open();}});
    });
    return { slides:[...track.querySelectorAll('.slide')], dots:[...dotsWrap.querySelectorAll('.d')] };
  }

  /* rotation — rebuilt whenever the set is (re)loaded */
  let slides=[], dots=[], idx=0, newsTimer=null;
  function go(n){
    const len=slides.length; if(!len) return;
    n=(n+len)%len;
    slides.forEach((s,i)=>s.classList.toggle('on',i===n));
    dots.forEach((d,i)=>d.classList.toggle('on',i===n));
    idx=n;
  }
  function startNews(){ clearInterval(newsTimer); if(slides.length>1){newsTimer=setInterval(()=>go(idx+1),10000);} }
  function nudge(n){ go(idx+n); startNews(); }
  document.getElementById('newsPrev').addEventListener('click',()=>nudge(-1));
  document.getElementById('newsNext').addEventListener('click',()=>nudge(1));

  function mountBulletin(posts){
    if(UCP_LOCKED) return;
    const built=renderBulletin(posts);
    slides=built.slides; dots=built.dots; idx=0;
    dots.forEach((d,i)=>d.addEventListener('click',()=>{go(i);startNews();}));
    startNews();
  }

  fetch('/api/bulletins.php?scope=dashboard',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      if(!d || d.ok!==true){ mountBulletin([]); return; }
      mountBulletin(d.bulletins||[]);
      const manage=document.getElementById('bulletinManage');
      if(manage) manage.hidden = !d.may_manage;
      /* The sidebar is driven by the rank from session.php above; this
         endpoint only decides whether the manage link on the carousel shows. */
    })
    .catch(()=>mountBulletin([]));

  /* Anyone who bookmarked the old hash route lands on the page that
     replaced it rather than on a dashboard that ignores them. */
  if((location.hash||'').replace('#/','') === 'notifications'){
    window.location.replace('/dashboard/notifications');
  }

  /* account menu */
  const accBtn=document.querySelector('.account-btn'), accMenu=document.querySelector('.account-menu');
  accMenu.style.display='none';
  accBtn.addEventListener('click',e=>{e.stopPropagation();accMenu.style.display=accMenu.style.display==='none'?'block':'none';});
  document.addEventListener('click',()=>{accMenu.style.display='none';});

  /* =====================================================================
     ANNOUNCEMENT BANNER

     One live announcement at a time, published from
     /dashboard/announcements. A dismissal is remembered per announcement
     revision, so editing the wording brings it back for everyone — they
     have not read THIS one. Some types can't be dismissed at all.
     ===================================================================== */
  const ANN_TYPES={
    notice:      {label:'Announcement', cls:'t-notice'},
    maintenance: {label:'Maintenance',  cls:'t-maintenance'},
    warning:     {label:'Warning',      cls:'t-warning'},
    critical:    {label:'Critical',     cls:'t-critical'},
    success:     {label:'Resolved',     cls:'t-success'}
  };
  /* One glyph per type — a megaphone for news reads wrong on an outage. */
  const ANN_ICONS={
    notice:'<path d="M3 11l14-5v12L3 15v-4z"/><path d="M17 8a3 3 0 0 1 0 8M6 15v3a1 1 0 0 0 1 1h2"/>',
    maintenance:'<path d="M14.7 6.3a4 4 0 0 1-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.1 2.1-2.3-2.3z"/>',
    warning:'<path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/>',
    critical:'<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/>',
    success:'<path d="M20 6L9 17l-5-5"/>'
  };

  function annDismissedKey(a){ return 'bs-ann-'+a.id+'-'+a.rev; }

  function annWhen(ts){
    if(window.UCP && UCP.relTime) return UCP.relTime(ts);
    const d=Math.floor(Date.now()/1000)-ts;
    return d<3600 ? Math.max(1,Math.floor(d/60))+' min ago' : Math.floor(d/3600)+'h ago';
  }

  function renderAnnouncement(a){
    const el=document.getElementById('announce');
    if(!el) return;
    if(!a){ el.hidden=true; el.innerHTML=''; return; }

    try{ if(a.dismissable && localStorage.getItem(annDismissedKey(a))){ el.hidden=true; return; } }catch(e){}

    const t=ANN_TYPES[a.type]||ANN_TYPES.notice;
    el.className='ann '+t.cls;
    el.innerHTML=`
      <span class="ann-stamp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">${ANN_ICONS[a.type]||ANN_ICONS.notice}</svg></span>
      <div class="ann-main">
        <div class="ann-eyebrow">${esc(t.label)}<span class="sep">·</span><span class="ago">${esc(annWhen(a.at))}</span></div>
        <div class="ann-head" title="${esc(a.lead)}">${esc(a.lead)}</div>
        ${a.body?`<div class="ann-detail" title="${esc(a.body)}">${esc(a.body)}</div>`:''}
      </div>
      <div class="ann-acts">
        ${a.link?`<a class="ann-link" href="${esc(a.link)}" target="_blank" rel="noopener">Read more<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h13M13 6l6 6-6 6"/></svg></a>`:''}
        ${a.dismissable?`<button class="ann-x" aria-label="Dismiss"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 6l12 12M18 6L6 18"/></svg></button>`:''}
      </div>`;
    el.hidden=false;

    const x=el.querySelector('.ann-x');
    if(x) x.addEventListener('click',()=>{
      el.hidden=true;
      try{ localStorage.setItem(annDismissedKey(a),'1'); }catch(err){}
    });
  }

  fetch('/api/announcements.php',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{ if(!UCP_LOCKED && d && d.ok===true) renderAnnouncement(d.announcement); })
    .catch(()=>{});

  /* mobile drawer */
  const scrim=document.getElementById('scrim'), menuToggle=document.getElementById('menuToggle');
  function openNav(){document.body.classList.add('nav-open');scrim.classList.add('show');}
  function closeNav(){document.body.classList.remove('nav-open');scrim.classList.remove('show');}
  menuToggle.addEventListener('click',e=>{e.stopPropagation();document.body.classList.contains('nav-open')?closeNav():openNav();});
  scrim.addEventListener('click',closeNav);
  document.getElementById('nav').addEventListener('click',e=>{
    if(e.target.closest('.sub a') || e.target.closest('.nav-group:not([data-collapsible]) .nav-item')) closeNav();
  });
  window.addEventListener('resize',()=>{if(window.innerWidth>760)closeNav();});

  /* The clock, the build number and the status line are drawn by
     assets/js/ucp.js — one copy for every page. */

  /* ===== COUNT-UP on stat numbers ===== */
  function countUp(el){
    const target=parseInt(el.dataset.count,10); if(isNaN(target))return;
    const dur=900, t0=performance.now();
    function frame(now){
      const p=Math.min(1,(now-t0)/dur);
      const eased=1-Math.pow(1-p,3); /* easeOutCubic */
      el.textContent=Math.round(target*eased).toLocaleString('en-US');
      if(p<1) requestAnimationFrame(frame);
      else el.textContent=target.toLocaleString('en-US');
    }
    requestAnimationFrame(frame);
  }

  /* ===== LOADING → REVEAL demo =====
     Simulates the brief moment before live server data arrives. When you
     wire real data, call finishLoading() once the payload is in. Skeletons
     show first, then content reveals + stats count up. */
  const SKELETON_TARGETS=['#statStrip .num','.capacity .pv','.capacity .meta'];
  function startLoading(){
    document.body.classList.add('loading');
    document.querySelectorAll('#statStrip .stat').forEach(s=>s.classList.add('skeleton'));
    const cap=document.querySelector('.capacity'); cap&&cap.classList.add('skeleton');
  }
  function finishLoading(){
    document.body.classList.remove('loading');
    document.querySelectorAll('.skeleton').forEach(s=>s.classList.remove('skeleton'));
    document.querySelectorAll('#statStrip .num[data-count]').forEach(countUp);
  }
  /* ===== REAL DATA =====
     Registered users and User applications come from api/stats.php — one
     count of UCP accounts that confirmed their email, one of applications
     that were actually sent. The other three tiles have no tables behind
     them yet and keep their designed placeholders until they do.

     finishLoading() runs either way: if the call fails, the numbers already
     in the HTML stay and the strip still reveals, rather than leaving
     skeletons on screen forever. */
  startLoading();
  (function loadStats(){
    var done = false;
    var reveal = function(){ if(!done && !UCP_LOCKED){ done = true; finishLoading(); } };

    fetch('/api/stats.php', {credentials:'include'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(d && d.ok === true && typeof d.users === 'number'){
          var el = document.getElementById('statUsers');
          if(el) el.dataset.count = d.users;
        }
        /* Applications only arrived with the application system, so the key
           is absent on a UCP that has not run the migration — and absent is
           not zero. The tile keeps its placeholder in that case rather than
           reporting a number nobody has any reason to believe. */
        if(d && d.ok === true && typeof d.applications === 'number'){
          var ae = document.getElementById('statApplications');
          if(ae) ae.dataset.count = d.applications;
        }
      })
      .catch(function(){})
      .then(reveal);

    /* A slow or hanging request must not hold the page in skeletons. */
    setTimeout(reveal, 2500);
  })();

  /* apply the signed-in user's name/role across the header + greeting */
  if(!UCP_LOCKED) applyIdentity();

  /* =====================================================================
     LIVE DISCORD STATS  —  pulls online + member counts from Discord's
     public widget. Requires "Enable Server Widget" in Discord:
       Server Settings → Widget → Enable Server Widget (ON)
     If the widget is off or the request fails, the numbers already in the
     HTML stay as a sensible fallback.
     DISCORD_GUILD_ID is your server's ID (right-click the server → Copy
     Server ID, with Developer Mode enabled).
     ===================================================================== */
  const DISCORD_GUILD_ID = '1530330234874892360';  // BlaineSide Discord server ID
  const DISCORD_INVITE_CODE = '8GUuTBcEsD'; // from https://discord.gg/8GUuTBcEsD

  function fmt(n){ return Number(n).toLocaleString('en-US'); }

  async function loadDiscordStats(){
    const onlineEl=document.getElementById('dcOnline');
    const membersEl=document.getElementById('dcMembers');
    if(!onlineEl||!membersEl) return;

    // 1) Widget gives the live "online now" count (needs widget enabled).
    if(DISCORD_GUILD_ID){
      try{
        const r=await fetch(`https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`,{cache:'no-store'});
        if(r.ok){
          const d=await r.json();
          if(typeof d.presence_count==='number') onlineEl.textContent=fmt(d.presence_count);
        }
      }catch(_){/* keep fallback */}
    }

    // 2) The invite endpoint gives approximate total + online (works from the invite code).
    try{
      const r=await fetch(`https://discord.com/api/v10/invites/${DISCORD_INVITE_CODE}?with_counts=true`,{cache:'no-store'});
      if(r.ok){
        const d=await r.json();
        if(typeof d.approximate_presence_count==='number') onlineEl.textContent=fmt(d.approximate_presence_count);
        if(typeof d.approximate_member_count==='number') membersEl.textContent=fmt(d.approximate_member_count)+' members total';
      }
    }catch(_){/* keep fallback */}
  }
  loadDiscordStats();
  setInterval(loadDiscordStats, 120000); // refresh every 2 min

  /* =====================================================================
     APPLICATION NOTICE

     A player cannot join the server until an application has been passed.
     The UCP and the forums are open to them the whole time, so this is a
     notice and not a gate — nothing on this page is hidden by it.

     It draws NOTHING for a player who has passed, for a player whose
     application is switched off, and for staff. The result of a decision
     arrives through the notification bell instead, which is why there is
     no "you passed" card: a permanent congratulation is clutter, and this
     is the busiest page in the UCP.
     ===================================================================== */
  function drawApplicationNotice(){
    var box = document.getElementById('appNotice');
    if(!box) return;

    UCP.get('application-mine.php').then(function(d){
      if(!d || !d.ok || !d.available) return;
      if(d.state === 'passed') return;

      var h = '';
      if(d.state === 'pending'){
        h = '<div class="appnote"><div class="ic"><svg viewBox="0 0 24 24">' +
            '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>' +
            '<div><h4>Your application is with Support Staff</h4>' +
            '<p>It is read by hand, and you will get a notification the moment it is decided.</p></div>' +
            '<div class="act"><span class="step">Step <b>2 of 2</b> · waiting</span>' +
            '<a class="go quiet" href="/dashboard/application">View it</a></div></div>';
      } else {
        var again = d.state === 'denied';
        h = '<div class="appnote"><div class="ic"><svg viewBox="0 0 24 24">' +
            '<path d="M4 5h16v14H4z"/><path d="M8 10h8M8 14h5"/></svg></div>' +
            '<div><h4>' + (again
              ? 'Your last application was denied'
              : 'You need to pass an application before you can join the server') + '</h4>' +
            '<p>' + (again
              ? 'There is feedback waiting that says what to change. You can apply again straight away.'
              : 'The UCP and forums are open to you now — the server is not. Every application is read by hand.') +
            '</p></div>' +
            '<div class="act"><span class="step">Step <b>1 of 2</b> · ' +
            (again ? 'not restarted' : 'not started') + '</span>' +
            '<a class="go" href="/dashboard/application">' +
            (again ? 'Apply again' : 'Start your application') + '</a></div></div>';
      }
      box.innerHTML = h;
    });
  }

</script>
</body>
</html>
