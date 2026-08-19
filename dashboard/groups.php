<?php
/**
 * Group Management.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'BlaineSide — County Bulletin';
$PAGE_HEADING = 'Group Management';
$PAGE_HEAD = <<<'HTML'
<style>
  :root{
    --amber:#d4923a; --gold:#e2b65c;
    --charcoal:#121110; --charcoal-2:#1a1815; --charcoal-3:#221f1b; --charcoal-4:#2b2723;
    --parchment:#f1efe9; --stone:#8a7f70; --text-dim:#655e51; --text-faint:#968e7e;
    --border:#26221e; --border-soft:#1f1c18;
    --danger:#c1553f; --ok:#7fa05a; --warn:#e2b65c;
    --sidebar-w:256px; --header-h:66px; --content-bg:#100f0e;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html{height:100%}
  body{font-family:'Inter',system-ui,sans-serif;background:var(--content-bg);color:var(--parchment);
    -webkit-font-smoothing:antialiased;display:flex;min-height:100vh;font-size:14px;line-height:1.5}
  a{color:var(--gold);text-decoration:none}
  ::-webkit-scrollbar{width:9px}
  ::-webkit-scrollbar-track{background:transparent}
  ::-webkit-scrollbar-thumb{background:var(--charcoal-4);border-radius:6px}

  /* ===== SIDEBAR (matches dashboard shell) ===== */
  .sidebar{width:var(--sidebar-w);flex:none;position:relative;background:var(--charcoal-2);
    border-right:1px solid var(--border-soft);z-index:50}
  .side-inner{position:sticky;top:0;height:100vh;display:flex;flex-direction:column;padding-bottom:66px}
  .side-brand{display:flex;align-items:center;height:var(--header-h);padding:0 24px;border-bottom:1px solid var(--border-soft);flex:none}
  .side-brand .name{font-family:'Oswald',sans-serif;font-weight:600;font-size:25px;letter-spacing:.07em;
    text-transform:uppercase;line-height:1;color:var(--parchment)}
  .side-brand .name b{color:var(--gold);font-weight:700}
  .side-scroll{flex:1;overflow-y:auto;padding:12px 14px 18px}
  .nav-group{margin-bottom:1px}
  .nav-item{display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:9px;font-size:14px;
    font-weight:500;color:var(--text-faint);cursor:pointer;transition:background .14s,color .14s;position:relative;user-select:none}
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
  .sub{max-height:0;overflow:hidden;transition:max-height .26s ease;margin-left:9px;border-left:1px solid var(--border);padding-left:8px}
  .nav-group.open .sub{max-height:340px}
  .sub a{display:block;padding:9px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--text-dim);transition:.13s;margin:1px 0}
  .sub a:hover{background:var(--charcoal-3);color:var(--parchment)}
  .sub a.slot-empty{color:var(--text-dim);font-style:italic;cursor:default}
  .sub a.slot-empty:hover{background:transparent;color:var(--text-dim)}
  .nav-heading{font-size:10.5px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);padding:18px 12px 8px}
  .side-foot{position:absolute;left:0;right:0;bottom:0;background:var(--charcoal-2);padding:13px 20px 15px;
    border-top:1px solid var(--border-soft);display:flex;flex-direction:column;gap:5px}
  .foot-line{font-size:11px;color:var(--text-dim);font-variant-numeric:tabular-nums;display:flex;align-items:center;gap:5px;flex-wrap:wrap;line-height:1.5}
  .foot-line .fv{color:var(--text-faint);font-weight:600}
  .foot-line .st{display:inline-flex;align-items:center;gap:6px}
  .foot-line .st .d{width:6px;height:6px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok)}

  /* ===== MAIN + HEADER ===== */
  .main{flex:1;min-width:0;display:flex;flex-direction:column;position:relative;background:transparent}

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
  .topbar,.content{position:relative;z-index:1}

  .topbar{height:var(--header-h);flex:none;display:flex;align-items:center;gap:16px;padding:0 26px;
    background:var(--charcoal-2);border-bottom:1px solid var(--border);
    box-shadow:0 1px 0 rgba(0,0,0,.4),0 6px 18px -12px rgba(0,0,0,.7);position:sticky;top:0;z-index:45}
  .page-title h1{font-size:16px;font-weight:700;letter-spacing:-.01em}
  .topbar .spacer{flex:1}
  .searchbox{display:flex;align-items:center;gap:9px;height:38px;padding:0 14px;width:280px;
    background:var(--charcoal);border:1px solid var(--border);border-radius:10px;color:var(--text-dim)}
  .searchbox svg{width:15px;height:15px;flex:none;stroke-width:2}
  .searchbox input{background:none;border:none;outline:none;color:var(--parchment);font-family:inherit;font-size:13.5px;width:100%}
  .searchbox input::placeholder{color:var(--text-dim)}
  .icon-btn{width:38px;height:38px;flex:none;display:grid;place-items:center;border-radius:10px;
    background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint);cursor:pointer;transition:.14s;position:relative}
  .icon-btn:hover{color:var(--parchment);background:var(--charcoal-3)}
  .icon-btn svg{width:18px;height:18px;stroke-width:1.9}
  .icon-btn .dot{position:absolute;top:9px;right:10px;width:7px;height:7px;border-radius:50%;background:var(--danger);border:2px solid var(--charcoal-2)}
  .hamburger{display:none}
  .search-mini{display:none}
  .divider{width:1px;height:30px;background:var(--border);flex:none}
  /* ---- account menu ----
     Missing entirely on this page: the markup was added when the account
     control went on every page, and the CSS for it was not. Nothing looked
     obviously wrong except the two icons, which without a size rule render
     at their intrinsic 170px — an arrow the height of the dropdown.
     Copied verbatim from the other pages rather than approximated. */
  .account-menu{position:absolute;right:0;top:calc(100% + 10px);width:230px;
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:13px;
    box-shadow:0 24px 50px -18px rgba(0,0,0,.8);padding:8px;z-index:60}
  .account-menu .mhead{padding:8px 10px 12px;border-bottom:1px solid var(--border-soft);margin-bottom:6px}
  .account-menu .mhead .n{font-size:14px;font-weight:700;color:var(--parchment)}
  .account-menu .mhead .rr{font-size:12px;color:var(--amber);font-weight:600;margin-top:1px}
  .menu-item{display:flex;align-items:center;gap:12px;padding:10px;border-radius:9px;
    font-size:13.5px;font-weight:500;color:var(--text-faint);cursor:pointer;transition:.13s;text-decoration:none}
  .menu-item svg{width:16px;height:16px;stroke-width:1.9;flex:none}
  .menu-item:hover{background:var(--charcoal-3);color:var(--parchment)}
  .menu-item.on{background:var(--charcoal-3);color:var(--parchment)}
  .menu-item.on svg{color:var(--gold)}
  .menu-item.danger{color:#d98a78}
  .menu-item.danger:hover{background:rgba(193,85,63,.12);color:#eab3a6}
  .menu-sep{height:1px;background:var(--border-soft);margin:6px 4px}
  @media (max-width:760px){ .account-menu{width:220px;right:-4px} }

  .account-btn{display:flex;align-items:center;gap:12px;padding:6px 12px;border-radius:10px;
    background:var(--charcoal);border:1px solid var(--border);cursor:pointer;transition:.14s;min-width:170px}
  .account-btn:hover{background:var(--charcoal-3)}
  .account-meta{display:flex;flex-direction:column;line-height:1.3;flex:1;min-width:0;text-align:left}
  .account-meta .u{font-size:13.5px;font-weight:600;color:var(--parchment);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .account-meta .r{font-size:11px;color:var(--amber);font-weight:600}
  .account-btn .caret{width:15px;height:15px;color:var(--text-dim);stroke-width:2;flex:none}
  .account-btn .acct-ico{display:none}

  /* ===== CONTENT ===== */
  .content{padding:28px 30px 44px;max-width:1180px;width:100%;margin:0 auto;display:flex;flex-direction:column;gap:22px}

  .page-back{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--text-faint);transition:.14s}
  .page-back:hover{color:var(--parchment)}
  .page-back svg{width:16px;height:16px}

  .phead{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap}
  .phead h2{font-size:24px;font-weight:700;letter-spacing:-.02em;margin-bottom:4px}
  .phead p{font-size:13.5px;color:var(--text-faint)}
  .phead p b{color:var(--gold);font-weight:700}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 18px;border-radius:10px;
    font-family:inherit;font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);
    background:var(--charcoal-2);color:var(--parchment);transition:.14s;white-space:nowrap}
  .btn:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  .btn svg{width:16px;height:16px;stroke-width:2}
  .btn.primary{background:linear-gradient(145deg,var(--gold),var(--amber));color:#1a1206;border:none;box-shadow:0 6px 16px rgba(212,146,58,.26)}
  .btn.primary:hover{transform:translateY(-1px);box-shadow:0 9px 20px rgba(212,146,58,.34)}
  .btn.danger{color:#e0a99b;border-color:rgba(193,85,63,.4)}
  .btn.danger:hover{background:rgba(193,85,63,.12);color:#eab3a6}
  .btn.ghost{background:transparent}
  .btn.sm{padding:8px 13px;font-size:12.5px}
  .btn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}

  /* dashboard-slot meter */
  .slotbar{display:flex;align-items:center;gap:14px;background:var(--charcoal-2);border:1px solid var(--border);
    border-radius:12px;padding:13px 18px;flex-wrap:wrap}
  .slotbar .si{display:flex;align-items:center;gap:9px;font-size:12.5px;font-weight:600;color:var(--text-faint);flex:none}
  .slotbar .si svg{width:16px;height:16px;color:var(--gold);stroke-width:1.9}
  .slotbar .cnt{font-size:13.5px;font-weight:700;font-variant-numeric:tabular-nums;flex:none}
  .slotbar .cnt b{color:var(--gold)}
  .slotbar .track{flex:1;height:7px;border-radius:5px;background:var(--charcoal);overflow:hidden;border:1px solid var(--border-soft);min-width:80px}
  .slotbar .track > i{display:block;height:100%;border-radius:5px;background:linear-gradient(90deg,var(--amber),var(--gold));transition:width .3s}
  .slotbar .note{font-size:11.5px;color:var(--text-dim);flex:none;min-width:0}

  /* ===== LISTING GRID ===== */
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}
  .bcard{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;overflow:hidden;
    display:flex;flex-direction:column;transition:border-color .16s, transform .16s}
  .bcard:hover{border-color:var(--charcoal-4)}
  .bcard.shown{border-color:rgba(212,146,58,.45)}
  .bcard .thumb{position:relative;height:150px;background:var(--charcoal-3);overflow:hidden}
  .bcard .thumb img{width:100%;height:100%;object-fit:cover;display:block}
  .bcard .thumb .grad{position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,11,10,.05),rgba(12,11,10,.55))}
  .bcard .thumb.noimg{display:grid;place-items:center;color:var(--text-dim)}
  .bcard .thumb.noimg svg{width:34px;height:34px;stroke-width:1.4}
  .bcard .thumb.g1{background:linear-gradient(120deg,#3a2a16,#191510)}
  .bcard .thumb.g2{background:linear-gradient(120deg,#2c2617,#181712)}
  .bcard .thumb.g3{background:linear-gradient(120deg,#33211a,#191310)}
  .shown-badge{position:absolute;top:11px;right:11px;z-index:2;display:inline-flex;align-items:center;gap:6px;
    font-size:11px;font-weight:700;color:#1a1206;background:linear-gradient(145deg,var(--gold),var(--amber));
    padding:5px 10px;border-radius:100px;box-shadow:0 4px 12px rgba(0,0,0,.4)}
  .shown-badge svg{width:12px;height:12px;stroke-width:2.6}
  .tag{position:absolute;top:11px;left:11px;z-index:2;display:inline-block;font-size:10px;font-weight:700;
    letter-spacing:.1em;text-transform:uppercase;padding:4px 9px;border-radius:100px;color:#1a1206;background:var(--gold)}
  .tag.evt{background:var(--danger);color:#fff}
  .tag.upd{background:var(--stone);color:#141210}
  .bcard .body{padding:15px 17px 8px;flex:1;display:flex;flex-direction:column;gap:6px}
  .bcard .body h3{font-size:16px;font-weight:700;letter-spacing:-.01em;line-height:1.3}
  .bcard .body p{font-size:12.5px;color:var(--text-faint);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .bcard .meta{font-size:11px;color:var(--text-dim);font-weight:600;margin-top:auto}
  .link-chip{display:inline-flex;align-items:center;gap:4px;color:var(--gold)}
  .link-chip svg{width:11px;height:11px}
  .bcard .foot{display:flex;align-items:center;gap:8px;padding:12px 15px;border-top:1px solid var(--border-soft);margin-top:10px}
  .toggle{display:inline-flex;align-items:center;gap:9px;cursor:pointer;user-select:none;flex:1;font-size:12.5px;font-weight:600;color:var(--text-faint)}
  .toggle .sw{width:38px;height:22px;border-radius:100px;background:var(--charcoal-4);position:relative;transition:.18s;flex:none}
  .toggle .sw::after{content:"";position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;background:#6b6357;transition:.18s}
  .toggle.on .sw{background:linear-gradient(145deg,var(--gold),var(--amber))}
  .toggle.on .sw::after{transform:translateX(16px);background:#1a1206}
  .toggle.on{color:var(--parchment)}
  .toggle.disabled{opacity:.4;cursor:not-allowed}
  .icon-act{width:34px;height:34px;flex:none;display:grid;place-items:center;border-radius:8px;background:var(--charcoal-3);
    border:1px solid var(--border);color:var(--text-faint);cursor:pointer;transition:.13s}
  .icon-act:hover{color:var(--parchment);background:var(--charcoal-4)}
  .icon-act.del:hover{color:#eab3a6;background:rgba(193,85,63,.14);border-color:rgba(193,85,63,.4)}
  .icon-act svg{width:15px;height:15px;stroke-width:2}

  /* inline delete confirm */
  .confirm{display:flex;align-items:center;gap:10px;padding:12px 15px;border-top:1px solid var(--border-soft);
    background:rgba(193,85,63,.07)}
  .confirm .q{flex:1;font-size:12.5px;font-weight:600;color:#e0a99b}

  /* empty listing */
  .empty{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;
    padding:60px 24px;gap:14px;background:var(--charcoal-2);border:1px dashed var(--border);border-radius:14px}
  .empty .ei{width:56px;height:56px;border-radius:15px;display:grid;place-items:center;background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-dim)}
  .empty .ei svg{width:26px;height:26px;stroke-width:1.6}
  .empty h4{font-size:16px;font-weight:700;color:var(--text-faint)}
  .empty p{font-size:13px;color:var(--text-dim);max-width:320px;line-height:1.5}

  /* ===== CREATE / EDIT ===== */
  .editor{display:grid;grid-template-columns:1.1fr .9fr;gap:24px;align-items:start}
  .form-card{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;padding:22px 24px}
  .field{display:flex;flex-direction:column;gap:8px;margin-bottom:18px}
  .field:last-child{margin-bottom:0}
  .field label{font-size:12.5px;font-weight:600;color:var(--stone);display:flex;justify-content:space-between;align-items:center;gap:8px}
  .field label .lt{display:inline-flex;align-items:baseline;gap:3px}
  .field label .req{color:var(--amber);font-weight:700}
  .field label .opt{color:var(--text-dim);font-weight:500}
  .field label .count{font-size:11px;color:var(--text-dim);font-weight:600;font-variant-numeric:tabular-nums;flex:none}
  .field-hint{font-size:11.5px;color:var(--text-dim);min-height:0}
  .field-hint.err{color:var(--danger)}
  .field-hint.ok{color:var(--ok)}
  .locked-field{display:flex;align-items:center;gap:10px;padding:12px 13px;background:var(--charcoal-3);
    border:1px solid var(--border);border-radius:10px;color:var(--parchment);font-size:14px;font-weight:600;cursor:not-allowed}
  .locked-field svg{width:15px;height:15px;flex:none;color:var(--text-dim);stroke-width:2}
  .locked-field .lock-note{margin-left:auto;font-size:11px;font-weight:600;color:var(--text-dim);
    background:var(--charcoal);border:1px solid var(--border);padding:3px 9px;border-radius:100px}
  input[type=text], textarea, select{width:100%;padding:12px 13px;font-family:inherit;font-size:14px;
    background:var(--charcoal);border:1px solid var(--border);border-radius:10px;color:var(--parchment);transition:border-color .16s, box-shadow .16s}
  textarea{resize:vertical;min-height:110px;line-height:1.55}
  input::placeholder, textarea::placeholder{color:var(--text-dim)}
  input:focus, textarea:focus, select:focus{outline:none;border-color:var(--amber);box-shadow:0 0 0 3px rgba(212,146,58,.15)}
  select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23968e7e' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;background-size:16px;padding-right:38px}

  /* type segmented control */
  .seg{display:flex;gap:6px}
  .seg button{flex:1;padding:10px;border-radius:9px;font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer;
    background:var(--charcoal);border:1px solid var(--border);color:var(--text-faint);transition:.14s;display:flex;align-items:center;justify-content:center;gap:7px}
  .seg button .swatch{width:9px;height:9px;border-radius:50%}
  .seg button:hover{color:var(--parchment)}
  .seg button.on{color:var(--parchment);border-color:var(--charcoal-4);background:var(--charcoal-3)}

  /* image uploader + reposition */
  .uploader{border:1px dashed var(--border);border-radius:12px;overflow:hidden;background:var(--charcoal)}
  .drop{padding:34px 20px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;cursor:pointer;transition:.14s}
  .drop:hover, .drop.drag{background:var(--charcoal-3)}
  .drop .di{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;background:var(--charcoal-3);border:1px solid var(--border);color:var(--gold)}
  .drop .di svg{width:22px;height:22px;stroke-width:1.7}
  .drop h5{font-size:13.5px;font-weight:700;color:var(--parchment)}
  .drop p{font-size:12px;color:var(--text-dim)}
  .drop .browse{color:var(--gold);font-weight:700}
  .imgframe{position:relative;height:190px;overflow:hidden;background:var(--charcoal-3);cursor:grab}
  .imgframe.dragging{cursor:grabbing}
    /* height:100% is what makes this draggable. Without it the box is the
     image's own height, object-fit:cover has nothing to crop, and
     object-position moves nothing — which is exactly how it behaved. */
.imgframe img{position:absolute;inset:0;width:100%;height:100%;user-select:none;-webkit-user-drag:none;object-fit:cover}
  .imgframe .hint{position:absolute;left:0;right:0;bottom:0;padding:9px 12px;font-size:11px;font-weight:600;color:#e6d3b2;
    background:linear-gradient(180deg,transparent,rgba(12,11,10,.85));display:flex;align-items:center;gap:7px;pointer-events:none}
  .imgframe .hint svg{width:13px;height:13px}
  .img-actions{display:flex;gap:8px;padding:10px 12px;border-top:1px solid var(--border-soft);background:var(--charcoal-2)}
  .img-actions .btn{flex:1}

  .form-actions{display:flex;gap:10px;margin-top:22px;padding-top:20px;border-top:1px solid var(--border-soft)}
  .form-actions .btn{flex:1}

  /* live preview */
  .preview-wrap{position:sticky;top:calc(var(--header-h) + 24px)}
  .preview-label{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--text-dim);margin-bottom:12px;display:flex;align-items:center;gap:8px}
  .preview-label .dotlbl{width:6px;height:6px;border-radius:50%;background:var(--gold)}
  /* preview reuses the dashboard bulletin slide look */
  .pv-slide{position:relative;height:250px;border-radius:14px;overflow:hidden;border:1px solid var(--border);display:flex;align-items:flex-end;background:var(--charcoal-3)}
  .pv-slide .pvbg{position:absolute;inset:0}
  .pv-slide.g1 .pvbg{background:linear-gradient(120deg,#3a2a16,#191510)}
  .pv-slide.g2 .pvbg{background:linear-gradient(120deg,#2c2617,#181712)}
  .pv-slide.g3 .pvbg{background:linear-gradient(120deg,#33211a,#191310)}
  .pv-slide .pvbg img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .pv-slide .pvbg::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,11,10,.1),rgba(12,11,10,.55) 55%,rgba(12,11,10,.95))}
  /* Matches the dashboard: the image fades out behind the caption, and
     the fade is on .cap so it begins where the text begins. */
  .pv-slide.has-img .pvbg::after{background:
    linear-gradient(180deg,rgba(10,9,8,.30) 0%,rgba(10,9,8,.08) 38%,rgba(10,9,8,.30) 100%)}
  .pv-slide.has-img .cap{padding-top:54px;
    background:linear-gradient(to top,
      rgba(10,9,8,.97) 0%, rgba(10,9,8,.95) 48%, rgba(10,9,8,.78) 72%, rgba(10,9,8,0) 100%)}
  .pv-slide .cap{position:relative;padding:22px;width:100%}
  .pv-slide .cap .tg{position:static;display:inline-block;margin-bottom:11px}
  .pv-slide .cap h4{font-size:19px;font-weight:700;letter-spacing:-.01em;margin-bottom:6px;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  /* Two lines, never more. A long description over a photo turns the slide
     into wallpaper with words on it. */
  .pv-slide .cap p{font-size:13px;line-height:1.55;color:#cdc2ad;max-width:56ch;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .pv-slide.has-img .cap h4{text-shadow:0 1px 12px rgba(0,0,0,.55)}
  .pv-slide.has-img .cap p{color:#ddd3c2;text-shadow:0 1px 10px rgba(0,0,0,.5)}
  .pv-slide .cap .m{font-size:11.5px;color:var(--text-faint);margin-top:10px;font-weight:600}
  .pv-slide.has-img .cap .m{color:#b6ab99}
  .pv-link{display:inline-flex;align-items:center;gap:6px;margin-top:11px;font-size:11.5px;font-weight:700;
    color:#1a1206;background:linear-gradient(145deg,var(--gold),var(--amber));padding:5px 11px;border-radius:100px}
  .pv-link svg{width:13px;height:13px}
  .pv-note{font-size:12px;color:var(--text-dim);margin-top:12px;line-height:1.5}


  /* ---------- ANNOUNCEMENTS ---------- */
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

  .arow{display:flex;align-items:center;gap:14px;padding:15px 16px;border:1px solid var(--border-soft);
    border-radius:13px;background:var(--charcoal-2);margin-bottom:11px}
  .arow.live{border-color:rgba(226,182,92,.4);background:linear-gradient(180deg,rgba(226,182,92,.05),transparent)}
  .arow .abody{flex:1;min-width:0}
  .arow .aline{font-size:13.5px;font-weight:600;color:var(--parchment);display:flex;align-items:center;gap:9px;flex-wrap:wrap}
  .arow .asub{font-size:12.5px;color:var(--text-dim);margin-top:5px;line-height:1.55;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .arow .ameta{font-size:11.5px;color:var(--text-faint);margin-top:7px;font-weight:600}
  .arow .aacts{display:flex;align-items:center;gap:8px;flex:none}
  .live-badge{display:inline-flex;align-items:center;gap:6px;font-size:10.5px;font-weight:800;
    letter-spacing:.1em;text-transform:uppercase;color:#1a1206;background:var(--gold);
    padding:3px 9px;border-radius:100px}
  .live-badge .dot{width:6px;height:6px;border-radius:50%;background:#1a1206}
  .tchip{font-size:10.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
    padding:3px 9px;border-radius:100px;border:1px solid var(--border);color:var(--text-dim)}
  .apreview{margin-top:8px}
  .switchrow{display:flex;align-items:center;gap:11px;margin-top:16px;font-size:13px;color:var(--text-dim)}
  .switchrow .sw{width:38px;height:21px;border-radius:100px;background:var(--charcoal-4);position:relative;
    border:1px solid var(--border);flex:none;transition:.2s}
  .switchrow .sw::after{content:"";position:absolute;left:2px;top:1.5px;width:15px;height:15px;border-radius:50%;
    background:var(--stone);transition:.2s}
  .switchrow.on .sw{background:rgba(226,182,92,.3);border-color:rgba(226,182,92,.5)}
  .switchrow.on .sw::after{left:19px;background:var(--gold)}


  /* ---------- GROUP MANAGEMENT ---------- */

  /* Tier colours live in assets/css/tones.css, loaded above — one palette
     for the whole UCP rather than a copy per page. --tone fills, --tone-ink
     reads on top of a fill, --tone-text reads on the dark background. */

  /* ---- The toolbar: pick a group, or search across all of them ---- */
  .mtools{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
  .mtools .msearch{display:flex;align-items:center;gap:10px;height:52px;padding:0 15px;
    flex:1;min-width:230px;max-width:380px;border-radius:12px;
    background:var(--charcoal-2);border:1px solid var(--border);transition:.15s}
  .mtools .msearch:focus-within{border-color:var(--charcoal-4);background:var(--charcoal-3)}
  .msearch svg{width:16px;height:16px;color:var(--text-faint);flex:none;stroke-width:2}
  .msearch input{flex:1;min-width:0;background:none;border:none;outline:none;color:var(--parchment);
    font-size:13.5px;font-family:inherit}
  .msearch input::placeholder{color:var(--text-dim)}
  .mcount{font-size:12px;color:var(--text-dim);font-weight:600;margin-left:auto;
    font-variant-numeric:tabular-nums;text-align:right}

  /* The group picker. A native <select> can't carry the tier colours, and
     the colours are the point — so this is a button and a listbox, with a
     headcount on every rung. */
  .gpick{position:relative;flex:none;width:308px;max-width:100%}
  .gpick-btn{width:100%;display:flex;align-items:center;gap:13px;height:52px;padding:0 15px;
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:12px;
    font-family:inherit;color:var(--parchment);cursor:pointer;text-align:left;transition:.15s}
  .gpick-btn:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  .gpick.open .gpick-btn{background:var(--charcoal-3);
    border-color:color-mix(in srgb, var(--tone) 55%, transparent)}
  .gdot{width:10px;height:10px;border-radius:50%;flex:none;background:var(--tone);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--tone) 24%, transparent)}
  .gpick-lbl{flex:1;min-width:0}
  .gpick-lbl .k{display:block;font-size:9.5px;font-weight:800;letter-spacing:.14em;
    text-transform:uppercase;color:var(--text-dim);line-height:1}
  .gpick-lbl .v{display:block;font-size:14.5px;font-weight:700;letter-spacing:-.01em;margin-top:4px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1}
  .gpick-cnt{flex:none;font-size:11.5px;font-weight:700;color:var(--text-faint);
    font-variant-numeric:tabular-nums;background:var(--charcoal);border:1px solid var(--border);
    padding:4px 9px;border-radius:100px}
  .gpick-chev{width:16px;height:16px;flex:none;color:var(--text-faint);stroke-width:2.2;transition:transform .18s}
  .gpick.open .gpick-chev{transform:rotate(180deg)}

  .gmenu{position:absolute;z-index:60;left:0;right:0;top:calc(100% + 7px);padding:6px;
    background:var(--charcoal-2);border:1px solid var(--border);border-radius:13px;
    box-shadow:0 26px 54px -20px rgba(0,0,0,.85);max-height:min(62vh,420px);overflow-y:auto}
  .gmenu[hidden]{display:none}
  .gopt{display:flex;align-items:center;gap:12px;width:100%;padding:10px 11px;border-radius:9px;
    background:none;border:none;cursor:pointer;font-family:inherit;font-size:13.5px;font-weight:600;
    color:var(--text-faint);text-align:left;transition:.12s}
  .gopt:hover{background:var(--charcoal-3);color:var(--parchment)}
  .gopt.on{color:var(--parchment);background:color-mix(in srgb, var(--tone) 15%, transparent)}
  .gopt .gn{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .gopt .gc{flex:none;font-size:11.5px;font-weight:700;color:var(--text-dim);font-variant-numeric:tabular-nums}
  .gopt.zero .gn,.gopt.zero .gc{color:var(--text-dim)}
  .gopt.zero .gdot{opacity:.4;box-shadow:none}
  .gmenu .gsep{height:1px;background:var(--border-soft);margin:5px 9px}

  /* Search scope bar — only shown when a search is spanning every group,
     because the picker already says which group is open. */
  .mscope{display:flex;align-items:center;gap:12px;padding:11px 15px;margin-bottom:12px;
    border-radius:11px;background:var(--charcoal-2);border:1px solid var(--border-soft)}
  .mscope .txt{flex:1;min-width:0;font-size:13px;color:var(--text-dim)}
  .mscope .txt b{color:var(--parchment);font-weight:700}

  /* ---- The member row -------------------------------------------------
     Left is what you READ, right is what you CHANGE. The reading side is a
     record card: name, group, then three labelled lines — Timeline, Security,
     Linking — with sub-groups in their own column against the Access panel.
     -------------------------------------------------------------------- */
  .mrow{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 318px;
    margin-bottom:9px;border:1px solid var(--border-soft);border-radius:14px;
    background:var(--charcoal-2);overflow:hidden;transition:border-color .15s, background .15s}
  .mrow::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--tone);z-index:1}
  .mrow:hover{border-color:var(--border)}
  .mrow.is-you{border-color:rgba(226,182,92,.32)}

  .mbody{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:stretch;min-width:0}
  .mleft{display:flex;flex-direction:column;justify-content:center;gap:14px;
    padding:16px 20px 16px 22px;min-width:0}

  /* ---- identity ----
     The group sits on its own line under the name rather than beside it: it
     is a property of the person, not part of what they are called, and a
     twenty-character UCP name can't push it off the row. */
  .mname{display:flex;align-items:baseline;gap:9px;min-width:0}
  .mname .nm{font-size:17px;font-weight:700;letter-spacing:-.015em;color:var(--parchment);
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-decoration:none;transition:color .13s}
  /* The name is the way into the account. Underlined only on hover, because a
     list of twenty underlined names is a list nobody can read. */
  a.nm:hover{color:var(--gold);text-decoration:underline;text-underline-offset:3px}
  .mname .uid{font-size:12px;font-weight:600;color:var(--text-dim);font-variant-numeric:tabular-nums;flex:none}
  .msub{display:flex;align-items:center;gap:8px;margin-top:8px;flex-wrap:wrap}

  .mtag{font-size:9.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;
    padding:3px 8px;border-radius:100px;border:1px solid var(--border);color:var(--text-faint);white-space:nowrap}
  .mtag.you{color:var(--gold);border-color:rgba(226,182,92,.42);background:rgba(226,182,92,.09)}
  /* Suspended is a decision somebody made; pending is a letter nobody has
     opened. Different colours, because they need different reactions. */
  .mtag.bad{color:#d29b8d;border-color:rgba(193,85,63,.4);background:rgba(193,85,63,.09)}
  .mtag.wait{color:#c2a878;border-color:rgba(212,146,58,.34);background:rgba(212,146,58,.07)}

  /* ---- the three lines ---- */
  .mrows{display:flex;flex-direction:column}
  .mline{display:grid;grid-template-columns:82px minmax(0,1fr);gap:16px;align-items:center;
    padding:9px 0;border-top:1px solid var(--border-soft)}
  .mline:first-child{border-top:none;padding-top:0}
  .mline:last-child{padding-bottom:0}
  /* Sentence case at a readable size. The old 8.5px all-caps against a
     12.5px value was two type systems stacked on one line. */
  .mline .k{font-size:11.5px;font-weight:600;color:var(--text-dim);line-height:1}
  .mline .v{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;
    color:var(--parchment);min-width:0;flex-wrap:wrap}
  .mline .v .q{color:var(--text-faint);font-weight:500}
  .mline .v .sep{width:1px;height:14px;background:var(--border);flex:none;margin:0 3px}

  /* ---- state marks ----
     Deliberately quiet: these are indicators on a dark page, not buttons.
     They only have to be told apart from each other and from off. */
  .mk{display:inline-flex;align-items:center;gap:7px;height:26px;padding:0 10px;border-radius:8px;
    font-size:11.5px;font-weight:600;white-space:nowrap;
    background:var(--charcoal);border:1px solid var(--border);color:var(--text-dim)}
  .mk svg{width:12px;height:12px;stroke-width:2;flex:none;opacity:.9}
  .mk.on{color:#9fae8d;border-color:rgba(127,160,90,.24);background:rgba(127,160,90,.06)}
  .mk.on.forum{color:#b6a382;border-color:rgba(212,146,58,.24);background:rgba(212,146,58,.06)}
  .mk.on.dis{color:#9298b5;border-color:rgba(123,132,200,.26);background:rgba(123,132,200,.07)}
  /* --border-soft is a divider colour: right for a hairline between rows,
     invisible as the outline of a chip. An off mark still has to read as a
     thing with an edge, or "No Discord" looks like stray text. */
  .mk.off{color:var(--text-dim);background:var(--charcoal);border-color:var(--charcoal-4)}
  .mk.off svg{opacity:.55}

  /* ---- sub-groups ----
     Absent entirely when none are held — no heading, no placeholder, no
     divider. The card takes the width back. */
  .sgcol{display:flex;flex-direction:column;justify-content:center;align-items:flex-end;gap:8px;
    padding:15px 18px 15px 22px;border-left:1px solid var(--border-soft);text-align:right}
  .sgcol .k{font-size:9px;font-weight:800;letter-spacing:.15em;text-transform:uppercase;
    color:var(--text-dim);line-height:1;margin-bottom:1px}
  .sgrow{display:flex;flex-direction:row-reverse;align-items:center;gap:9px;font-size:12.5px;
    font-weight:600;color:var(--parchment);white-space:nowrap}
  .sgrow .dot{width:5px;height:5px;border-radius:50%;background:var(--gold);flex:none}
  .sgrow.soon{color:var(--text-faint)}
  .sgrow.soon .dot{background:var(--stone)}

  /* ---- the Access panel ----
     Recessed rather than raised: a well cut into the card, which is what
     makes "everything in here changes the account" read without a border
     shouting it. */
  .maccess{display:flex;flex-direction:column;gap:9px;padding:15px 17px;
    background:rgba(0,0,0,.22);border-left:1px solid var(--border-soft)}
  .maccess .ahead{display:flex;align-items:center;gap:8px;font-size:9px;font-weight:800;
    letter-spacing:.14em;text-transform:uppercase;color:var(--text-dim);line-height:1}
  .maccess .ahead svg{width:13px;height:13px;stroke-width:2;color:var(--stone);flex:none}
  .maccess .agrp{display:flex;gap:8px}
  .maccess select{flex:1;min-width:0;height:36px;padding:0 32px 0 12px;border-radius:10px;
    background:var(--charcoal);border:1px solid var(--border);color:var(--parchment);
    font-size:12.5px;font-family:inherit;appearance:none;cursor:pointer;transition:.14s;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%238a7f70' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 10px center}
  .maccess select:hover{border-color:var(--charcoal-4)}
  .maccess select:focus{outline:none;border-color:rgba(226,182,92,.5);box-shadow:0 0 0 3px rgba(212,146,58,.13)}
  .maccess .btn{flex:none}
  .maccess .btn:disabled{opacity:.38;cursor:not-allowed;filter:saturate(.5)}
  .anote{font-size:11.5px;color:var(--text-dim);line-height:1.5}
  .alock{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;font-weight:600;
    color:var(--text-faint);background:var(--charcoal);border:1px solid var(--border);
    padding:9px 12px;border-radius:10px}
  .alock svg{width:13px;height:13px;stroke-width:2;flex:none}

  /* A checkbox, not a pill: it has an off state you can see. */
  .aticks{display:flex;flex-direction:column;gap:1px;margin:1px -4px 0}
  .ck{display:flex;align-items:center;gap:10px;width:100%;padding:4px 8px;border-radius:8px;
    background:transparent;border:1px solid transparent;cursor:pointer;transition:.13s;
    font-family:inherit;font-size:12.5px;font-weight:600;color:var(--text-faint);text-align:left;
    white-space:nowrap}
  .ck:hover:not(:disabled){background:var(--charcoal-3);color:var(--parchment)}
  .ck .box{width:16px;height:16px;border-radius:5px;flex:none;border:1.5px solid var(--charcoal-4);
    display:grid;place-items:center;transition:.14s}
  .ck .box svg{width:11px;height:11px;stroke-width:3;color:#1a1206;opacity:0;transition:.12s}
  .ck.on{color:var(--parchment)}
  .ck.on .box{background:linear-gradient(145deg,var(--gold),var(--amber));border-color:transparent}
  .ck.on .box svg{opacity:1}
  /* A sub-group that grants nothing yet still assigns — a real record, just
     not a gold one. */
  .ck.soon.on .box{background:var(--stone);border-color:transparent}
  .ck.soon.on .box svg{color:#16130f}
  .ck .tail{margin-left:auto;font-size:8.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
    color:var(--text-dim);border:1px solid var(--border-soft);border-radius:100px;padding:2px 6px;flex:none}
  .ck:disabled{opacity:.42;cursor:not-allowed}
  .ck.busy{opacity:.5;cursor:progress}

  .pcount{font-size:12.5px;color:var(--text-faint);font-weight:600;font-variant-numeric:tabular-nums}

  @media (max-width:1120px){
    .mrow{grid-template-columns:minmax(0,1fr)}
    .maccess{border-left:none;border-top:1px solid var(--border-soft);
      flex-direction:row;flex-wrap:wrap;align-items:center;gap:12px}
    .maccess .ahead{width:100%}
    .maccess .agrp{flex:none;width:auto}
    .maccess select{width:170px;flex:none}
    .aticks{flex-direction:row;flex-wrap:wrap;gap:6px;margin:0}
    .ck{width:auto;border:1px solid var(--border);background:var(--charcoal)}
    .anote{width:100%}
  }
  @media (max-width:820px){
    .mbody{grid-template-columns:minmax(0,1fr)}
    .sgcol{border-left:none;border-top:1px solid var(--border-soft);align-items:flex-start;
      text-align:left;padding:13px 20px 13px 22px}
    .sgrow{flex-direction:row}
  }
  @media (max-width:620px){
    .mleft{padding:15px 15px 15px 19px}
    .mline{grid-template-columns:minmax(0,1fr);gap:7px}
    .maccess{flex-direction:column;align-items:stretch}
    .maccess select{width:auto;flex:1}
    .ck{width:100%}
  }

  /* toast */
  .toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);z-index:200;
    display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:11px;background:var(--charcoal-3);
    border:1px solid var(--border);box-shadow:0 18px 44px -14px rgba(0,0,0,.75);font-size:13.5px;font-weight:600;
    color:var(--parchment);opacity:0;pointer-events:none;transition:.28s}
  .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
  .toast svg{width:17px;height:17px;color:var(--ok);stroke-width:2.4}

  /* pager — centered */
  .pager{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:4px}
  .pager .pg{min-width:36px;height:36px;padding:0 11px;display:grid;place-items:center;border-radius:9px;
    background:var(--charcoal-2);border:1px solid var(--border);color:var(--text-faint);
    font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:.14s}
  .pager .pg:hover:not(:disabled){color:var(--parchment);background:var(--charcoal-3)}
  .pager .pg.on{background:var(--charcoal-3);color:var(--parchment);border-color:var(--charcoal-4)}
  .pager .pg:disabled{opacity:.4;cursor:not-allowed}
  .pager .pg svg{width:15px;height:15px;stroke-width:2.2}
  .pager .pg-gap{color:var(--text-dim);padding:0 2px}

  .view{display:none}
  .view.active{display:flex;flex-direction:column;gap:22px}

  @media (max-width:1000px){ .editor{grid-template-columns:1fr} .preview-wrap{position:static} }
  @media (max-width:760px){
    .sidebar{position:fixed;left:0;top:0;height:100dvh;transform:translateX(-100%);transition:transform .26s ease}
    .side-inner{position:static;height:100dvh}
    body.nav-open .sidebar{transform:translateX(0);box-shadow:0 0 60px rgba(0,0,0,.6)}
    .hamburger{display:grid}
    .topbar{padding:0 14px;gap:10px}
    .searchbox{display:none}
    .search-mini{display:grid}
    .divider{display:none}
    .account-btn{min-width:0;padding:9px 11px;gap:6px}
    .account-meta{display:none}
    .account-btn .acct-ico{display:block;width:18px;height:18px;color:var(--text-faint)}
    .content{padding:18px 14px 32px}
    .phead h2{font-size:20px}
    .grid{grid-template-columns:1fr}
    .slotbar .track{order:4;flex-basis:100%}
    .slotbar .note{flex-basis:100%;order:5}
  }
  .scrim{display:none;position:fixed;inset:0;background:rgba(8,7,6,.6);backdrop-filter:blur(2px);z-index:48;opacity:0;transition:opacity .22s}
  .scrim.show{display:block;opacity:1}
  @media (prefers-reduced-motion:reduce){*{animation-duration:.001ms!important;transition-duration:.001ms!important}}
</style>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <!-- ============ LISTING VIEW ============ -->
      <!-- ============ LISTING ============ -->
      <div class="view active" id="view-list">
        <a class="page-back" href="/dashboard"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg>Back to dashboard</a>

        <div class="phead">
          <div>
            <h2>Group Management</h2>
            <p id="introLine">Promote and demote accounts. Every change is written to that account's security log.</p>
          </div>
        </div>

        <div class="mtools">
          <div class="gpick tone-0" id="gpick"></div>
          <label class="msearch">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <input type="text" id="q" placeholder="Search staff by part of a name, or a Member by their full name…" autocomplete="off" spellcheck="false">
          </label>
          <span class="mcount" id="mcount"></span>
        </div>

        <div id="mscope"></div>
        <div id="mlist"></div>
        <div class="pager" id="pager"></div>
      </div>

    </main>
  </div>

  <div class="toast" id="toast"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6L9 17l-5-5"/></svg><span id="toastMsg">Saved</span></div>

<script src="/assets/js/ucp.js?v=3.0.2"></script>
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

  /* ===================== BULLETIN DATA =====================
     Each bulletin: {id, type, title, body, by, when, link (url|null),
     image (dataURL|null), imgpos (0-100 vertical %), shown (on dashboard)}.
     MAX_SHOWN caps how many rotate on the dashboard.
     `by` is always the UCP name of whoever created it (locked). Wire to server later. */

  function checkLink(raw){
    const v=(raw||'').trim();
    if(!v) return {url:null, ok:true, msg:''};
    let url=v;
    if(!/^https?:\/\//i.test(url)) url='https://'+url;  /* assume https if scheme omitted */
    try{
      const u=new URL(url);
      if(!u.hostname.includes('.')) return {url:null, ok:false, msg:'That doesn\u2019t look like a valid web address.'};
      return {url:u.href, ok:true, msg:'Players who click this bulletin will open '+u.hostname};
    }catch(_){ return {url:null, ok:false, msg:'That doesn\u2019t look like a valid web address.'}; }
  }



  /* =====================================================================
     GROUP MANAGEMENT

     Resting state is the picker: ten groups, a headcount each. Nothing is
     listed until a group is picked or a name is searched — page one of every
     account in the UCP is not an answer to any question somebody came here
     with.

     The rules are the server's (api/_groups.php); this page asks what it is
     allowed to offer and renders that. A row it can't touch says why rather
     than being greyed out — "you can't" is not an explanation.
     ===================================================================== */
  let RANKS=[], ASSIGNABLE=[], YOU={id:0,rank:0};
  let TEAMS=[], TEAMS_OK=false, TEAM_BAND=null;
  let ITEMS=[], TOTAL_PAGES=1, curPage=1, QUERY='', GROUP=null, LOADING=false;

  function rankName(r){ return (RANKS.find(x=>x.rank===r)||{}).name||('Group '+r); }

  /* Dates arrive as UTC SQL strings; times as relative, with the exact value
     on hover — "3 days ago" is what you scan for, the timestamp is what you
     quote in a report. */
  function toTs(sql){
    if(!sql) return null;
    const m=String(sql).match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
    return m ? Date.UTC(+m[1],+m[2]-1,+m[3],+m[4],+m[5],+m[6])/1000 : null;
  }
  const MON=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  function dateOf(sql){
    const t=toTs(sql); if(!t) return '—';
    const d=new Date(t*1000);
    return d.getUTCDate()+' '+MON[d.getUTCMonth()]+' '+d.getUTCFullYear();
  }
  function agoOf(sql){
    const t=toTs(sql); if(!t) return 'never';
    if(window.UCP && UCP.relTime) return UCP.relTime(t);
    return dateOf(sql);
  }
  function exactOf(sql){
    const t=toTs(sql); if(!t) return 'Never logged in';
    const d=new Date(t*1000), p=n=>String(n).padStart(2,'0');
    return dateOf(sql)+', '+p(d.getUTCHours())+':'+p(d.getUTCMinutes())+' UTC';
  }

  /* ---- the group picker ----
     RANKS arrives low-to-high; the menu shows it high-to-low, because the
     groups people come here to hand out are at the top of the ladder. */
  function toneOf(r){ return 'tone-' + (r >= 0 && r <= 9 ? r : 0); }

  let PICK_OPEN = false;

  function renderPicker(){
    const host = document.getElementById('gpick');
    const cur  = GROUP === null ? null : RANKS.find(r => r.rank === GROUP);
    const all  = RANKS.reduce((a,r) => a + (r.count|0), 0);

    host.className = 'gpick ' + (cur ? toneOf(cur.rank) : 'tone-0') + (PICK_OPEN ? ' open' : '');
    host.innerHTML =
      '<button class="gpick-btn" type="button" id="gpickBtn" aria-haspopup="listbox" aria-expanded="' + PICK_OPEN + '">' +
        '<span class="gdot"></span>' +
        '<span class="gpick-lbl"><span class="k">Group</span>' +
          '<span class="v">' + (cur ? escapeHtml(cur.name) : 'All groups') + '</span></span>' +
        '<span class="gpick-cnt">' + (cur ? cur.count : all).toLocaleString('en-GB') + '</span>' +
        '<svg class="gpick-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9l6 6 6-6"/></svg>' +
      '</button>' +
      '<div class="gmenu" role="listbox" ' + (PICK_OPEN ? '' : 'hidden') + '>' +
        '<button class="gopt tone-0 ' + (GROUP === null ? 'on' : '') + '" type="button" role="option" data-pick="all">' +
          '<span class="gdot"></span><span class="gn">All groups</span>' +
          '<span class="gc">' + all.toLocaleString('en-GB') + '</span></button>' +
        '<div class="gsep"></div>' +
        RANKS.slice().reverse().map(function(r){
          return '<button class="gopt ' + toneOf(r.rank) + ' ' + (GROUP === r.rank ? 'on' : '') +
                 (r.count ? '' : ' zero') + '" type="button" role="option" data-pick="' + r.rank + '">' +
            '<span class="gdot"></span><span class="gn">' + escapeHtml(r.name) + '</span>' +
            '<span class="gc">' + (r.count|0).toLocaleString('en-GB') + '</span></button>';
        }).join('') +
      '</div>';
  }

  /* Opening and closing only touches classes — it does NOT re-render.
     Re-rendering here detaches the very button that was clicked, and the
     document-level "click outside" handler below then sees a node with no
     parent, decides the click was outside, and shuts the menu again the
     instant it opens. */
  function setPickOpen(v){
    PICK_OPEN = v;
    const host = document.getElementById('gpick');
    host.classList.toggle('open', v);
    const menu = host.querySelector('.gmenu'), btn = host.querySelector('#gpickBtn');
    if(menu) menu.hidden = !v;
    if(btn)  btn.setAttribute('aria-expanded', String(v));
  }

  document.getElementById('gpick').addEventListener('click', function(e){
    const opt = e.target.closest('[data-pick]');
    if(opt){
      const v = opt.dataset.pick;
      GROUP = (v === 'all') ? null : +v;
      QUERY = ''; document.getElementById('q').value = '';
      setPickOpen(false);
      loadPage(1);
      return;
    }
    if(e.target.closest('#gpickBtn')) setPickOpen(!PICK_OPEN);
  });
  /* Clicking anywhere else, or Escape, closes it. */
  document.addEventListener('click', function(e){
    if(PICK_OPEN && !e.target.closest('#gpick')) setPickOpen(false);
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && PICK_OPEN) setPickOpen(false);
  });

  const ICO_LOCK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>';
  const ICO_TICK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6L9 17l-5-5"/></svg>';
  const ICO_SHIELD= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/></svg>';
  const ICO_SHOK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>';
  const ICO_FORUM = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v11H8l-4 3z"/></svg>';
  const ICO_DIS   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 11a1 1 0 1 0 0-.01M16 11a1 1 0 1 0 0-.01"/><path d="M8.5 17c-2 0-3.5-1.2-4-3.5C4 10 5 6.8 6.5 5.5 7.4 5 8.6 4.7 9.5 4.6l.6 1.2a12 12 0 0 1 3.8 0l.6-1.2c.9.1 2.1.4 3 .9C19 6.8 20 10 19.5 13.5c-.5 2.3-2 3.5-4 3.5l-.9-1.5"/></svg>';

  /** Unix seconds → "3 days ago", with the exact stamp on hover. */
  function seenAgo(ts){
    if(!ts) return null;
    return (window.UCP && UCP.relTime) ? UCP.relTime(ts) : new Date(ts*1000).toUTCString();
  }
  function seenExact(ts){
    if(!ts) return 'No activity on the UCP yet';
    const d=new Date(ts*1000), p=n=>String(n).padStart(2,'0');
    return d.getUTCDate()+' '+MON[d.getUTCMonth()]+' '+d.getUTCFullYear()+', '+
           p(d.getUTCHours())+':'+p(d.getUTCMinutes())+' UTC';
  }
  /** Whole days between a SQL date and now. */
  function daysSince(sql){
    const t=toTs(sql); if(!t) return null;
    return Math.max(0, Math.floor((Date.now()/1000 - t) / 86400));
  }

  function rowHTML(m){
    /* Suspended is a decision somebody made; pending is a confirmation email
       nobody has opened. Different states, different colours. */
    const flags =
      (m.self ? '<span class="mtag you">You</span>' : '') +
      (m.status === 'suspended' ? '<span class="mtag bad">Banned</span>' : '') +
      (m.status === 'locked'    ? '<span class="mtag bad">Locked</span>' : '') +
      (m.status === 'pending'   ? '<span class="mtag wait">Pending email</span>' : '');

    const days  = daysSince(m.created_at);
    const seen  = seenAgo(m.last_seen);

    const timeline =
      'Joined ' + escapeHtml(dateOf(m.created_at)) +
      (days !== null ? ' <span class="q">· ' + days + (days === 1 ? ' day' : ' days') + '</span>' : '') +
      '<span class="sep"></span>Last active ' +
      (seen ? '<span title="' + escapeHtml(seenExact(m.last_seen)) + '">' + escapeHtml(seen) + '</span>'
            : '<span class="q">never</span>');

    const security = m.twofa
      ? '<span class="mk on">' + ICO_SHOK + 'Two-step on</span>'
      : '<span class="mk off">' + ICO_SHIELD + 'No two-step</span>';

    const linking =
      (m.forum ? '<span class="mk on forum">' + ICO_FORUM + 'Forum</span>'
               : '<span class="mk off">' + ICO_FORUM + 'No forum</span>') +
      (m.discord ? '<span class="mk on dis">' + ICO_DIS + 'Discord</span>'
                 : '<span class="mk off">' + ICO_DIS + 'No Discord</span>');

    return '<div class="mrow ' + toneOf(m.rank) + (m.self ? ' is-you' : '') + '" data-id="' + m.id + '">' +
      '<div class="mbody">' +
        '<div class="mleft">' +
          '<div>' +
            '<div class="mname"><a class="nm" href="/dashboard/lookup?id=' + m.id + '"' +
              ' title="Open ' + escapeHtml(m.name) + '\u2019s account">' + escapeHtml(m.name) + '</a>' +
              '<span class="uid">#' + m.id + '</span></div>' +
            '<div class="msub"><span class="gchip">' + escapeHtml(m.role) + '</span>' + flags + '</div>' +
          '</div>' +
          '<div class="mrows">' +
            '<div class="mline"><span class="k">Timeline</span><span class="v">' + timeline + '</span></div>' +
            '<div class="mline"><span class="k">Security</span><span class="v">' + security + '</span></div>' +
            '<div class="mline"><span class="k">Linking</span><span class="v">' + linking + '</span></div>' +
          '</div>' +
        '</div>' +
        subgroupsHTML(m) +
      '</div>' +
      '<div class="maccess">' + accessHTML(m) + '</div>' +
    '</div>';
  }

  /** The read-only sub-groups column — nothing at all when none are held. */
  function subgroupsHTML(m){
    const held = teamOrder(m.teams || []);
    if(!TEAMS.length || !m.team_eligible || !held.length) return '';
    return '<div class="sgcol"><span class="k">Sub-groups</span>' +
      held.map(function(k){
        const t = teamBy(k);
        return '<span class="sgrow ' + (t && t.live ? '' : 'soon') + '">' +
               '<span class="dot"></span>' + escapeHtml(teamLabel(k)) + '</span>';
      }).join('') + '</div>';
  }

  /** Everything that changes this account, in one panel. */
  function accessHTML(m){
    const head = '<span class="ahead">' + ICO_LOCK + 'Access</span>';

    if(!m.editable){
      return head +
        '<span class="alock" title="' + escapeHtml(m.blocked_by || '') + '">' + ICO_LOCK +
          (m.self ? 'Your own group' : 'Founder only') + '</span>' +
        '<span class="anote">' + escapeHtml(m.blocked_by || '') + '</span>';
    }

    const opts = ASSIGNABLE.map(function(r){
      return '<option value="' + r + '"' + (r === m.rank ? ' selected' : '') + '>' +
             escapeHtml(rankName(r)) + '</option>';
    }).join('');
    /* Someone whose group is above what this actor may hand out still needs
       their group SHOWN, so it goes in as a disabled option. */
    const missing = !ASSIGNABLE.includes(m.rank)
      ? '<option value="' + m.rank + '" selected disabled>' + escapeHtml(m.role) + '</option>' : '';

    let out = head +
      '<div class="agrp">' +
        '<select data-rank="' + m.id + '" aria-label="Group for ' + escapeHtml(m.name) + '">' + missing + opts + '</select>' +
        '<button class="btn sm primary" data-apply="' + m.id + '" disabled>Apply</button>' +
      '</div>';

    if(!TEAMS.length) return out;
    if(!m.team_eligible) return out + '<span class="anote">' + escapeHtml(m.team_why || '') + '</span>';
    if(!TEAMS_OK) return out + '<span class="anote">Sub-groups need docs/migration-subgroups.sql running first.</span>';

    const held = m.teams || [];
    out += '<div class="aticks">' + TEAMS.map(function(t){
      const on = held.indexOf(t.key) > -1;
      const title = t.live ? (t.blurb + ' ' + (t.grants || []).join(' ')) : (t.why || t.blurb);
      return '<button class="ck ' + (on ? 'on ' : '') + (t.live ? '' : 'soon') + '"' +
        ' data-team="' + t.key + '" data-for="' + m.id + '" aria-pressed="' + on + '"' +
        ' title="' + escapeHtml(title) + '">' +
        '<span class="box">' + ICO_TICK + '</span>' + escapeHtml(t.label) +
        (t.live ? '' : '<span class="tail">Soon</span>') +
      '</button>';
    }).join('') + '</div>';

    return out;
  }

  /* Registry order, not whatever order the database handed them back in. */
  function teamOrder(keys){
    return TEAMS.map(function(t){ return t.key; }).filter(function(k){ return keys.indexOf(k) > -1; });
  }
  function teamBy(key){
    for(var i=0;i<TEAMS.length;i++) if(TEAMS[i].key === key) return TEAMS[i];
    return null;
  }
  /** Label for a sub-group key, falling back to the key itself. */
  function teamLabel(key){
    const t = teamBy(key);
    return t ? t.label : key;
  }

  /* The picker already names the open group, so the scope bar only earns
     its place when a search is spanning all of them. */
  function renderScope(d){
    const host = document.getElementById('mscope');
    if(!d.listed || !QUERY){ host.innerHTML = ''; return; }
    host.innerHTML =
      '<div class="mscope"><div class="txt">Searching every group for <b>' + escapeHtml(QUERY) + '</b>' +
      ' \u2014 ' + d.total + ' ' + (d.total === 1 ? 'match' : 'matches') + '</div>' +
      '<button class="btn sm ghost" data-clear>Clear</button></div>';
    host.querySelector('[data-clear]').onclick = function(){
      GROUP = null; QUERY = ''; document.getElementById('q').value = ''; loadPage(1);
    };
  }

  function renderList(d){
    const host = document.getElementById('mlist');

    if(!d.listed){
      /* Two different nothings: no group chosen yet, and a group that can't
         be listed at all. The second one has to say why or it reads as a
         broken page. */
      const icon = d.reason
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>';
      host.innerHTML = '<div class="empty">' +
        '<span class="ei">' + icon + '</span>' +
        '<h4>' + (d.reason ? 'Search for them by name' : 'Pick a group') + '</h4>' +
        '<p>' + (d.reason ? escapeHtml(d.reason)
              : 'Choose one from the list above to see who\u2019s in it, or search for someone by name across every group.') + '</p>' +
        '</div>';
      document.getElementById('pager').innerHTML = '';
      return;
    }
    if(!ITEMS.length){
      host.innerHTML = '<div class="empty">' +
        '<span class="ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></span>' +
        '<h4>Nobody here</h4>' +
        '<p>' + (QUERY
              ? 'Nothing matches \u201c' + escapeHtml(QUERY) + '\u201d. Members are only found by their '
                + 'full UCP name \u2014 a partial one finds staff only.'
              : 'This group is empty.') + '</p>' +
        '</div>';
      document.getElementById('pager').innerHTML = '';
      return;
    }
    host.innerHTML = ITEMS.map(rowHTML).join('');
    renderPager(d);
  }

  function renderPager(d){
    const pager=document.getElementById('pager');
    if(TOTAL_PAGES<=1){
      pager.innerHTML = d.total>0 ? `<span class="pcount">${d.total} ${d.total===1?'account':'accounts'}</span>` : '';
      return;
    }
    let nums='';
    for(let i=1;i<=TOTAL_PAGES;i++){
      if(i===1||i===TOTAL_PAGES||Math.abs(i-curPage)<=1) nums+=`<button class="pg ${i===curPage?'on':''}" data-pg="${i}">${i}</button>`;
      else if(Math.abs(i-curPage)===2) nums+=`<span class="pg-gap">…</span>`;
    }
    pager.innerHTML=
      `<span class="pcount">${d.from}–${d.to} of ${d.total.toLocaleString('en-GB')}</span>`+
      `<button class="pg" data-pg="${curPage-1}" ${curPage===1?'disabled':''}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg></button>`+
      nums+
      `<button class="pg" data-pg="${curPage+1}" ${curPage===TOTAL_PAGES?'disabled':''}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></button>`;
  }
  document.getElementById('pager').addEventListener('click',e=>{
    const b=e.target.closest('[data-pg]'); if(!b||b.disabled) return;
    loadPage(parseInt(b.dataset.pg,10));
    document.querySelector('.content').scrollIntoView({behavior:'smooth',block:'start'});
  });

  /* Apply lights up only once the selection differs — so the button always
     means "make this change", never "save what's already true". */
  document.getElementById('mlist').addEventListener('change',e=>{
    const sel=e.target.closest('[data-rank]'); if(!sel) return;
    const m=ITEMS.find(x=>x.id===+sel.dataset.rank);
    const btn=document.querySelector(`[data-apply="${sel.dataset.rank}"]`);
    if(btn) btn.disabled = (+sel.value === m.rank);
  });

  /* A sub-group toggle saves the moment it is clicked. The whole row's list
     is sent, not the one that changed — the server sets exactly what it is
     given, so two admins clicking at once can't interleave into a state
     neither of them chose. */
  document.getElementById('mlist').addEventListener('click',e=>{
    const tg=e.target.closest('[data-team]');
    if(tg && !tg.disabled){
      const id=+tg.dataset.for;
      const row=document.querySelector('.mrow[data-id="'+id+'"]');
      const want=[];
      row.querySelectorAll('[data-team]').forEach(function(b){
        const on = (b===tg) ? !b.classList.contains('on') : b.classList.contains('on');
        if(on) want.push(b.dataset.team);
      });
      row.querySelectorAll('[data-team]').forEach(function(b){ b.classList.add('busy'); b.disabled=true; });

      UCP.post('member-teams.php',{id:id,teams:want}).then(function(res){
        const d=res.data||{};
        if(!d.ok){ toast(d.error||'That did not work'); loadPage(); return; }
        toast(d.message); loadPage();
      }).catch(function(){ toast('Could not reach the server'); loadPage(); });
      return;
    }

    const btn=e.target.closest('[data-apply]'); if(!btn||btn.disabled) return;
    const id=+btn.dataset.apply;
    const sel=document.querySelector(`[data-rank="${id}"]`); if(!sel) return;
    btn.disabled=true; btn.textContent='Applying…';
    UCP.post('member-rank.php',{id:id,rank:+sel.value}).then(function(res){
      const d=res.data||{};
      btn.textContent='Apply';
      if(!d.ok){ toast(d.error||'That did not work'); loadPage(); return; }
      toast(d.message); loadPage();
    }).catch(function(){ btn.textContent='Apply'; toast('Could not reach the server'); });
  });

  function loadPage(n){
    if(LOADING) return Promise.resolve();
    LOADING=true; curPage=n||curPage;
    const qs='page='+curPage+'&q='+encodeURIComponent(QUERY)+(GROUP===null?'':'&group='+GROUP);
    return UCP.get('members.php?'+qs).then(function(d){
      LOADING=false;
      if(!d || d.ok!==true){
        if(d && d.authenticated===false){ window.location.replace('/login?return='+encodeURIComponent('/dashboard/groups')); return; }
        toast('Could not load accounts'); return;
      }
      ITEMS=d.members||[]; TOTAL_PAGES=d.pages||1; curPage=d.page||curPage;
      RANKS=d.ranks||RANKS; ASSIGNABLE=d.assignable||ASSIGNABLE; YOU=d.you||YOU;
      TEAMS=d.teams||TEAMS; TEAMS_OK=!!d.teams_ok; TEAM_BAND=d.team_band||TEAM_BAND;
      document.getElementById('mcount').textContent =
        d.total_all.toLocaleString('en-GB')+' accounts in the UCP';
      renderPicker(); renderScope(d); renderList(d);
    }).catch(function(err){
      LOADING=false;
      console.error('[groups] load failed', err);
      toast(err && err.message ? 'Page error: ' + err.message : 'Could not reach the server');
    });
  }

  /* Debounced search — one request when they stop typing, not one per key. */
  let qTimer=null;
  document.getElementById('q').addEventListener('input',function(){
    const v=this.value.trim();
    clearTimeout(qTimer);
    qTimer=setTimeout(function(){ QUERY=v; if(v) GROUP=null; loadPage(1); },320);
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



  /* =====================================================================
     BOOT — Management and above. The endpoints check it themselves too.
     ===================================================================== */
  function lockOut(){
    document.querySelector('.content').innerHTML=`
      <div class="empty" style="margin:60px auto;max-width:520px">
        <span class="ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></span>
        <h4>Management only</h4>
        <p>Groups are managed by Management and Founders. If you think you should have access, speak to a Founder.</p>
        <a class="btn primary" href="/dashboard">Back to dashboard</a>
      </div>`;
  }

  UCP.get('session.php').then(function(d){
    if(!d || d.authenticated!==true){
      window.location.replace('/login?return='+encodeURIComponent('/dashboard/groups'));
      return;
    }
    const an=document.getElementById('acctName'), ar=document.getElementById('acctRole');
    if(an) an.textContent=d.name||'';
    if(ar) ar.textContent=d.role||'Member';
    /* Keep it for the next page load — this is what stops the flicker. */
    if(window.UCP && UCP.rememberMe) UCP.rememberMe(d);
    if((d.rank|0) < 8){ lockOut(); return; }

    const was = [IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join();
    IS_MANAGER=true; IS_FOUNDER=(d.rank|0) >= 9; IS_ADMINISTRATOR=true;
    MY_RANK = d.rank | 0; MY_TEAMS = d.teams || [];
    /* Only redraw if the seed above was wrong — redrawing identical HTML is
       what the eye reads as a flash. */
    if([IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join() !== was) renderSidebar(SIDEBAR);

    /* Say the limits out loud rather than letting people discover them by
       being refused. */
    document.getElementById('introLine').textContent = IS_FOUNDER
      ? 'Promote and demote any account. Every change is written to that account\'s security log.'
      : 'Promote and demote up to Lead Admin. Management and Founders are Founder-only, and nobody changes their own group.';

    loadPage(1);
  }).catch(function(err){
    /* A thrown error in the block above is NOT a network failure, and
       saying so sends you to check the server for a bug that is in the
       page. Say which it was, and put the detail in the console. */
    console.error('[groups] boot failed', err);
    toast(err && err.message ? 'Page error: ' + err.message : 'Could not reach the server');
  });

</script>
</body>
</html>
