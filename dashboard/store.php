<?php
/**
 * Credit Store.
 *
 * The shell — sidebar, top bar, credit box, backdrop — comes from
 * ../partials/shell-top.php and ../partials/shell-foot.php. Nothing about
 * it is repeated here.
 */
$PAGE_TITLE = 'Credit Store · BlaineSide';
$PAGE_HEADING = 'Credit Store';
$PAGE_CSS = <<<'CSS'

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

  /* ===== ACCOUNT MENU =====
     The same component as the profile page. It used to exist only there, so
     the button in the corner of every other page looked interactive and did
     nothing. */
  .account{position:relative}
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

  /* The card shell, same as the lookup and profile pages. Announcements
     draws its own list rows and never needed it, so it isn't in the shell
     this page was cut from. */
  .card{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;
    box-shadow:0 20px 44px -30px rgba(0,0,0,.9);overflow:hidden}
  .card + .card{margin-top:20px}
  .card-h{display:flex;align-items:baseline;justify-content:space-between;gap:16px;flex-wrap:wrap;
    padding:17px 22px 15px;border-bottom:1px solid var(--rule)}
  .card-h h3{font-size:15.5px;font-weight:700;letter-spacing:-.015em}
  .card-b{padding:6px 22px 18px}
  /* =====================================================================
     REPORTS, APPEALS & REFUNDS — one area, three or fewer views.

     Nothing here is built. That is the whole design problem: an area that
     is coming soon can either be hidden until it works, or it can be shown
     honestly. Hiding it means nobody knows it is coming and staff keep
     answering the same question in Discord. Showing an empty table means
     "you have no refund requests", which is a different and worse lie.

     So each view states what it will ask for, what happens afterwards, and
     that it is not switched on — and the page never draws a form, a table
     or a count it cannot stand behind.
     ===================================================================== */
  .qhead{display:flex;align-items:flex-start;gap:16px;margin-bottom:22px}
  .qhead .qi{width:44px;height:44px;flex:none;display:grid;place-items:center;border-radius:13px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--gold)}
  .qhead .qi svg{width:21px;height:21px;stroke-width:1.8}
  .qhead h1{font-size:23px;font-weight:800;letter-spacing:-.02em;line-height:1.2}
  .qhead p{font-size:13.5px;color:var(--text-faint);line-height:1.6;margin-top:6px;
    max-width:66ch;text-wrap:pretty}

  /* The view switcher. The sidebar links straight to a view, so this is a
     second way to the same place rather than the only one. */
  .qtabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
  .qtab{display:inline-flex;align-items:center;gap:9px;padding:10px 16px;border-radius:11px;
    border:1px solid var(--border);background:var(--charcoal-2);color:var(--text-dim);
    font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:.14s}
  .qtab:hover{background:var(--charcoal-3);color:var(--parchment)}
  .qtab[aria-selected="true"]{background:var(--charcoal-4);color:var(--parchment);
    border-color:rgba(226,182,92,.34);box-shadow:0 1px 0 rgba(0,0,0,.35)}
  .qtab .lk{display:inline-grid;place-items:center;width:15px;height:15px;flex:none;
    color:var(--text-faint)}
  .qtab .lk svg{width:13px;height:13px;stroke-width:2.1}

  .qgrid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start}
  @media (max-width:1040px){ .qgrid{grid-template-columns:1fr} }

  .qwhat{font-size:14.5px;font-weight:600;color:var(--parchment);line-height:1.5;
    padding-top:16px}
  .qsteps{list-style:none;margin-top:14px;display:flex;flex-direction:column;gap:0}
  .qsteps li{display:flex;gap:13px;padding:13px 0;font-size:13px;color:var(--text-faint);
    line-height:1.6;text-wrap:pretty}
  .qsteps li + li{border-top:1px solid var(--rule)}
  .qsteps .n{flex:none;width:21px;height:21px;display:grid;place-items:center;border-radius:7px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-dim);
    font-size:10.5px;font-weight:800;font-variant-numeric:tabular-nums;margin-top:1px}
  .qafter{margin-top:16px;padding:14px 16px;border-radius:11px;background:var(--charcoal);
    border:1px solid var(--border);font-size:12.5px;color:var(--text-faint);line-height:1.65;
    text-wrap:pretty}
  .qafter b{color:var(--parchment);font-weight:600}

  /* The status panel. Amber, not red: nothing is wrong, it just isn't here
     yet. The refusal panel below it is the one that gets a colour. */
  .qstate{display:flex;align-items:flex-start;gap:12px;padding:15px 17px;border-radius:12px;
    background:rgba(226,182,92,.06);border:1px solid rgba(226,182,92,.26)}
  .qstate .si{width:28px;height:28px;flex:none;display:grid;place-items:center;border-radius:9px;
    background:rgba(226,182,92,.1);border:1px solid rgba(226,182,92,.3);color:#e3bd72}
  .qstate .si svg{width:15px;height:15px;stroke-width:2}
  .qstate h4{font-size:13.5px;font-weight:700;color:#e3bd72}
  .qstate p{font-size:12.5px;color:var(--text-faint);line-height:1.6;margin-top:5px;
    text-wrap:pretty}

  .qno{display:flex;align-items:flex-start;gap:13px;padding:16px 18px;border-radius:12px;
    background:rgba(193,85,63,.07);border:1px solid rgba(193,85,63,.32)}
  .qno .si{width:30px;height:30px;flex:none;display:grid;place-items:center;border-radius:9px;
    background:rgba(193,85,63,.1);border:1px solid rgba(193,85,63,.3);color:#d29b8d}
  .qno .si svg{width:16px;height:16px;stroke-width:2}
  .qno h4{font-size:14px;font-weight:700;color:#dfa294}
  .qno p{font-size:13px;color:var(--text-faint);line-height:1.65;margin-top:5px;
    max-width:62ch;text-wrap:pretty}
  .qno .who{color:var(--parchment);font-weight:600}

  .qmeta{display:flex;flex-direction:column}
  .qmeta .row{display:flex;align-items:baseline;justify-content:space-between;gap:14px;
    padding:13px 0;font-size:13px}
  .qmeta .row + .row{border-top:1px solid var(--rule)}
  .qmeta .k{color:var(--text-faint);font-size:12.5px}
  .qmeta .v{color:var(--parchment);font-weight:600;text-align:right}
  .qmeta .v.off{color:var(--text-dim);font-weight:500}


  /* =====================================================================
     CREDIT STORE

     Five tabs, one page, chosen by the hash so a link can point at any of
     them:

       (none) / #overview  the introduction
       #credits            the price tiers
       #shop               what credits buy
       #history            what you have bought
       #support            tickets, and the only tab wired to anything

     The first three are a designed shopfront with no payment provider
     behind them. They say so, out loud, at the top of the page: a Buy
     button that silently does nothing is worse than one that admits it.
     ===================================================================== */
/* header */
.qhead{display:flex;gap:15px;align-items:center;margin-bottom:0}
.qhead .qi{width:42px;height:42px;flex:none;border-radius:12px;display:grid;place-items:center;
  background:linear-gradient(160deg,rgba(226,182,92,.2),rgba(212,146,58,.06));
  border:1px solid rgba(226,182,92,.34)}
.qhead .qi svg{width:20px;height:20px;stroke:var(--gold);fill:none;stroke-width:1.9}
.qhead h1{font-size:23px;font-weight:700;letter-spacing:-.02em}
.qhead p{font-size:13.5px;color:var(--text-faint);margin-top:4px}

/* Every gap on this page is the same 14px, set once here rather than as a
   margin on each block, so nothing can drift. */
.stack > * + *{margin-top:14px}

/* Tabs as a shelf: five labelled destinations, icon and label centred
   together on one row. */
.tabs{display:flex;gap:8px;flex-wrap:wrap}
.tab{flex:1;min-width:150px;display:flex;align-items:center;justify-content:center;gap:11px;height:52px;
  padding:0 14px;border-radius:12px;background:var(--charcoal-2);border:1px solid var(--border);
  cursor:pointer;font-family:inherit;transition:.14s;position:relative}
.tab .ic{width:30px;height:30px;flex:none;border-radius:8px;display:grid;place-items:center;
  background:var(--charcoal-3);border:1px solid var(--border-soft);color:var(--text-faint)}
.tab .ic svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.9}
.tab b{font-size:12.5px;font-weight:600;color:var(--text-faint);line-height:1}
.tab:hover{border-color:rgba(226,182,92,.24)}
.tab.on{background:var(--charcoal-3);border-color:rgba(226,182,92,.4)}
.tab.on b{color:var(--parchment)}
.tab.on .ic{color:var(--gold);border-color:rgba(226,182,92,.34);background:rgba(226,182,92,.1)}
/* Inline, not a corner badge: a number pinned to the corner of a tile
   reads as floating rather than as part of the label it counts.

   Neutral, not gold. A count is not a status — colouring it made every tab
   look like it was warning you about something. It takes the same quiet
   treatment as a closed pill.

   A single digit needs a circle, not a stadium: equal width and height,
   and the digit centred by grid rather than by padding, because padding
   cannot centre something whose width it does not know. */
.tab .n{display:grid;place-items:center;box-sizing:border-box;
  min-width:20px;height:20px;padding:0 6px;border-radius:100px;
  background:transparent;border:1px solid var(--border);
  font-size:10.5px;font-weight:800;line-height:1;color:var(--text-dim);
  font-variant-numeric:tabular-nums;text-indent:0}
.tab.on .n{color:var(--text-faint);border-color:var(--charcoal-4)}


/* =====================================================================
   PURCHASE SUPPORT

   Built out of the appeal page's own parts rather than a second set that
   looks similar: .aprow for the list, its centred status pill, .kv for
   the detail panel, .cm for a comment, .composer for the reply. A ticket
   should feel like an appeal because it is the same kind of object.
   ===================================================================== */
.phead{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:18px}
.phead > div:first-child{flex:1;min-width:260px}
.phead h2{font-size:24px;font-weight:700;letter-spacing:-.02em;margin-bottom:4px}
.phead p{font-size:13.5px;color:var(--text-faint);line-height:1.6}
.phead p b{color:var(--gold);font-weight:700}
.backb{margin-bottom:14px}

.tabbar{display:inline-flex;gap:4px;padding:5px;border-radius:13px;background:var(--charcoal-2);
  border:1px solid var(--border);margin-bottom:18px;max-width:100%;overflow-x:auto}
.tabbar .tab{flex:none;min-width:0;height:auto;
  display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:10px 17px;
  border-radius:9px;border:1px solid transparent;background:none;cursor:pointer;white-space:nowrap;
  font-size:13.5px;font-weight:600;color:var(--text-faint);font-family:inherit;transition:.15s}
.tabbar .tab svg{width:16px;height:16px;stroke-width:1.9;flex:none;color:var(--stone);fill:none;stroke:currentColor}
.tabbar .tab:hover{background:var(--charcoal-3);color:var(--parchment)}
.tabbar .tab.on{background:var(--charcoal-4);color:var(--parchment);border-color:rgba(226,182,92,.34)}
.tabbar .tab.on svg{color:var(--gold)}
.tabbar .tab .n{position:static;top:auto;right:auto}
/* The count stays neutral on the active tab too: it is a quantity, not
   a status, and gold here made every tab look like an alert. */
.tabbar .tab.on .n{color:var(--text-faint);border-color:var(--charcoal-4)}

.filters{display:inline-flex;gap:4px;padding:4px;border-radius:11px;background:var(--charcoal);
  border:1px solid var(--border);flex-wrap:wrap}
.filters button{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 13px;
  border-radius:8px;border:1px solid transparent;background:none;cursor:pointer;font-family:inherit;
  font-size:12.5px;font-weight:600;color:var(--text-faint);transition:.15s}
.filters button:hover{background:var(--charcoal-3);color:var(--parchment)}
.filters button.on{background:var(--charcoal-4);color:var(--parchment);border-color:rgba(226,182,92,.34)}
.filters button .n{display:grid;place-items:center;box-sizing:border-box;min-width:20px;height:20px;
  padding:0 6px;border-radius:100px;border:1px solid var(--border);background:transparent;
  font-size:10.5px;font-weight:800;line-height:1;color:var(--text-dim);
  font-variant-numeric:tabular-nums;text-indent:0}
.filters button.on .n{color:var(--text-faint);border-color:var(--charcoal-4)}

/* The administrative record's pager, exactly: a count on the left,
   Previous / numbers / Next on the right, above a hairline. */
.pager{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
  margin-top:17px;padding-top:15px;border-top:1px solid var(--border)}
.pcount{font-size:12px;color:var(--text-faint);font-variant-numeric:tabular-nums}
.pcount b{color:var(--parchment);font-weight:600}
.pnav{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
.pnav button{min-width:33px;height:33px;padding:0 9px;border-radius:9px;border:1px solid var(--border);
  background:var(--charcoal);color:var(--text-faint);font-family:inherit;font-size:12.5px;
  font-weight:600;cursor:pointer;display:grid;place-items:center;
  font-variant-numeric:tabular-nums;transition:.14s}
.pnav button:hover:not([disabled]){color:var(--parchment);border-color:var(--charcoal-4)}
.pnav button[aria-current="true"]{background:var(--charcoal-4);color:var(--parchment);
  border-color:rgba(226,182,92,.38)}
.pnav button[disabled]{opacity:.3;cursor:default}
@media (max-width:640px){.pager{flex-direction:column;align-items:stretch}.pnav{justify-content:center}}

/* A page rule makes .hint an inline-flex box, which turned the sentence and
   its bold N/A into two flex items with a gap between them — which is why
   the full stop sat adrift. Here it is a plain block of text. */
.card .fld .hint{display:block;background:none;border:0;padding:0;border-radius:0;
  font-size:11px;color:var(--text-dim);margin-top:8px;line-height:1.6}
.card .fld .hint b{display:inline;color:var(--text-faint);font-weight:600}

.suphead{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px}
.suphead .tabbar{margin-bottom:0}
.suphead .btn{margin-left:auto}

.searchrow{display:flex;gap:11px;align-items:center;margin-bottom:14px;flex-wrap:wrap}
/* Scoped to the ticket list. Unscoped, this hit the TOP BAR's .searchbox
   too — same class name, shared shell — and flex:1 stretched it across
   the header. */
.searchrow .searchbox{display:flex;align-items:center;gap:9px;height:40px;padding:0 14px;
  flex:1;min-width:200px;width:auto;
  border-radius:10px;border:1px solid var(--border);background:var(--charcoal);color:var(--text-dim)}
.searchrow .searchbox svg{width:15px;height:15px;flex:none;stroke-width:2;fill:none;stroke:currentColor}
.searchrow .searchbox input{flex:1;min-width:0;background:none;border:0;outline:none;padding:0;
  margin:0;border-radius:0;height:auto;box-shadow:none;color:var(--parchment);
  font-family:inherit;font-size:13.5px}
.searchbox input::placeholder{color:var(--text-dim)}

.aplist{display:flex;flex-direction:column}
.aprow{color:var(--parchment);text-decoration:none;
  display:grid;grid-template-columns:82px minmax(0,1fr) 150px 104px 14px;align-items:center;
  gap:0 16px;padding:12px 15px;border-radius:11px;background:var(--charcoal-3);
  border:1px solid var(--border-soft);cursor:pointer;transition:background .13s,border-color .13s}
.aprow + .aprow{margin-top:9px}
.aprow:hover{background:var(--charcoal-4);border-color:rgba(226,182,92,.26)}
.aprow .num{font-size:11.5px;font-weight:700;color:var(--text-dim);font-variant-numeric:tabular-nums;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aprow:hover .num{color:var(--gold)}
.aprow .t{display:block;font-size:13px;font-weight:700;line-height:1.45;
  color:var(--parchment);overflow-wrap:anywhere}
.aprow .s{display:block;font-size:11.5px;color:var(--text-dim);margin-top:3px;line-height:1.5}
.aprow .s b{color:var(--text-faint);font-weight:600}
.aprow .s .dot{padding:0 5px;opacity:.55}
.aprow .tcat{font-size:12px;color:var(--text-faint);text-align:right;overflow:hidden;
  text-overflow:ellipsis;white-space:nowrap}
.aprow .go{width:14px;height:14px;stroke-width:2.4;stroke:var(--text-dim);fill:none;justify-self:end;
  transition:stroke .12s,transform .12s}
.aprow:hover .go{stroke:var(--gold);transform:translateX(2px)}
@media (max-width:900px){
  .aprow{grid-template-columns:minmax(0,1fr) auto;gap:8px 12px}
  .aprow .num,.aprow .cat,.aprow .go{display:none}
}

/* One pill, three words, centred in its own box. */
.pill,.ap{display:inline-flex;align-items:center;justify-content:center;text-align:center;
  font-size:11px;font-weight:800;letter-spacing:.02em;text-transform:uppercase;line-height:1;
  padding:6px 12px;border-radius:100px;white-space:nowrap;border:1px solid var(--border);
  background:var(--charcoal-3);color:var(--text-faint)}
.aprow .ap{justify-self:stretch;padding:6px 0}
.pill.open,.ap.open{color:#e3bd72;background:rgba(226,182,92,.1);border-color:rgba(226,182,92,.32)}
.pill.answered,.ap.answered{color:#9fae8d;background:rgba(127,160,90,.11);border-color:rgba(127,160,90,.32)}
/* Closed is the quiet state. It takes the row's own surface rather than a
   darker one, so it blends instead of announcing itself. */
.pill.closed,.ap.closed{background:transparent;border-color:var(--border);color:var(--text-dim)}

.plink{color:inherit;text-decoration:none;border-bottom:1px solid rgba(226,182,92,.35);transition:.13s}
.plink:hover{color:var(--gold);border-bottom-color:var(--gold)}

.apgrid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start}
@media (max-width:1050px){.apgrid{grid-template-columns:minmax(0,1fr)}}

.kv{display:flex;flex-direction:column}
.kv .r{padding:12px 0;font-size:13px}
.kv .r + .r{border-top:1px solid var(--border-soft)}
.kv .k{font-size:12px;color:var(--text-dim)}
.kv .v{color:var(--parchment);font-weight:600;margin-top:4px;overflow-wrap:anywhere}
.kv .v.soft{color:var(--text-faint);font-weight:500}

.cm{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--charcoal-2)}
.cm + .cm{margin-top:11px}
.cm .h{display:flex;align-items:center;gap:9px;flex-wrap:wrap;padding:10px 15px;
  background:var(--charcoal-3);border-bottom:1px solid var(--border);font-size:12.5px;color:var(--text-faint)}
.cm .h b{color:var(--parchment);font-weight:700}
.cm .h .when{margin-left:auto;color:var(--text-dim);font-size:12px}
.cm .body{padding:12px 15px;font-size:13.5px;color:var(--parchment);line-height:1.7;
  white-space:pre-wrap;overflow-wrap:anywhere}
.cm.staff{border-color:rgba(226,182,92,.3)}
.cm.staff .h{background:rgba(226,182,92,.07);border-bottom-color:rgba(226,182,92,.22)}

.composer{margin-top:16px}
.composer textarea{display:block;width:100%;padding:12px 14px;border-radius:11px;border:1px solid var(--border);
  background:var(--charcoal);color:var(--parchment);font-family:inherit;font-size:13.5px;line-height:1.65;resize:vertical}
.composer textarea::placeholder{color:#4f4941}
.composer .row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:11px}
.composer .row .grow{flex:1;text-align:right;font-size:12px;color:var(--text-dim)}
.evrow2{display:flex;align-items:center;gap:11px;padding:13px 15px;border-radius:11px;background:var(--charcoal);
  border:1px solid var(--border);font-size:12.5px;color:var(--text-dim);margin-top:16px;flex-wrap:wrap}
.evrow2 svg{width:15px;height:15px;flex:none;stroke:var(--stone);fill:none;stroke-width:2}
.evrow2 b{color:var(--text-faint);font-weight:600}
.evrow2 .btn{margin-left:auto}

/* ---- opening a ticket ---- */
.steps{display:flex;flex-direction:column}
.step{border-top:1px solid var(--border);padding:18px 20px}
.step:first-child{border-top:0}
.sn{display:flex;align-items:center;gap:11px;margin-bottom:14px;flex-wrap:wrap}
.sn .n{width:22px;height:22px;flex:none;border-radius:7px;display:grid;place-items:center;font-size:10.5px;
  font-weight:800;background:var(--gold);color:#000;font-variant-numeric:tabular-nums}
.sn h4{font-size:13px;font-weight:700}
.sn .req2{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold)}
.sn p{margin-left:auto;font-size:11.5px;color:var(--text-dim)}
.cats{display:grid;grid-template-columns:repeat(2,1fr);gap:9px}
@media (max-width:760px){.cats{grid-template-columns:1fr}}
.cat{display:flex;gap:12px;align-items:flex-start;padding:13px 14px;border-radius:11px;
  border:1px solid var(--border);background:var(--charcoal);cursor:pointer;font-family:inherit;
  text-align:left;transition:.13s}
.cat:hover{border-color:var(--charcoal-4)}
.cat.on{border-color:rgba(226,182,92,.5);background:rgba(226,182,92,.07)}
.cat .ic{width:30px;height:30px;flex:none;border-radius:8px;display:grid;place-items:center;
  background:var(--charcoal-3);border:1px solid var(--border)}
.cat .ic svg{width:15px;height:15px;stroke:var(--text-faint);fill:none;stroke-width:1.8}
.cat.on .ic{background:rgba(226,182,92,.14);border-color:rgba(226,182,92,.4)}
.cat.on .ic svg{stroke:var(--gold)}
.cat b{display:block;font-size:12.5px;font-weight:600;color:var(--text-faint);line-height:1.3}
.cat.on b{color:var(--parchment)}
.cat span span{display:block;font-size:11px;color:var(--text-dim);margin-top:6px;line-height:1.55}
.fld{margin-bottom:0}
.fld label{display:flex;align-items:center;gap:8px;font-size:10px;font-weight:800;letter-spacing:.13em;
  text-transform:uppercase;color:var(--text-dim);margin-bottom:9px}
.fld label u{text-decoration:none;letter-spacing:.02em;text-transform:none;font-size:11px;
  font-weight:600;color:var(--stone)}
.fld input,.fld textarea{display:block;width:100%;padding:11px 13px;border-radius:10px;
  border:1px solid var(--border);background:var(--charcoal);color:var(--parchment);font-family:inherit;
  font-size:13px;line-height:1.6}
.fld textarea{resize:vertical}
.fld input:disabled{opacity:.45}
.fld input::placeholder,.fld textarea::placeholder{color:#4f4941}
.fld .hint{background:none;border:0;padding:0;border-radius:0;
  font-size:11px;color:var(--text-dim);margin-top:8px;line-height:1.6}
.fld .hint b{display:inline;color:var(--text-faint);font-weight:600}
.fld .hint .count{float:right;font-variant-numeric:tabular-nums}
.orow{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:stretch}
@media (max-width:620px){.orow{grid-template-columns:1fr}}
/* No fixed height: the grid row stretches it to whatever the input beside
   it measures, so the two always agree even if the field's padding or font
   size changes later. */
.na{display:inline-flex;align-items:center;justify-content:center;gap:9px;align-self:stretch;
  padding:0 15px;border-radius:10px;border:1px solid var(--border);background:var(--charcoal);
  color:var(--text-dim);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
.na .bx{width:15px;height:15px;flex:none;border-radius:4px;border:1.6px solid var(--border);
  display:grid;place-items:center}
.na .bx svg{width:10px;height:10px;stroke:#000;stroke-width:3.4;fill:none;opacity:0}
.na.on{border-color:rgba(226,182,92,.5);color:var(--parchment)}
.na.on .bx{border-color:var(--gold);background:var(--gold)}
.na.on .bx svg{opacity:1}
/* The euro sign sits in the field rather than in the placeholder, so it is
   there whether or not anything has been typed and nobody has to type it. */
.pfxwrap{position:relative}
.pfxwrap .pfx{position:absolute;left:13px;top:50%;transform:translateY(-50%);
  font-size:13px;color:var(--text-faint);pointer-events:none;line-height:1}
.card .pfxwrap input{padding-left:30px}

.two{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px}
@media (max-width:620px){.two{grid-template-columns:1fr}}
.fa{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:16px 20px;
  border-top:1px solid var(--border);background:var(--charcoal)}
.tcheck{padding:12px 13px;border-radius:11px;background:var(--charcoal);border:1px solid var(--border)}
.tcheck .k{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--text-dim)}
.tcheck .v{font-size:12.5px;font-weight:700;color:var(--gold);margin-top:8px;line-height:1.5;
  overflow-wrap:anywhere}
.asech{font-size:10px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--text-dim);
  margin:16px 0 8px}
.ck{display:flex;gap:10px;padding:10px 0;font-size:12px;color:var(--text-dim);line-height:1.6;
  border-top:1px solid var(--border-soft)}
.ck:first-child{border-top:0}
.ck svg{width:14px;height:14px;flex:none;margin-top:2px;fill:none;stroke-width:2.4;stroke:var(--text-dim)}
.ck.done svg{stroke:var(--ok)}
.ck.done{color:var(--text-faint)}
.ck b{color:var(--text-faint);font-weight:600}
.priv{display:flex;gap:10px;font-size:11.5px;color:var(--text-dim);line-height:1.65;padding:11px 12px;
  border-radius:10px;background:rgba(226,182,92,.06);border:1px solid rgba(226,182,92,.22)}
.priv svg{width:14px;height:14px;flex:none;margin-top:2px;stroke:var(--gold);fill:none;stroke-width:1.9}

/* Every button centres its own contents — a label sitting left of centre
   in a fixed-width box is the thing that makes a panel look unfinished. */
.btn,.btn.primary,.btn.sm{display:inline-flex;align-items:center;justify-content:center;text-align:center}
.gchip{justify-content:center}

.notice{display:flex;align-items:center;gap:13px;background:linear-gradient(90deg,rgba(212,146,58,.10),rgba(212,146,58,.045));
  border:1px solid rgba(212,146,58,.30);border-radius:13px;padding:13px 16px;margin-bottom:18px;font-size:12.5px}
.notice b{font-weight:700}
.notice span{color:var(--text-faint)}

/* ---------- tiers ---------- */
/* ---------------- Purchase Credits ----------------
   A list you choose from, and a live summary of that choice. Five tiles
   side by side made every pack look equally good and left nothing room to
   explain itself. */
.buygrid{display:grid;grid-template-columns:minmax(0,1fr) 392px;gap:14px;align-items:start}
@media (max-width:1150px){.buygrid{grid-template-columns:minmax(0,1fr)}}
.buygrid > .card + .card{margin-top:0}

.packs{display:flex;flex-direction:column}
/* A <button> does not inherit the page's colour, so the row states it. */
.prow{color:var(--parchment);display:grid;
  grid-template-columns:164px minmax(0,1fr) 92px 112px 22px;align-items:center;gap:14px;
  padding:14px 18px;cursor:pointer;border:0;background:none;font-family:inherit;text-align:left;
  width:100%;border-top:1px solid var(--border);transition:.13s;position:relative}
.prow:first-child{border-top:0}
.prow:hover{background:var(--charcoal-3)}
.prow.on{background:var(--charcoal-3)}
.prow.on::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;background:var(--gold)}
.prow .top{display:flex;align-items:center;gap:8px}
.prow .top svg{width:17px;height:17px;stroke:#b99a5e;fill:none;stroke-width:1.85;flex:none}
.prow.on .top svg{stroke:var(--gold)}
.prow .top .n{font-size:20px;font-weight:700;line-height:1;letter-spacing:-.025em;
  font-variant-numeric:tabular-nums;color:var(--parchment)}
.prow .line{display:block;font-size:10.5px;color:var(--text-dim);margin-top:7px;line-height:1;
  font-variant-numeric:tabular-nums}
.prow .line b{color:#9dbd77;font-weight:700}
.prow .tr{height:9px;border-radius:100px;background:var(--charcoal-4);overflow:hidden}
.prow .tr i{display:block;height:100%;border-radius:100px;
  background:linear-gradient(90deg,var(--stone),#a8916a)}
.prow.on .tr i{background:linear-gradient(90deg,var(--amber),var(--gold))}
.prow .off{font-size:11.5px;color:var(--text-dim);font-variant-numeric:tabular-nums;text-align:right;line-height:1}
.prow .off.base{font-style:italic}
.prow.on .off{color:var(--gold);font-weight:700}
.prow .pr{text-align:right;line-height:1}
.prow .pr .now{font-size:16px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.01em;
  color:var(--parchment)}
.prow.on .pr .now{color:var(--gold)}
.prow .pr .was{display:block;font-size:11px;color:var(--text-dim);text-decoration:line-through;margin-top:5px}
.prow .pr .each{display:block;font-size:10.5px;color:var(--text-dim);margin-top:5px}
.prow .rd{width:18px;height:18px;border-radius:50%;border:1.6px solid var(--border);display:grid;place-items:center}
.prow.on .rd{border-color:var(--gold)}
.prow.on .rd::after{content:'';width:8px;height:8px;border-radius:50%;background:var(--gold)}

/* The promotion is the header of the card whose prices it changes, so the
   claim and the numbers it affects are one object. */
.p3h{display:flex;align-items:center;gap:13px;padding:13px 18px;
  background:linear-gradient(90deg,rgba(212,146,58,.13),rgba(212,146,58,.03) 62%,transparent);
  border-bottom:1px solid rgba(212,146,58,.26)}
.p3h .ic{width:34px;height:34px;flex:none;border-radius:9px;display:grid;place-items:center;
  background:rgba(212,146,58,.13);border:1px solid rgba(212,146,58,.34);color:var(--gold)}
.p3h .ic svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.6}
.p3h .tx b{font-size:12.5px;font-weight:700}
.p3h .tx b em{font-style:normal;color:var(--gold)}
.p3h .tx span{display:block;font-size:11.5px;color:var(--text-dim);margin-top:3px}
.p3h .r{margin-left:auto;text-align:right;flex:none}
.p3h .r .t{font-size:12px;font-weight:700;font-variant-numeric:tabular-nums;color:var(--parchment)}
.p3h .r .k{display:block;font-size:9.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
  color:var(--text-dim);margin-top:4px}
.fbtn{margin-left:14px;flex:none;display:inline-flex;align-items:center;gap:7px;height:30px;padding:0 12px;
  border-radius:8px;border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);
  font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;text-indent:.12em;
  cursor:pointer;font-family:inherit;line-height:1}
.fbtn:hover{border-color:var(--charcoal-4);color:var(--parchment)}
.fbtn svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.9}

.sum .big{display:flex;align-items:flex-end;gap:10px;padding-bottom:14px;border-bottom:1px solid var(--border)}
.sum .big .bi{width:26px;height:26px;flex:none;color:var(--gold);padding-bottom:3px}
.sum .big .bi svg{width:26px;height:26px;stroke:currentColor;fill:none;stroke-width:1.5}
.sum .big .n{font-size:34px;font-weight:700;color:var(--gold);line-height:.95;letter-spacing:-.025em;
  font-variant-numeric:tabular-nums}
.sum .big .u{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
  color:var(--text-dim);padding-bottom:4px}
.sum .big .pr{margin-left:auto;text-align:right;line-height:1}
.sum .big .pr .k{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--text-dim)}
.sum .big .pr .n2{display:block;margin-top:8px;font-size:19px;font-weight:700;font-variant-numeric:tabular-nums}
.sum .row{display:flex;align-items:center;gap:12px;padding:11px 0;font-size:12.5px;color:var(--text-dim)}
.sum .row + .row{border-top:1px solid rgba(56,50,43,.55)}
.sum .row .v{margin-left:auto;color:var(--parchment);font-weight:600;font-variant-numeric:tabular-nums}
.sum .row .v.good{color:#9dbd77}
.sum .row .v.strike{text-decoration:line-through;color:var(--text-dim);font-weight:500}
.sum .after{display:flex;align-items:center;gap:11px;margin-top:14px;padding:12px 13px;border-radius:11px;
  background:var(--charcoal);border:1px solid var(--border);font-size:12px;color:var(--text-dim)}
.sum .after b{margin-left:auto;color:var(--parchment);font-weight:700;font-variant-numeric:tabular-nums;font-size:14px}
.sum .after svg{width:15px;height:15px;stroke:var(--gold);fill:none;stroke-width:1.8;flex:none}
.methods{display:flex;gap:6px;margin-top:14px;flex-wrap:wrap}
.mth{display:inline-flex;align-items:center;gap:7px;height:28px;padding:0 10px;border-radius:8px;
  border:1px solid var(--border);background:var(--charcoal);font-size:11px;color:var(--text-faint);font-weight:600}
.mth svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.9}
.sum .go{margin-top:14px;width:100%}
.sum .note{margin-top:13px;font-size:11px;color:var(--text-dim);line-height:1.7}

.paying{border-top:1px solid var(--border);padding:15px 20px 17px;background:var(--charcoal)}
.paying .k{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--text-dim)}
.prow2{display:flex;gap:11px;font-size:12px;color:var(--text-dim);line-height:1.7;padding:9px 0}
.prow2 + .prow2{border-top:1px solid rgba(56,50,43,.5)}
.prow2 svg{width:14px;height:14px;flex:none;margin-top:3px;stroke:var(--gold);fill:none;stroke-width:1.8}
.prow2 b{color:var(--text-faint);font-weight:600}

.det{display:grid;grid-template-columns:repeat(3,1fr)}
@media (max-width:900px){.det{grid-template-columns:1fr}}
.dc{padding:17px 18px 19px}
.dc + .dc{border-left:1px solid var(--border)}
@media (max-width:900px){.dc + .dc{border-left:0;border-top:1px solid var(--border)}}
.dc b{display:flex;align-items:center;gap:9px;font-size:12.5px;font-weight:700;line-height:1}
.dc b svg{width:15px;height:15px;stroke:var(--gold);fill:none;stroke-width:1.8;flex:none}
.dc p{font-size:12px;color:var(--text-dim);line-height:1.75;margin-top:10px}

/* Founder-only promotion editor. */
.pmask{position:fixed;inset:0;background:rgba(10,9,8,.72);display:grid;place-items:center;z-index:70;
  padding:24px;animation:bsFade .16s ease both}
.pmodal{width:520px;max-width:100%;background:var(--charcoal-2);border:1px solid var(--border);
  border-radius:14px;box-shadow:0 24px 60px rgba(0,0,0,.6);overflow:hidden;animation:bsOpen .18s ease-out both}
.pmodal .mh{display:flex;align-items:center;gap:11px;padding:15px 18px;border-bottom:1px solid var(--border)}
.pmodal .mh h4{font-size:13.5px;font-weight:700}
.pmodal .mh .who{margin-left:auto;font-size:9.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;
  color:var(--amber);border:1px solid rgba(212,146,58,.35);border-radius:5px;padding:4px 7px;line-height:1}
.pmodal .mb{padding:18px;max-height:70vh;overflow:auto}
.fld{margin-bottom:15px}
.fld label{display:block;font-size:10px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;
  color:var(--text-dim);margin-bottom:8px}
.fld input{width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);
  background:var(--charcoal);color:var(--parchment);font-family:inherit;font-size:13px}
.fld input:focus{outline:none;border-color:rgba(226,182,92,.45)}
.seg{display:flex;gap:8px}
.seg button{flex:1;height:52px;border-radius:10px;border:1px solid var(--border);background:var(--charcoal);
  color:var(--text-faint);font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px}
.seg button.on{border-color:rgba(226,182,92,.45);background:rgba(226,182,92,.09);color:var(--parchment)}
.seg button span{font-size:10.5px;color:var(--text-dim);font-weight:500}
.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.prev{padding:12px 13px;border-radius:11px;background:var(--charcoal);border:1px solid var(--border)}
.prev .k{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--text-dim)}
.prev .l{font-size:12.5px;color:var(--text-faint);margin-top:8px;line-height:1.7}
.prev .l b{color:var(--gold);font-weight:700}
.pmodal .mf{display:flex;align-items:center;gap:9px;padding:14px 18px;border-top:1px solid var(--border);
  background:var(--charcoal);flex-wrap:wrap}
.pmodal .mf .warn{font-size:11px;color:var(--text-dim);line-height:1.6;max-width:250px}
.pmodal .mf .sp{margin-left:auto;display:flex;gap:9px}


.notice{display:flex;align-items:center;gap:13px;background:linear-gradient(90deg,rgba(212,146,58,.10),rgba(212,146,58,.045));
  border:1px solid rgba(212,146,58,.30);border-radius:13px;padding:13px 16px;margin-bottom:18px;font-size:12.5px}
.notice b{font-weight:700}
.notice span{color:var(--text-faint)}

/* ---------- tiers ---------- */
.tiers{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
@media (max-width:1250px){.tiers{grid-template-columns:repeat(3,1fr)}}
.tier{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;overflow:hidden;
  display:flex;flex-direction:column;position:relative;transition:border-color .15s}
.tier:hover{border-color:rgba(226,182,92,.42)}
.tier.best{border-color:rgba(226,182,92,.5)}
.tier .flag{position:absolute;top:12px;right:12px;font-size:9.5px;font-weight:800;letter-spacing:.12em;
  text-transform:uppercase;color:#1a1206;background:linear-gradient(145deg,var(--amber),var(--gold));
  padding:4px 8px;border-radius:5px}
.tier .amt{padding:20px 18px 16px;border-bottom:1px solid var(--border-soft)}
.tier .amt .n{font-family:Oswald,sans-serif;font-size:38px;font-weight:600;line-height:1;color:var(--parchment)}
.tier .amt .u{font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
  color:var(--text-dim);margin-top:6px}
.tier .body{padding:15px 18px 18px;display:flex;flex-direction:column;flex:1}
.tier .price{font-size:19px;font-weight:700;letter-spacing:-.01em}
.tier .rate{font-size:11.5px;color:var(--text-dim);margin-top:4px}
.tier .rate b{color:#9dbd77;font-weight:700}
.tier .buy{margin-top:16px;display:block;text-align:center;padding:10px;border-radius:9px;
  font-size:12.5px;font-weight:700;background:var(--charcoal-3);border:1px solid var(--border);
  color:var(--text-faint);cursor:not-allowed}
.tier.best .buy{background:linear-gradient(145deg,var(--amber),var(--gold));color:#1a1206;border:none}

/* ---------- shop ---------- */
.toolbar{display:flex;align-items:center;gap:9px;margin-bottom:16px;flex-wrap:wrap}
.chip{padding:7px 13px;border-radius:100px;border:1px solid var(--border);background:var(--charcoal-2);
  font-size:12px;font-weight:700;color:var(--text-faint);cursor:pointer}
.chip.on{background:rgba(226,182,92,.1);border-color:rgba(226,182,92,.4);color:var(--gold)}
.sbox{position:relative;margin-left:auto;min-width:250px}
.sbox svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;
  stroke:var(--text-dim);fill:none;stroke-width:2}
.sbox input{width:100%;background:var(--charcoal-2);border:1px solid var(--border);border-radius:10px;
  color:var(--parchment);font:inherit;font-size:12.5px;padding:10px 12px 10px 36px}

.items{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;grid-auto-rows:1fr}
@media (max-width:1150px){.items{grid-template-columns:repeat(2,1fr)}}
.item{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;
  display:flex;flex-direction:column;overflow:hidden;transition:border-color .15s,background .15s}
.item:hover{border-color:rgba(226,182,92,.42)}
.item .ihead{display:flex;align-items:flex-start;gap:13px;padding:16px 17px 0}
.item .ic{width:38px;height:38px;flex:none;border-radius:10px;display:grid;place-items:center;
  background:var(--charcoal-3);border:1px solid var(--border)}
.item .ic svg{width:18px;height:18px;stroke:var(--gold);fill:none;stroke-width:1.8}
.item .eyebrow{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
  color:var(--text-dim)}
.item h3{font-size:15px;font-weight:700;letter-spacing:-.01em;margin-top:4px}
.item .cost{margin-left:auto;text-align:right;flex:none}
.item .cost .n{font-family:Oswald,sans-serif;font-size:26px;font-weight:600;line-height:1;color:var(--gold)}
.item .cost .u{font-size:9.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
  color:var(--text-dim);margin-top:3px}
.item p{font-size:12.5px;color:var(--text-faint);line-height:1.65;padding:12px 17px 0;flex:1}
.item .facts{display:flex;flex-wrap:wrap;gap:6px;padding:13px 17px 0}
.item .fact{font-size:10.5px;font-weight:700;letter-spacing:.03em;color:var(--text-faint);
  background:var(--charcoal-3);border:1px solid var(--border);border-radius:6px;padding:4px 8px}
.item .foot{display:flex;align-items:center;gap:10px;margin-top:15px;padding:13px 17px;
  border-top:1px solid var(--border-soft)}
.item .foot .own{font-size:11.5px;color:var(--text-dim)}
.item .btn{margin-left:auto;padding:8px 15px;border-radius:9px;font-size:12.5px;font-weight:700;
  background:linear-gradient(145deg,var(--amber),var(--gold));color:#1a1206;border:none;cursor:pointer}
.item .btn.quiet{background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-faint)}

/* ---------- table ---------- */
.card{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px}
.card-h{display:flex;align-items:center;gap:11px;padding:14px 18px;border-bottom:1px solid var(--border-soft)}
.card-h h3{font-size:13.5px;font-weight:700}
.card-h .r{margin-left:auto;display:flex;gap:8px;align-items:center}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
  color:var(--text-dim);padding:11px 16px;border-bottom:1px solid var(--border-soft);white-space:nowrap}
td{padding:13px 16px;font-size:13px;border-bottom:1px solid var(--border-soft)}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:var(--charcoal-3)}
.mono{font-variant-numeric:tabular-nums;color:var(--text-faint)}
.amtcell{font-family:Oswald,sans-serif;font-size:16px;font-weight:600;color:var(--gold)}
.amtcell.in{color:#9dbd77}
.st{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600}
.st s{width:7px;height:7px;border-radius:50%;text-decoration:none;flex:none}
.st.ok s{background:var(--ok)} .st.ok{color:#9dbd77}
.st.warn s{background:var(--amber)} .st.warn{color:#dda157}
.st.dead s{background:var(--stone)} .st.dead{color:var(--text-faint)}
.pager{display:flex;gap:7px}
.pg{min-width:34px;height:34px;padding:0 10px;display:grid;place-items:center;border-radius:9px;
  border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);font-size:12px;font-weight:700}
.pg.on{background:var(--charcoal);color:var(--parchment)}
.sel{appearance:none;background:var(--charcoal) url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23968e7e' stroke-width='2'><path d='M6 9l6 6 6-6'/></svg>") no-repeat right 11px center/14px;
  border:1px solid var(--border);border-radius:9px;color:var(--parchment);font:inherit;font-size:12px;padding:8px 34px 8px 11px}

/* ---------- support ---------- */
.split{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:16px;align-items:start}
.trow{display:flex;align-items:center;gap:13px;padding:14px 16px;border-bottom:1px solid var(--border-soft);cursor:pointer}
.trow:last-child{border-bottom:none}
.trow:hover{background:var(--charcoal-3)}
.trow .id{font-family:Oswald,sans-serif;font-size:15px;color:var(--text-dim);width:58px;flex:none}
.trow .tx b{display:block;font-size:13.5px;font-weight:600}
.trow .tx span{display:block;font-size:11.5px;color:var(--text-dim);margin-top:3px}
.trow .r{margin-left:auto;display:flex;align-items:center;gap:14px;flex:none}
.card-b{padding:18px}
.flab{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);margin-bottom:8px}
.flab+.flab{margin-top:14px}
input[type=text],textarea{width:100%;background:var(--charcoal);border:1px solid var(--border);border-radius:10px;
  color:var(--parchment);font:inherit;font-size:13px;padding:11px 13px;resize:vertical}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;
  border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);font:inherit;
  font-size:12.5px;font-weight:700;cursor:pointer}
.btn.primary{background:linear-gradient(145deg,var(--amber),var(--gold));color:#1a1206;border:none}
.lede{font-size:12.5px;color:var(--text-faint);line-height:1.7}
.msg{border:1px solid var(--border-soft);border-radius:12px;padding:14px 16px;background:var(--charcoal-3);margin-top:10px}
.msg.staff{background:rgba(226,182,92,.06);border-color:rgba(226,182,92,.26)}
.msg .who{font-size:11.5px;font-weight:700;color:var(--gold);margin-bottom:6px}
.msg.player .who{color:var(--text-faint)}
.msg p{font-size:12.5px;color:var(--text-faint);line-height:1.7}

  /* Ticket pagination, lifted from the administrative record so paging
     behaves the same everywhere in the UCP. */
  .pager{display:flex;align-items:center;gap:7px}
  .pg{min-width:34px;height:34px;padding:0 10px;display:grid;place-items:center;border-radius:9px;
    border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);
    font-size:12px;font-weight:700;cursor:pointer;font-variant-numeric:tabular-nums}
  .pg.on{background:var(--charcoal);color:var(--parchment);border-color:var(--charcoal-4)}
  .pg[disabled]{opacity:.35;cursor:not-allowed}
  .pgap{color:var(--text-dim);font-size:12px}

  /* ---------------- Overview ----------------
     The photograph is the county at dusk, processed down to about half
     colour and masked so it only exists on the right of the card: the
     headline sits on flat charcoal and never needs a shadow to stay
     readable. 16KB of WebP. */
  .hero{position:relative;padding:30px 30px 0;overflow:hidden;background:var(--charcoal-2);
    display:flex;flex-direction:column}
  .hero .img{position:absolute;inset:0;background:url(/assets/img/hero-jack.webp) no-repeat center right/cover}
  .hero .veil{position:absolute;inset:0;background:linear-gradient(90deg,
    var(--charcoal-2) 22%, rgba(32,29,25,.60) 48%, rgba(32,29,25,.14) 76%, rgba(32,29,25,.26) 100%)}
  .hero .glow{position:absolute;inset:0;background:
    radial-gradient(560px 240px at 74% 12%, rgba(212,146,58,.14), transparent 66%)}
  .hero .in{position:relative;padding-bottom:26px}
  .hero .eyebrow{font-size:10.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--amber)}
  .hero h2{font-size:27px;font-weight:700;line-height:1.28;letter-spacing:-.015em;margin:11px 0 13px;max-width:26ch}
  .hero h2 em{font-style:normal;color:var(--gold)}
  .hero p{font-size:13px;color:var(--text-faint);line-height:1.85;max-width:52ch}
  .hero .acts{display:flex;gap:9px;margin-top:20px;flex-wrap:wrap}
  /* Breaks out of the hero's 30px padding so the strip runs edge to edge,
     and it is opaque: a translucent band over a photograph is a band with
     a building in it. */
  .facts{position:relative;display:flex;margin:auto -30px 0;padding:18px 30px;
    border-top:1px solid var(--border);background:#191714}
  .facts .f{flex:1;padding-right:16px;min-width:0}
  .facts .f + .f{padding-left:18px;border-left:1px solid var(--border)}
  .facts .f b{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;
    color:var(--parchment);line-height:1}
  .facts .f b svg{width:14px;height:14px;stroke:var(--gold);fill:none;stroke-width:1.9;flex:none}
  .facts .f span{display:block;font-size:11.5px;color:var(--text-dim);line-height:1.65;margin-top:7px}

  /* The shell puts a top margin on any .card following a .card; inside a
     grid row that pushes the second column down by itself. */
  /* Both cards end level, and the shorter one spreads its own rows to fill
     rather than leaving a pocket of empty card at the bottom. */
  .ovtop{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;align-items:stretch}
  .ovtop > .card{display:flex;flex-direction:column}
  .ovtop .card-b{flex:1;display:flex;flex-direction:column}
  .bars{flex:1;justify-content:space-evenly}
  .ovtop > .card + .card{margin-top:0}
  @media (max-width:1080px){.ovtop{grid-template-columns:minmax(0,1fr)}}

  /* Value comparison. Longer bar means a better deal, which is the
     direction people expect; the smallest pack is the baseline everything
     is measured against, so it has no bar at all. */
  /* Not stretched to match its neighbour: a card padded out to someone
     else's height is just a pool of empty space with a border round it. */
  .val .card-b{padding:16px 18px 18px}
  .val .lead{font-size:12px;color:var(--text-dim);line-height:1.7;margin-bottom:15px}
  .bars{display:flex;flex-direction:column;gap:2px}
  .bar{display:grid;grid-template-columns:62px minmax(0,1fr) 74px;align-items:center;gap:11px;
    padding:8px 9px;border-radius:9px;cursor:pointer;border:1px solid transparent;transition:.13s;
    background:none;font-family:inherit;text-align:left;width:100%}
  .bar:hover{background:var(--charcoal-3)}
  .bar.on{background:var(--charcoal-3);border-color:rgba(226,182,92,.32)}
  .bar .pk{font-size:12px;font-weight:600;color:var(--text-faint);font-variant-numeric:tabular-nums;
    text-align:right;line-height:1}
  .bar.on .pk{color:var(--parchment)}
  .bar .tr{height:9px;border-radius:100px;background:var(--charcoal-4);overflow:hidden}
  .bar .tr i{display:block;height:100%;border-radius:100px;
    background:linear-gradient(90deg,var(--stone),#a8916a);transition:.2s}
  .bar.on .tr i{background:linear-gradient(90deg,var(--amber),var(--gold))}
  .bar .rt{font-size:11.5px;color:var(--text-dim);font-variant-numeric:tabular-nums;text-align:right;line-height:1}
  .bar .rt.base{font-style:italic}
  .bar.on .rt{color:var(--gold);font-weight:700}
  .val .out{margin-top:16px;border-top:1px solid var(--border);display:grid;grid-template-columns:repeat(3,1fr)}
  .val .out .cell{padding:13px 0 2px;text-align:center}
  .val .out .cell + .cell{border-left:1px solid var(--border)}
  .val .out .k{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;
    color:var(--text-dim);line-height:1;text-indent:.13em}
  .val .out .n{display:block;margin-top:9px;font-size:17px;font-weight:700;color:var(--parchment);
    line-height:1;font-variant-numeric:tabular-nums;letter-spacing:-.01em}
  .val .out .n.gold{color:var(--gold)}
  .val .out .n.good{color:#9dbd77}
  .val .note{margin-top:15px;padding-top:13px;border-top:1px solid var(--border);
    font-size:11px;color:var(--text-dim);line-height:1.65}

  .flow{display:grid;grid-template-columns:repeat(3,1fr)}
  @media (max-width:900px){.flow{grid-template-columns:1fr}}
  .step{padding:20px 20px 22px}
  .ovtop .step{padding:15px 18px 16px}
  .ovtop .step p{margin-top:9px}
  .step + .step{border-left:1px solid var(--border)}
  @media (max-width:900px){.step + .step{border-left:0;border-top:1px solid var(--border)}}
  .step .mk{display:flex;align-items:center;gap:10px}
  .step .mk .n{width:24px;height:24px;border-radius:7px;display:grid;place-items:center;font-size:11px;
    font-weight:800;color:#1a1512;background:linear-gradient(180deg,var(--gold),var(--amber));
    font-variant-numeric:tabular-nums}
  .step .mk h4{font-size:13px;font-weight:700}
  .step p{font-size:12.5px;color:var(--text-dim);line-height:1.75;margin-top:11px}
  .step .hint{display:inline-flex;align-items:center;gap:7px;margin-top:13px;font-size:11px;
    color:var(--text-faint);background:var(--charcoal-3);border:1px solid var(--border-soft);
    border-radius:8px;padding:6px 10px;line-height:1}
  .step .hint svg{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2}

  /* FAQ. Everything is closed on arrival except the first, and answers
     carry their own structure — lists where a case branches, a callout
     where there is something worth not missing. */
  .faq .q{border-top:1px solid var(--border)}
  .faq .q:first-child{border-top:0}
  .faq .qh{display:flex;align-items:center;gap:12px;padding:14px 18px;font-size:12.5px;font-weight:600;
    line-height:1;cursor:pointer;user-select:none;transition:.13s}
  .faq .qh:hover{background:rgba(41,37,32,.5)}
  .faq .q.on .qh{color:var(--gold)}
  .faq .qh .tg{font-size:9.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;
    color:var(--text-dim);border:1px solid var(--border);border-radius:5px;padding:4px 7px;line-height:1}
  .faq .q.on .qh .tg{color:var(--amber);border-color:rgba(212,146,58,.35)}
  .faq .qh .cv{margin-left:auto;width:13px;height:13px;stroke:var(--text-dim);fill:none;
    stroke-width:2.2;transition:.16s;flex:none}
  .faq .q.on .qh .cv{transform:rotate(180deg);stroke:var(--gold)}
  .faq .qb{display:none;padding:2px 18px 17px;font-size:12.5px;color:var(--text-dim);line-height:1.85}
  .faq .q.on .qb{display:block;animation:bsOpen .16s ease-out both}
  .faq .qb ul{list-style:none;margin:10px 0 0}
  .faq .qb li{position:relative;padding-left:17px;margin-top:7px}
  .faq .qb li::before{content:'';position:absolute;left:3px;top:9px;width:5px;height:5px;
    border-radius:50%;background:var(--stone)}
  .faq .qb b{color:var(--text-faint);font-weight:600}
  .faq .qb .kb{display:flex;gap:11px;margin-top:12px;padding:11px 13px;border-radius:10px;
    background:rgba(212,146,58,.07);border:1px solid rgba(212,146,58,.24);color:var(--text-faint);
    font-size:12px;line-height:1.7}
  .faq .qb .kb svg{width:15px;height:15px;flex:none;margin-top:3px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .faq .qb .kb.no{background:rgba(193,85,63,.07);border-color:rgba(193,85,63,.26)}
  .faq .qb .kb.no svg{stroke:#d98a75}
  .faq .foot{display:flex;align-items:center;gap:6px;padding:13px 18px;border-top:1px solid var(--border);
    font-size:12px;color:var(--text-dim);background:var(--charcoal);flex-wrap:wrap}
  .faq .foot a{color:var(--gold);text-decoration:none;font-weight:600;cursor:pointer}

  .help{display:grid;grid-template-columns:minmax(0,1fr) 420px}
  @media (max-width:1000px){.help{grid-template-columns:minmax(0,1fr)}}
  .hleft{padding:22px 24px 24px;display:flex;flex-direction:column}
  .hleft .eyebrow{font-size:10.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--amber)}
  .hleft h4{font-size:16px;font-weight:700;margin:9px 0 10px;letter-spacing:-.01em}
  .hleft p{font-size:12.5px;color:var(--text-dim);line-height:1.8;margin-bottom:16px}
  .sfacts{display:flex;margin:4px 0 18px}
  .sfacts .sf{flex:1;padding-right:14px;min-width:0}
  .sfacts .sf + .sf{padding-left:16px;border-left:1px solid var(--border)}
  .sfacts .sf b{display:block;font-size:12px;font-weight:700;color:var(--gold);line-height:1}
  .sfacts .sf span{display:block;font-size:11.5px;color:var(--text-dim);margin-top:6px;line-height:1.55}
  .hleft .btn{align-self:flex-start;margin-top:auto}
  .hright{border-left:1px solid var(--border);padding:22px 24px;
    background:radial-gradient(420px 220px at 100% 100%, rgba(212,146,58,.08), transparent 70%), var(--charcoal)}
  @media (max-width:1000px){.hright{border-left:0;border-top:1px solid var(--border)}}
  .hk{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--text-dim);
    margin-bottom:12px}
  .hrow{display:flex;gap:11px;font-size:12px;color:var(--text-dim);line-height:1.7;padding:9px 0}
  .hrow + .hrow{border-top:1px solid rgba(56,50,43,.5)}
  .hrow svg{width:14px;height:14px;flex:none;margin-top:3px;stroke:var(--text-faint);fill:none;stroke-width:1.9}
  .hrow b{color:var(--text-faint);font-weight:600}

  .empty{border:1px dashed var(--border);border-radius:12px;padding:34px 18px;text-align:center;
    color:var(--text-dim);font-size:12.5px}
  .card-b .empty{margin:0}
  .tab{position:relative}
  .backline{display:flex;align-items:center;gap:11px;margin-bottom:14px;flex-wrap:wrap}
  .backline .btn svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2.2}
  .msg .body{font-size:12.5px;color:var(--text-faint);line-height:1.7;white-space:pre-wrap}
  .trow.on{background:var(--charcoal-3)}
  .who-line{font-size:11.5px;color:var(--text-dim)}


CSS;
require __DIR__ . '/../partials/shell-top.php';
?>


      <div class="stack">
        <div class="qhead">
          <span class="qi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/><path d="M12 7.2v9.6"/><path d="M15 9.4a3.6 3.6 0 1 0 0 5.2"/></svg></span>
          <div>
            <h1>Credit Store</h1>
            <p>Buy credits, spend them, and keep the receipts.</p>
          </div>
        </div>

        <div class="tabs" id="tabs"></div>
        <div id="body"><div class="empty">Loading&hellip;</div></div>
      </div>

<?php require __DIR__ . '/../partials/shell-foot.php'; ?>

</body>
</html>
