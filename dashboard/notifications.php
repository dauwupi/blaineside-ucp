<?php
/**
 * Notifications.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'BlaineSide — Notifications';
$PAGE_HEADING = 'Notifications';
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

  /* =====================================================================
     BAN APPEALS — shared styles for the queue, the form and one appeal.

     Card shell first: the announcements page this shell is cut from draws
     its own list rows and never needed .card.
     ===================================================================== */
  .card{background:var(--charcoal-2);border:1px solid var(--border);border-radius:14px;
    box-shadow:0 20px 44px -30px rgba(0,0,0,.9);overflow:hidden}
  .card + .card{margin-top:20px}
  .card-h{display:flex;align-items:baseline;justify-content:space-between;gap:16px;flex-wrap:wrap;
    padding:17px 22px 15px;border-bottom:1px solid var(--rule)}
  .card-h h3{font-size:15.5px;font-weight:700;letter-spacing:-.015em}
  .card-h .aside{font-size:12.5px;color:var(--text-faint);font-variant-numeric:tabular-nums}
  .card-b{padding:16px 22px 20px}
  .card-lede{font-size:13px;color:var(--text-faint);line-height:1.65;text-wrap:pretty}

  /* ---- the heading ----
     Oswald in caps, the same face and treatment as BLAINESIDE in the sidebar,
     so the page title reads as part of the brand rather than as bolder body
     text. All white — the change of face already separates it from the
     sentence underneath, so a second colour would be saying it twice.

     The icon is centred on the block. Aligned to the first line it read as
     though it belonged to the title and the sentence below was something
     else. */
  .ahead{display:flex;align-items:center;gap:17px;margin-bottom:22px}
  .ahead .qi{width:48px;height:48px;flex:none;display:grid;place-items:center;border-radius:13px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--gold)}
  .ahead .qi svg{width:23px;height:23px;stroke-width:1.8}
  .ahead .tx{flex:1;min-width:0}
  .ahead h1{font-family:'Oswald',sans-serif;font-size:33px;font-weight:600;letter-spacing:.055em;
    text-transform:uppercase;line-height:1.05;color:var(--parchment)}
  .ahead p{font-size:13.5px;color:var(--text-faint);line-height:1.65;margin-top:8px;
    text-wrap:pretty}

  /* ---- the two views ----
     Half the page each. The panel is not drawn at all for somebody who
     cannot open it: a greyed control tells them a door exists and then
     refuses to say anything else about it, which is worse than the door not
     being there. Everything about who may open it is decided by
     api/queues.php; this only draws the answer. */
  .qtabs{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
  .qtabs.solo{grid-template-columns:1fr}
  .qtab{display:flex;align-items:center;gap:13px;text-align:left;padding:15px 18px;
    border-radius:13px;border:1px solid var(--border);background:var(--charcoal-2);
    font-family:inherit;cursor:pointer;transition:.15s;position:relative;overflow:hidden}
  .qtab:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  .qtab .ai{width:36px;height:36px;flex:none;display:grid;place-items:center;border-radius:10px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-faint);
    transition:.15s}
  .qtab .ai svg{width:18px;height:18px;stroke-width:1.9}
  .qtab .at{min-width:0;flex:1}
  .qtab .an{font-size:14px;font-weight:700;color:var(--parchment);display:flex;align-items:center;
    gap:9px;flex-wrap:wrap;line-height:1.3}
  .qtab .ad{font-size:11.5px;color:var(--text-dim);margin-top:3px;line-height:1.45}
  .qtab[aria-selected="true"]{background:var(--charcoal-3);border-color:rgba(226,182,92,.42)}
  .qtab[aria-selected="true"]::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;
    background:linear-gradient(180deg,var(--gold),var(--amber))}
  .qtab[aria-selected="true"] .ai{background:rgba(226,182,92,.12);
    border-color:rgba(226,182,92,.34);color:var(--gold)}
  /* The staff view used to be tinted gold to mark it out. It marked it out
     as the important one instead, which it is not — it is one of three, and
     the person who needs it knows who they are. The STAFF pill says what it
     is; the box looks like every other box. */
  .qtab:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
  .smark{font-size:9.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;
    color:#e3bd72;background:rgba(226,182,92,.11);border:1px solid rgba(226,182,92,.3);
    padding:2px 8px;border-radius:100px}
  @media (max-width:680px){ .qtabs{grid-template-columns:1fr} }

  /* =====================================================================
     BAN DETAILS — one design

     This card used to be three different shapes doing the same job: a stack
     of label-over-value rows for the punishment, a second stack for the
     accounts, and a grid for the account panel — with Forums and Discord
     turning up in two of them. It is now one card, sections inside it, and
     one fact-grid component used by every section.
     ===================================================================== */
  .sec + .sec{margin-top:20px;padding-top:18px;border-top:1px solid var(--rule)}
  .sec-h{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    margin-bottom:12px}
  .sec-h .t{font-size:10.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;
    color:var(--stone)}
  .sec-h .n{font-size:11.5px;color:var(--text-dim);font-variant-numeric:tabular-nums}
  .sec-h .go{display:inline-flex;align-items:center;gap:7px;font-size:11.5px;font-weight:700;
    color:var(--text-faint);transition:color .13s}
  .sec-h .go:hover{color:var(--gold)}
  .sec-h .go svg{width:12px;height:12px;stroke-width:2.2;fill:none;stroke:currentColor}

  /* The fact grid. Used for a punishment AND for the account.
     Four fixed columns, not auto-fit: both grids have a multiple of four
     cells, so the rows come out full — auto-fit left dead cells hanging off
     the end of the account grid. */
  .fg{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;
    background:var(--rule);border:1px solid var(--rule);border-radius:12px;overflow:hidden}
  .fg > div{background:var(--charcoal-2);padding:11px 14px 12px;min-width:0}
  .fg .k{font-size:9.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
    color:var(--stone)}
  .fg .v{font-size:13px;font-weight:600;color:var(--parchment);margin-top:5px;line-height:1.4;
    overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
  .fg .v.soft{color:var(--text-dim);font-weight:500;font-style:italic}
  .fg .s{font-size:11px;color:var(--text-dim);margin-top:3px;line-height:1.4}
  .fg .v a{color:var(--parchment);border-bottom:1px solid var(--charcoal-4)}
  .fg .v a:hover{color:var(--gold);border-bottom-color:var(--gold)}
  .fg .grp{color:var(--tone-text)}
  /* The appellant is not told who issued it, so their grid is three across.
     Left at four it ended on a dead cell. */
  .fg.three{grid-template-columns:repeat(3,1fr)}

  /* A value carrying a state is coloured text with a dot, not a pill. Two
     pills floating in a grid of plain values read as buttons and made those
     cells louder than the facts around them. */
  .dotv{display:inline-flex;align-items:center;gap:8px}
  .dotv::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;
    flex:none}
  .dotv.ok{color:#9ec178} .dotv.bad{color:#e0917f} .dotv.warn{color:#e3bd72}
  .dotv.off{color:var(--stone)}

  /* ---- one punishment ---- */
  .pun + .pun{margin-top:14px}
  .pun-h{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
  /* The pill says which of the two a ban is. "Ban · 14 DAYS" made the reader
     work the kind out from the length, and a permanent one had no length to
     work it out from. */
  .kindp{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:700;
    padding:5px 12px;border-radius:100px;white-space:nowrap;
    border:1px solid var(--rule);background:var(--charcoal);color:var(--text-faint)}
  .kindp::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;
    flex:none}
  .kindp.ban {color:#d98a78;border-color:rgba(193,85,63,.34);background:rgba(193,85,63,.1)}
  .kindp.lock{color:#b3a894;border-color:rgba(157,147,132,.34);background:rgba(157,147,132,.1)}
  .kindp.discord{color:#93a7cb;border-color:rgba(110,130,175,.34);background:rgba(110,130,175,.11)}
  .kindp.forums{color:#9fb0a0;border-color:rgba(120,150,120,.32);background:rgba(120,150,120,.1)}
  .lenp{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;
    color:var(--text-dim)}
  .statep{margin-left:auto;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:100px;
    white-space:nowrap;border:1px solid var(--rule);background:var(--charcoal);color:var(--stone)}
  .statep.live{color:#e79187;border-color:rgba(193,85,63,.34);background:rgba(193,85,63,.1)}

  /* ---- the reason, as the thing it is: what somebody wrote ---- */
  .why{margin-top:11px;padding:12px 15px;border-radius:11px;background:var(--charcoal-3);
    border:1px solid var(--border-soft)}
  .why .k{font-size:9.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
    color:var(--stone)}
  .why .b{font-size:13.5px;color:var(--parchment);line-height:1.6;margin-top:5px;
    overflow-wrap:anywhere;text-wrap:pretty;white-space:pre-wrap}
  .why .b.soft{color:var(--text-dim);font-style:italic;white-space:normal}

  /* ---- nothing on file ---- */
  .offsite{padding:13px 15px;border-radius:12px;border:1px solid rgba(226,182,92,.24);
    background:rgba(226,182,92,.05)}
  .offsite .h{font-size:13px;font-weight:700;color:#e3bd72}
  .offsite .p{font-size:12.5px;color:var(--text-faint);line-height:1.6;margin-top:5px;
    text-wrap:pretty}

  /* ---- characters ----
     Designed now, empty now. When the game server is linked this fills in
     and nothing about the card moves. */
  .chars{border:1px solid var(--rule);border-radius:12px;overflow:hidden}
  .chr{display:flex;align-items:center;gap:14px;padding:11px 15px;background:var(--charcoal-2)}
  .chr + .chr{border-top:1px solid var(--rule)}
  .chr .cd{width:7px;height:7px;border-radius:50%;flex:none;background:var(--stone)}
  .chr.on .cd{background:var(--ok);box-shadow:0 0 0 3px rgba(127,160,90,.15)}
  .chr .cl{min-width:0;flex:1}
  .chr .cn{font-size:13.5px;font-weight:700;color:var(--parchment);min-width:0}
  .chr .cs{font-size:11px;color:var(--text-dim);margin-top:2px}
  .chr .cf{font-size:12px;color:var(--text-faint);margin-left:auto;text-align:right;
    white-space:nowrap}
  .chr-none{padding:15px 16px;background:var(--charcoal-2);font-size:12.5px;
    color:var(--text-dim);line-height:1.6;text-wrap:pretty}

  @media (max-width:900px){ .fg,.fg.three{grid-template-columns:repeat(2,1fr)} }
  @media (max-width:520px){
    .fg,.fg.three{grid-template-columns:1fr}
    .statep{margin-left:0}
  }

  /* ---- the queue's filter strip ---- */
  .qfilters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
  .qfilters .qtab{display:inline-flex;align-items:center;gap:9px;padding:10px 16px;
    border-radius:11px;border:1px solid var(--border);background:var(--charcoal-2);
    color:var(--text-dim);font-size:13px;font-weight:600;cursor:pointer;transition:.14s}
  .qfilters .qtab:hover{background:var(--charcoal-3);color:var(--parchment)}
  .qfilters .qtab[aria-selected="true"]{background:var(--charcoal-4);color:var(--parchment);
    border-color:rgba(226,182,92,.34)}
  .qfilters .qtab::before{display:none}

  /* =====================================================================
     THE RULES GATE — redesigned.

     It was a wall: four headings, some paragraphs, two bulleted lists and a
     red box, all the same weight, all the same colour. Nothing told you
     where to look and nothing told you when you were done, so people
     scrolled past it to the button — which is exactly the failure the page
     exists to prevent.

     Now it has shape. Four sections, each visually a different KIND of
     thing: a checklist you can scan, two panels you pick between, three
     rule cards, and one red panel that means stop. You can tell them apart
     without reading them, which is what makes the reading happen.
     ===================================================================== */
  .gate{display:flex;flex-direction:column;gap:26px}

  .gsec > h4{display:flex;align-items:center;gap:10px;font-size:11.5px;font-weight:800;
    letter-spacing:.09em;text-transform:uppercase;color:var(--text-dim);margin-bottom:13px}
  .gsec > h4::after{content:'';flex:1;height:1px;background:var(--rule)}
  .gsec > .lede{font-size:13.5px;color:var(--text-faint);line-height:1.65;margin-bottom:14px;
    text-wrap:pretty}

  /* 1. the checklist — scannable, one glance tells you if you qualify */
  .gchecks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
  @media (max-width:820px){ .gchecks{grid-template-columns:1fr} }
  .gcheck{display:flex;align-items:flex-start;gap:11px;padding:13px 15px;border-radius:11px;
    background:var(--charcoal);border:1px solid var(--border);font-size:12.5px;
    color:var(--parchment);line-height:1.5;text-wrap:pretty}
  .gcheck .m{flex:none;width:19px;height:19px;display:grid;place-items:center;border-radius:6px;
    margin-top:0;background:rgba(127,160,90,.13);border:1px solid rgba(127,160,90,.34);
    color:#9fae8d}
  .gcheck .m svg{width:11px;height:11px;stroke-width:3}

  /* 2. two panels — you are in one situation or the other, so pick */
  .gsplit{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  @media (max-width:820px){ .gsplit{grid-template-columns:1fr} }
  .gcase{padding:16px 18px;border-radius:12px;background:var(--charcoal);
    border:1px solid var(--border)}
  .gcase h5{font-size:13.5px;font-weight:700;color:var(--parchment);
    display:flex;align-items:center;gap:9px}
  .gcase h5 .ic{width:24px;height:24px;flex:none;display:grid;place-items:center;
    border-radius:7px;background:var(--charcoal-3);border:1px solid var(--border);
    color:var(--gold)}
  .gcase h5 .ic svg{width:13px;height:13px;stroke-width:2}
  .gcase p{font-size:12.5px;color:var(--text-faint);line-height:1.65;margin-top:10px;
    text-wrap:pretty}
  .gcase .tip{margin-top:11px;padding-top:11px;border-top:1px solid var(--rule);
    font-size:12px;color:var(--text-dim);line-height:1.55}
  .gcase .tip b{color:#cbb07a;font-weight:700}

  /* 3. the rules — three cards, each one thing that ends an appeal */
  .grules{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
  @media (max-width:980px){ .grules{grid-template-columns:1fr} }
  .grule{padding:15px 17px;border-radius:12px;background:var(--charcoal);
    border:1px solid var(--border)}
  .grule .t{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:700;
    color:var(--parchment)}
  .grule .t .ic{width:22px;height:22px;flex:none;display:grid;place-items:center;
    border-radius:7px;background:var(--charcoal-3);border:1px solid var(--border);
    color:var(--text-faint)}
  .grule .t .ic svg{width:12px;height:12px;stroke-width:2.2}
  .grule p{font-size:12px;color:var(--text-faint);line-height:1.6;margin-top:9px;
    text-wrap:pretty}

  /* 4. stop */
  .gstop{padding:17px 19px;border-radius:12px;background:rgba(193,85,63,.07);
    border:1px solid rgba(193,85,63,.32)}
  .gstop .h{display:flex;align-items:center;gap:10px}
  .gstop .h .ic{width:26px;height:26px;flex:none;display:grid;place-items:center;
    border-radius:8px;background:rgba(193,85,63,.11);border:1px solid rgba(193,85,63,.32);
    color:#d29b8d}
  .gstop .h .ic svg{width:14px;height:14px;stroke-width:2.2}
  .gstop .h h5{font-size:13.5px;font-weight:700;color:#dfa294}
  .gstop > p{font-size:12.5px;color:var(--text-faint);line-height:1.6;margin-top:9px}
  .gstop .items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 24px;
    margin-top:14px;padding-top:14px;border-top:1px solid rgba(193,85,63,.22)}
  @media (max-width:820px){ .gstop .items{grid-template-columns:1fr} }
  .gstop .items > div{display:flex;gap:10px;align-items:flex-start}
  .gstop .items .x{flex:none;width:17px;height:17px;display:grid;place-items:center;
    border-radius:5px;background:rgba(193,85,63,.13);border:1px solid rgba(193,85,63,.3);
    color:#d29b8d;margin-top:1px}
  .gstop .items .x svg{width:9px;height:9px;stroke-width:3.4}
  .gstop .items b{display:block;font-size:12.5px;font-weight:700;color:var(--parchment)}
  .gstop .items span{display:block;font-size:12px;color:var(--text-faint);line-height:1.55;
    margin-top:2px;text-wrap:pretty}

  .gatefoot{display:flex;align-items:center;justify-content:flex-end;gap:14px;flex-wrap:wrap;
    margin-top:4px;padding-top:18px;border-top:1px solid var(--rule)}
  .gatefoot .agree{margin-right:auto;font-size:12.5px;color:var(--text-faint);
    line-height:1.6;text-wrap:pretty}

  /* ---- the form ---- */
  .q{padding:16px 0}
  .q + .q{border-top:1px solid var(--rule)}
  .q > .qlab{font-size:13.5px;color:var(--parchment);line-height:1.55;text-wrap:pretty}
  .q > .qlab b{font-weight:700;margin-right:5px}
  /* No measure cap. 74ch is a reading measure for prose; these are one-line
     instructions in a wide card, and capping them folded lines that had
     room to sit flat. */
  .q > .qhint{font-size:12.5px;color:var(--text-dim);line-height:1.6;margin-top:6px;
    text-wrap:pretty}
  .q .qbody{margin-top:13px}

  .checks{display:flex;gap:10px;flex-wrap:wrap}
  .chk{display:inline-flex;align-items:center;gap:10px;padding:11px 16px;border-radius:11px;
    border:1px solid var(--border);background:var(--charcoal);color:var(--text-dim);
    font-size:13px;font-weight:600;cursor:pointer;transition:.14s;font-family:inherit}
  .chk:hover:not(:disabled){background:var(--charcoal-3);color:var(--parchment)}
  .chk .bx{width:16px;height:16px;flex:none;border-radius:5px;border:1.5px solid var(--charcoal-4);
    display:grid;place-items:center;transition:.14s}
  .chk .bx svg{width:11px;height:11px;stroke-width:3;color:var(--charcoal-2);opacity:0}
  .chk.on{color:var(--parchment);border-color:rgba(226,182,92,.4);background:rgba(226,182,92,.07)}
  .chk.on .bx{background:var(--gold);border-color:var(--gold)}
  .chk.on .bx svg{opacity:1}
  .chk:disabled{opacity:.42;cursor:not-allowed}
  .chk .no{font-size:11px;font-weight:500;color:var(--text-dim)}

  .sel,.ta,.ti{width:100%;font-family:inherit;font-size:13.5px;color:var(--parchment);
    background:var(--charcoal);border:1px solid var(--border);border-radius:11px;
    padding:12px 14px;transition:.14s}
  .ta{min-height:190px;line-height:1.7;resize:vertical}
  .sel:focus,.ta:focus,.ti:focus{outline:none;border-color:rgba(226,182,92,.5);
    box-shadow:0 0 0 3px rgba(226,182,92,.09)}
  .sel:disabled,.ta:disabled,.ti:disabled{opacity:.5;cursor:not-allowed}
  .ta::placeholder,.ti::placeholder{color:var(--text-dim)}
  .fieldnote{font-size:12px;color:var(--text-dim);margin-top:8px;line-height:1.55}
  .fieldnote.bad{color:#dfa294}
  .counter{float:right;font-variant-numeric:tabular-nums}

  .evrow{display:flex;gap:10px;align-items:flex-start;margin-top:10px}
  .evrow .ti{flex:1}
  .evrow .url{flex:1.4}
  .evx{flex:none;width:38px;height:41px;display:grid;place-items:center;border-radius:10px;
    border:1px solid var(--border);background:var(--charcoal);color:var(--text-dim);
    cursor:pointer;transition:.14s}
  .evx:hover{color:#dfa294;border-color:rgba(193,85,63,.4)}
  .evx svg{width:15px;height:15px;stroke-width:2}

  /* ---- notices ---- */
  .note-a{display:flex;align-items:flex-start;gap:13px;padding:16px 18px;border-radius:12px;
    background:rgba(226,182,92,.06);border:1px solid rgba(226,182,92,.26)}
  .note-a.bad{background:rgba(193,85,63,.07);border-color:rgba(193,85,63,.32)}
  .note-a.good{background:rgba(127,160,90,.07);border-color:rgba(127,160,90,.3)}
  .note-a .si{width:30px;height:30px;flex:none;display:grid;place-items:center;border-radius:9px;
    background:rgba(226,182,92,.1);border:1px solid rgba(226,182,92,.3);color:#e3bd72}
  .note-a.bad .si{background:rgba(193,85,63,.1);border-color:rgba(193,85,63,.3);color:#d29b8d}
  .note-a.good .si{background:rgba(127,160,90,.1);border-color:rgba(127,160,90,.3);color:#9fae8d}
  .note-a .si svg{width:16px;height:16px;stroke-width:2}
  .note-a h4{font-size:14px;font-weight:700;color:#e3bd72}
  .note-a.bad h4{color:#dfa294}
  .note-a.good h4{color:#a8bb92}
  /* 64ch was a reading measure borrowed from body copy. These are one or
     two sentences inside a full-width card, and capping them folds a line
     that had room to sit flat. */
  .note-a p{font-size:13px;color:var(--text-faint);line-height:1.65;margin-top:5px;
    text-wrap:pretty}
  .note-a .acts{margin-top:13px;display:flex;gap:10px;flex-wrap:wrap}

  /* ---- status pills ---- */
  .pill{display:inline-flex;align-items:center;gap:6px;font-size:10.5px;font-weight:800;
    letter-spacing:.08em;text-transform:uppercase;padding:4px 10px;border-radius:100px;
    white-space:nowrap;line-height:1.35}
  .pill.pending {color:#e3bd72;background:rgba(226,182,92,.1);border:1px solid rgba(226,182,92,.32)}
  .pill.accepted{color:#9fae8d;background:rgba(127,160,90,.11);border:1px solid rgba(127,160,90,.32)}
  .pill.rejected{color:#d29b8d;background:rgba(193,85,63,.1);border:1px solid rgba(193,85,63,.34)}
  .pill.perm    {color:#d29b8d;background:rgba(193,85,63,.1);border:1px solid rgba(193,85,63,.34)}
  .pill.temp    {color:#e3bd72;background:rgba(226,182,92,.1);border:1px solid rgba(226,182,92,.32)}

  /* ---- the queue table ---- */
  .qbar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:0 0 16px}
  .qbar .grow{flex:1}
  .qsearch{position:relative;min-width:260px;flex:1;max-width:420px}
  .qsearch svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:15px;
    height:15px;stroke-width:2;color:var(--text-dim);pointer-events:none}
  .qsearch input{width:100%;padding-left:38px}
  .perpage{display:inline-flex;align-items:center;gap:9px;font-size:12.5px;color:var(--text-faint)}
  .perpage select{width:auto;padding:9px 12px}

  .qtable{width:100%;border-collapse:collapse}
  .qtable th{text-align:left;font-size:11px;font-weight:800;letter-spacing:.07em;
    text-transform:uppercase;color:var(--text-dim);padding:0 14px 11px;white-space:nowrap}
  .qtable td{padding:13px 14px;font-size:13px;color:var(--text-faint);
    border-top:1px solid var(--rule);vertical-align:middle}
  .qtable tr:hover td{background:rgba(255,255,255,.014)}
  .qtable td.who{color:var(--parchment);font-weight:600}
  .qtable td.gone{font-style:italic;color:var(--text-dim);font-weight:400}
  .qtable td.num{font-variant-numeric:tabular-nums;white-space:nowrap}
  .qtable .view{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:9px;
    border:1px solid var(--border);background:var(--charcoal);color:var(--parchment);
    font-size:12px;font-weight:600;transition:.14s;white-space:nowrap}
  .qtable .view:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  .qwrap{overflow-x:auto}

  .pager{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap;
    padding-top:16px;margin-top:4px;border-top:1px solid var(--rule)}
  .pager .pinfo{margin-right:auto;font-size:12.5px;color:var(--text-dim)}
  .pager button{min-width:34px;height:34px;padding:0 10px;border-radius:9px;
    border:1px solid var(--border);background:var(--charcoal);color:var(--text-faint);
    font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer;transition:.14s}
  .pager button:hover:not(:disabled){background:var(--charcoal-3);color:var(--parchment)}
  .pager button[aria-current="true"]{background:var(--charcoal-4);color:var(--parchment);
    border-color:rgba(226,182,92,.34)}
  .pager button:disabled{opacity:.36;cursor:not-allowed}
  .pager .gap{color:var(--text-dim);padding:0 2px}

  /* ---- buttons ---- */
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:10px 17px;border-radius:10px;border:1px solid var(--border);background:var(--charcoal);
    color:var(--text-faint);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;
    transition:.14s;white-space:nowrap}
  .btn:hover:not(:disabled){background:var(--charcoal-3);color:var(--parchment)}
  .btn:disabled{opacity:.45;cursor:not-allowed}
  .btn svg{width:15px;height:15px;stroke-width:2}
  /* Flat. The gold used to be a gradient with a lift and a glow on hover and
     a brightness filter on top of that — four things happening on one press,
     which read as the button flickering rather than responding. One solid
     colour, one slightly lighter colour on hover, nothing else. */
  .btn.primary{background:var(--gold);border-color:var(--gold);color:#1a1408}
  .btn.primary:hover:not(:disabled){background:#e8c06a;border-color:#e8c06a;color:#1a1408}
  .btn.primary:active:not(:disabled){background:var(--gold)}
  .btn.danger{color:#dfa294;border-color:rgba(193,85,63,.4);background:rgba(193,85,63,.08)}
  .btn.danger:hover:not(:disabled){background:rgba(193,85,63,.14);color:#e8b5a8}
  .btn.ok{color:#a8bb92;border-color:rgba(127,160,90,.4);background:rgba(127,160,90,.08)}
  .btn.ok:hover:not(:disabled){background:rgba(127,160,90,.14);color:#bccfa6}
  .btn.sm{padding:7px 12px;font-size:12px}

  .blank{display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center;
    padding:52px 20px}
  .blank .ei{width:52px;height:52px;display:grid;place-items:center;border-radius:15px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-dim)}
  .blank .ei svg{width:23px;height:23px;stroke-width:1.7}
  .blank h4{font-size:15.5px;font-weight:700;color:var(--parchment)}
  .blank p{font-size:13px;color:var(--text-faint);max-width:48ch;line-height:1.65;
    text-wrap:pretty}

  /* ---- how an appeal works ----
     The refusal state is the one most players see: the great majority have
     nothing to appeal and arrive here to find out how it works, or because
     something happened and they don't yet know what. A single red box
     saying "you can't" answers a question they didn't ask and leaves them
     going to Discord for the one they did.

     So the page keeps the status line and then explains the process: the
     four stages, what is and isn't appealable, and what an appeal has to
     contain. All of it is true whether or not they can use it today. */
  .flow{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0}
  @media (max-width:1000px){ .flow{grid-template-columns:repeat(2,minmax(0,1fr))} }
  @media (max-width:620px){ .flow{grid-template-columns:1fr} }

  .stp{position:relative;padding:0 20px 4px 0}
  .stp + .stp{padding-left:20px}
  /* The rail runs behind the numbers and stops at the last one, so the
     sequence reads as a track rather than four unrelated boxes. */
  /* --rule is a divider inside a card and all but vanishes on this
     background; the rail has to be readable as a track. */
  .stp::before{content:'';position:absolute;left:0;right:0;top:15px;height:1px;
    background:var(--charcoal-4);z-index:0}
  .stp:first-child::before{left:15px}
  .stp:last-child::before{right:auto;width:15px}
  @media (max-width:1000px){
    .stp:nth-child(2)::before{right:auto;width:15px}
    .stp:nth-child(3)::before{left:15px}
  }
  @media (max-width:620px){ .stp::before{display:none} }

  .stp .n{position:relative;z-index:1;width:30px;height:30px;display:grid;place-items:center;
    border-radius:50%;background:var(--charcoal-2);border:1px solid var(--charcoal-4);
    color:var(--text-faint);font-size:12px;font-weight:800;font-variant-numeric:tabular-nums}
  .stp.gold .n{background:rgba(226,182,92,.1);border-color:rgba(226,182,92,.4);color:#e3bd72}
  .stp h5{font-size:13.5px;font-weight:700;color:var(--parchment);margin-top:13px}
  .stp p{font-size:12.5px;color:var(--text-faint);line-height:1.65;margin-top:7px;
    text-wrap:pretty}

  /* ---- what can and can't be appealed ---- */
  .canlist{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 26px}
  @media (max-width:760px){ .canlist{grid-template-columns:1fr} }
  .canlist .col h5{font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
    color:var(--text-dim);padding-bottom:11px}
  .canrow{display:flex;align-items:flex-start;gap:11px;padding:11px 0;font-size:13px;
    color:var(--parchment);line-height:1.5}
  .canrow + .canrow{border-top:1px solid var(--rule)}
  .canrow .m{flex:none;width:19px;height:19px;display:grid;place-items:center;border-radius:6px;
    margin-top:1px}
  .canrow .m svg{width:11px;height:11px;stroke-width:3}
  .canrow.yes .m{background:rgba(127,160,90,.13);border:1px solid rgba(127,160,90,.34);
    color:#9fae8d}
  .canrow.no .m{background:rgba(193,85,63,.11);border:1px solid rgba(193,85,63,.32);
    color:#d29b8d}
  .canrow.no{color:var(--text-faint)}
  .canrow .s{display:block;font-size:12px;color:var(--text-dim);margin-top:4px;line-height:1.55;
    font-weight:400}

  .asks{display:flex;flex-direction:column}
  .askrow{display:flex;gap:13px;padding:12px 0;font-size:13px;color:var(--text-faint);
    line-height:1.6;text-wrap:pretty}
  .askrow + .askrow{border-top:1px solid var(--rule)}
  .askrow .qn{flex:none;width:22px;color:var(--text-dim);font-weight:800;font-size:12px;
    font-variant-numeric:tabular-nums}
  .askrow b{color:var(--parchment);font-weight:600}

  /* When the appeal has no staff column the grid narrows; the bar above it
     narrows to match, or a wide header sits over a narrow body. */
  body.appeal-solo .lookbar{max-width:900px}

  /* ---- the appeal is the widest view on this page ----
     Two columns of dense fact beside a panel of controls. The site's usual
     1180px left the staff column at 340, which is where "Concluded 21
     minutes ago by testtest" broke onto three lines and the whole panel
     read as squeezed. Only while an appeal is open — the queue and the
     rules are prose and stay at the normal width. */
  body.appeal-wide .content{max-width:1560px}
  body.appeal-wide .apgrid{grid-template-columns:minmax(0,1fr) 400px}
  @media (max-width:1320px){
    body.appeal-wide .apgrid{grid-template-columns:1fr}
  }
  /* No staff column.
     `body.appeal-wide .apgrid` is one specificity point heavier than
     `.apgrid.solo`, so the wide rule was winning on the appellant's own
     appeal too: a 400px staff column that has nothing in it, and their
     appeal squeezed into whatever was left. Stated at the same weight as
     the wide rule, and after it, so it wins.

     Centred rather than left-aligned — one column of text hard against the
     left edge of a wide page reads as a layout that failed to load. */
  body.appeal-solo .content{max-width:1180px}
  body.appeal-solo .apgrid{grid-template-columns:minmax(0,1fr);max-width:940px;margin:0 auto}
  body.appeal-solo .lookbar{max-width:940px;margin-left:auto;margin-right:auto}

  /* ---- one appeal ---- */
  .page-back{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;
    color:var(--text-faint);text-decoration:none;transition:.14s}
  .page-back:hover{color:var(--parchment)}
  .page-back svg{width:16px;height:16px;flex:none;stroke-width:2}
  .lookbar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:12px 16px;
    margin-bottom:20px;border-radius:12px;background:var(--charcoal-2);
    border:1px solid var(--border-soft)}
  .lookbar .grow{flex:1}
  /* A class rule with display beats the browser's [hidden] rule, so an
     element hidden with the attribute stays visible unless this is said.
     That is how the staff-only "every visit is logged" badge ended up on
     the appellant's own appeal. */
  .lookro[hidden]{display:none}
  .lookro{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;font-weight:700;
    letter-spacing:.02em;color:#e3bd72;background:rgba(226,182,92,.08);
    border:1px solid rgba(226,182,92,.3);border-radius:100px;padding:5px 12px;white-space:nowrap}
  .lookro svg{width:13px;height:13px;stroke-width:2.2;flex:none}

  /* Two columns for staff, one for the appellant — the right-hand column is
     entirely staff apparatus, so for a player there is nothing to put in it
     and a 340px hole beside their own appeal reads as something missing. */
  .apgrid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start}
  .apgrid.solo{grid-template-columns:minmax(0,1fr);max-width:900px}
  @media (max-width:1180px){ .apgrid{grid-template-columns:1fr} }

  .kv{display:flex;flex-direction:column;gap:0}
  .kv .r{padding:12px 0;font-size:13px}
  .kv .r + .r{border-top:1px solid var(--rule)}
  .kv .k{font-size:12px;color:var(--text-dim);letter-spacing:.01em}
  .kv .v{color:var(--parchment);font-weight:600;margin-top:4px;text-wrap:pretty}
  .kv .v.soft{color:var(--text-faint);font-weight:500}

  .ansq{padding:16px 0}
  .ansq + .ansq{border-top:1px solid var(--rule)}
  .ansq .n{font-size:13px;color:var(--text-faint);line-height:1.55;text-wrap:pretty}
  .ansq .n b{color:var(--parchment);font-weight:700;margin-right:5px}
  .ansq .a{margin-top:11px}
  .ansq .a.text{font-size:13.5px;color:var(--parchment);line-height:1.75;white-space:pre-wrap;
    padding:15px 17px;border-radius:11px;background:var(--charcoal);
    border:1px solid var(--border);text-wrap:pretty}
  .ansq .a.none{font-size:13px;color:var(--text-dim);font-style:italic}
  .rochk{display:inline-flex;align-items:center;gap:9px;margin-right:16px;font-size:13px;
    color:var(--text-dim)}
  .rochk.on{color:var(--parchment);font-weight:600}
  .rochk .bx{width:15px;height:15px;border-radius:4px;border:1.5px solid var(--charcoal-4);
    display:grid;place-items:center}
  .rochk.on .bx{background:var(--gold);border-color:var(--gold)}
  .rochk .bx svg{width:10px;height:10px;stroke-width:3.2;color:var(--charcoal-2);opacity:0}
  .rochk.on .bx svg{opacity:1}

  .evlist{display:flex;flex-direction:column;gap:8px}
  .evitem{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:11px;
    background:var(--charcoal);border:1px solid var(--border)}
  .evitem .ic{width:28px;height:28px;flex:none;display:grid;place-items:center;border-radius:8px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-faint)}
  .evitem .ic svg{width:14px;height:14px;stroke-width:2}
  .evitem .b{min-width:0;flex:1}
  .evitem a{color:#cbb07a;font-size:13px;font-weight:600;word-break:break-all}
  .evitem a:hover{text-decoration:underline}
  .evitem .nt{font-size:12.5px;color:var(--text-faint);margin-top:4px;line-height:1.55}

  /* ---- comments ---- */
  .cm{border-radius:12px;border:1px solid var(--border);background:var(--charcoal);
    overflow:hidden}
  .cm + .cm{margin-top:11px}
  .cm .h{display:flex;align-items:center;gap:9px;flex-wrap:wrap;padding:10px 15px;
    background:var(--charcoal-3);border-bottom:1px solid var(--border);font-size:12.5px;
    color:var(--text-faint)}
  .cm .h b{color:var(--parchment);font-weight:700}
  .cm .h .when{margin-left:auto;color:var(--text-dim);font-size:12px}
  .cm .body{padding:14px 16px;font-size:13.5px;color:var(--parchment);line-height:1.7;
    white-space:pre-wrap;text-wrap:pretty}
  .cm.staffonly{border-color:rgba(193,85,63,.3)}
  .cm.staffonly .h{background:rgba(193,85,63,.09);border-bottom-color:rgba(193,85,63,.24)}
  /* Named .ctag, not .tag.
     The shell this page is built from already owns `.tag` — it is the badge
     on a bulletin's image, and it is position:absolute. Reusing the name
     lifted every comment's Staff / Staff only pill out of its comment and
     stacked them all in the top-left corner of the page. */
  .ctag{display:inline-flex;align-items:center;font-size:9.5px;font-weight:800;
    letter-spacing:.08em;text-transform:uppercase;padding:2px 7px;border-radius:100px;
    line-height:1.5;position:static}
  .ctag.staff{color:#9fae8d;background:rgba(127,160,90,.12);border:1px solid rgba(127,160,90,.3)}
  .ctag.only {color:#d29b8d;background:rgba(193,85,63,.12);border:1px solid rgba(193,85,63,.34)}

  .composer{margin-top:16px}
  .composer .row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:11px}
  .composer .row .grow{flex:1}

  /* A switch, not a tick-box: which of the two audiences a comment is going
     to is the single most consequential choice on this page, and it should
     not look like a checkbox you skim past. */
  .aud{display:inline-flex;border-radius:10px;border:1px solid var(--border);
    background:var(--charcoal);overflow:hidden}
  .aud button{padding:8px 14px;border:0;background:transparent;color:var(--text-dim);
    font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer;transition:.14s}
  .aud button:hover{color:var(--parchment)}
  .aud button.on{background:var(--charcoal-4);color:var(--parchment)}
  .aud button.on[data-aud="staff"]{background:rgba(193,85,63,.16);color:#dfa294}

  /* ---- the staff panel ---- */
  .panelfield + .panelfield{margin-top:18px;padding-top:18px;border-top:1px solid var(--rule)}
  .panelfield label{display:block;font-size:12px;font-weight:700;color:var(--text-dim);
    letter-spacing:.02em;margin-bottom:9px}
  .panelfield .go{display:flex;justify-content:flex-end;margin-top:10px}
  .panelfield .hint{font-size:12px;color:var(--text-dim);line-height:1.6;margin-top:9px;
    text-wrap:pretty}
  .panelfield .hint.warn{color:#c9a06a}

  .log{display:flex;flex-direction:column;max-height:520px;overflow:auto}
  .logrow{display:flex;gap:11px;padding:11px 0;font-size:12.5px;color:var(--text-faint);
    line-height:1.55}
  .logrow + .logrow{border-top:1px solid var(--rule)}
  .logrow .dot{flex:none;width:7px;height:7px;border-radius:50%;background:var(--charcoal-4);
    margin-top:6px}
  .logrow.act .dot{background:#c9a06a}
  .logrow b{color:var(--parchment);font-weight:600}
  .logrow .t{color:var(--text-dim);font-variant-numeric:tabular-nums}

  /* ---- condensed ----
     The appeal page ran to a screen and a half of air before the reply box.
     Everything below tightens the rhythm without changing the type sizes:
     a handler reads three of these in a row and should not have to scroll
     each one twice. */
  .card-b{padding:14px 22px 18px}
  .kv .r{padding:10px 0}
  .kv .v{margin-top:3px}
  .ansq{padding:13px 0}
  .ansq .a{margin-top:9px}
  .ansq .a.text{padding:13px 15px;line-height:1.65}
  .panelfield + .panelfield{margin-top:15px;padding-top:15px}
  .panelfield .hint{margin-top:7px}
  .logrow{padding:9px 0}
  .cm .body{padding:12px 15px}
  .apgrid{gap:18px}

  /* The appellant's name in the card header, as a link to their record. */
  .wholink{display:inline-flex;align-items:center;gap:6px;color:#cbb07a;font-weight:600}
  .wholink:hover{color:var(--gold);text-decoration:underline}
  .wholink svg{width:12px;height:12px;stroke-width:2.2;flex:none;opacity:.7}

  /* A field somebody may read but not change. Reads as a value, not as a
     disabled control they should keep trying to click. */
  .panelfield .ro{font-size:13.5px;font-weight:600;color:var(--parchment);
    padding:11px 14px;border-radius:11px;background:var(--charcoal);
    border:1px solid var(--border)}

  /* The two comments that settled the appeal: the verdict, and the overrule
     that reversed it. Bordered rather than merely badged — on a long thread
     the badge is the thing you're scanning for, and a tinted edge finds it
     from the scrollbar. */
  .ctag.verdict {color:#e3bd72;background:rgba(226,182,92,.11);border:1px solid rgba(226,182,92,.34)}
  .ctag.overrule{color:#9fae8d;background:rgba(127,160,90,.13);border:1px solid rgba(127,160,90,.36)}
  .cm.is-verdict {border-color:rgba(226,182,92,.3)}
  .cm.is-verdict .h{background:rgba(226,182,92,.07);border-bottom-color:rgba(226,182,92,.22)}
  .cm.is-overrule{border-color:rgba(127,160,90,.32)}
  .cm.is-overrule .h{background:rgba(127,160,90,.08);border-bottom-color:rgba(127,160,90,.24)}

  /* Long unbroken strings — a pasted log line, a URL, or somebody leaning on
     one key — have nothing to wrap at, so they ran straight out of the box.
     pre-wrap keeps the author's line breaks; these let the browser break
     mid-word when there is no other option. */
  .ansq .a.text,
  .cm .body{overflow-wrap:anywhere;word-break:break-word}

  /* ---- past appeals ---- */
  .hlist{display:flex;flex-direction:column;gap:8px}
  .hrow{display:flex;align-items:center;gap:13px;padding:12px 14px;border-radius:11px;
    background:var(--charcoal);border:1px solid var(--border);transition:.14s}
  .hrow:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  .hrow .hst{flex:none}
  .hrow .hmain{flex:1;min-width:0}
  .hrow .ht{display:block;font-size:13px;font-weight:600;color:var(--parchment)}
  .hrow .hs{display:block;font-size:12px;color:var(--text-faint);margin-top:3px;
    line-height:1.5;text-wrap:pretty}
  .hrow .hgo{flex:none;color:var(--text-dim);display:grid;place-items:center}
  .hrow .hgo svg{width:15px;height:15px;stroke-width:2.2}
  .hrow:hover .hgo{color:var(--parchment)}

  /* =====================================================================
     STAFF REPORTS — the bits this page adds

     Everything above is shared with Ban Appeals, because the two pages do
     the same shape of work and a handler moving between them should not
     have to relearn where anything is. What follows is only what a report
     needs and an appeal doesn't: a staff picker, a wider question grid,
     and a third view button.
     ===================================================================== */

  /* Three views, not two. At two the buttons are halves; at three they are
     thirds, and below 900px they stack rather than leaving a runt. */
  .qtabs.three{grid-template-columns:repeat(3,1fr)}
  @media (max-width:980px){ .qtabs.three{grid-template-columns:1fr} }

  /* The form reuses the appeal form's controls; these are the aliases for
     the two names this page uses for them. */
  .inp{width:100%;font-family:inherit;font-size:13.5px;color:var(--parchment);
    background:var(--charcoal);border:1px solid var(--border);border-radius:11px;
    padding:12px 14px;transition:.14s}
  .inp:focus{outline:none;border-color:rgba(226,182,92,.5);
    box-shadow:0 0 0 3px rgba(226,182,92,.09)}
  .inp:disabled{opacity:.5;cursor:not-allowed}
  .inp::placeholder{color:var(--text-dim)}
  .inp.sm{font-size:12.5px;padding:10px 13px}
  .q > .qn{font-size:13.5px;color:var(--parchment);line-height:1.55;text-wrap:pretty}
  .q > .qn b{font-weight:700;margin-right:5px}
  .q .hint{font-size:12.5px;color:var(--text-dim);line-height:1.6;margin-top:8px;
    text-wrap:pretty}

  /* ---- the staff picker ----
     A list, not a text box. Typing a name meant getting it exactly right,
     and getting it wrong meant a report filed against nobody — which is a
     report no one can be allocated and no one can answer. Grouped by group
     because the reporter often knows the rank and not the spelling. */
  .pickrow{display:flex;gap:10px;align-items:center}
  .pickrow .sel{flex:1}
  .chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:11px}
  .chip{display:inline-flex;align-items:center;gap:9px;padding:7px 8px 7px 13px;
    border-radius:100px;background:var(--charcoal-3);border:1px solid var(--charcoal-4);
    font-size:12.5px;color:var(--text-faint)}
  .chip b{color:var(--parchment);font-weight:700}
  .chip .cg{font-size:11px;color:var(--text-dim)}
  .chip .cx{width:20px;height:20px;flex:none;display:grid;place-items:center;border-radius:50%;
    border:0;background:transparent;color:var(--text-dim);cursor:pointer;transition:.14s}
  .chip .cx:hover{color:#dfa294;background:rgba(193,85,63,.14)}
  .chip .cx svg{width:11px;height:11px;stroke-width:2.6}
  .chip-none{font-size:12.5px;color:var(--text-dim);font-style:italic}

  /* Three short answers on one line. They are read together — when, how
     often, and where — so they are asked together. */
  .frow{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
  @media (max-width:820px){ .frow{grid-template-columns:1fr} }
  .flab{display:block;font-size:11px;font-weight:800;letter-spacing:.09em;
    text-transform:uppercase;color:var(--stone);margin-bottom:7px}

  /* ---- evidence rows ---- */
  .evrow .ic{width:32px;height:41px;flex:none;display:grid;place-items:center;
    color:var(--text-dim)}
  .evrow .ic svg{width:15px;height:15px;stroke-width:2}
  .evrow .eb{flex:1;display:flex;flex-direction:column;gap:8px;min-width:0}
  .btn.icon{flex:none;width:38px;height:41px;padding:0}
  .btn.icon svg{width:15px;height:15px;stroke-width:2}

  .formfoot{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;
    margin-top:20px;padding-top:18px;border-top:1px solid var(--rule)}

  /* The opening comment and the decision are marked in the thread. Both
     read differently from an ordinary reply: "we are looking at this" in
     the middle of a thread is a remark, and the same words marked as the
     opening comment are the acknowledgement the reporter was waiting for. */
  .ctag.mark{color:#e3bd72;background:rgba(226,182,92,.11);
    border:1px solid rgba(226,182,92,.34)}
  .cm.marked{border-color:rgba(226,182,92,.3)}

  /* ---- the queue ---- */
  .qtable td.who .subline{display:block;font-size:11.5px;color:var(--text-dim);
    font-weight:500;margin-top:3px}
  .sec-h .n .grp{color:var(--tone-text);font-weight:700}
  .card-lede + .card-lede{margin-top:11px}
  /* A row in the queue that names the person reading it. Only Management
     and Founders ever see one, and it is the single most important thing
     about that row — so it is said on the row, not left to be discovered
     after they open it. */
  .youtag{display:inline-block;margin-left:9px;font-size:9.5px;font-weight:800;
    letter-spacing:.09em;text-transform:uppercase;padding:2px 8px;border-radius:100px;
    color:#dfa294;background:rgba(193,85,63,.12);border:1px solid rgba(193,85,63,.34);
    vertical-align:middle}

  /* =====================================================================
     SUBMIT — form and reference, side by side

     The information page is gone. It was a wall you had to read and
     dismiss before the form appeared, and nobody reads a wall they can
     dismiss. What it said now sits in the rail on the right, in front of
     the reporter while they write rather than two screens behind them.
     ===================================================================== */
  /* Two columns of cards on the page background — the same shape as one
     report, so the two views of this page are built the same way. */
  .split{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:20px;align-items:start}
  .split > .card{margin-top:0}
  .rail .card + .card{margin-top:16px}
  @media (max-width:1080px){ .split{grid-template-columns:1fr} }
  .sticky{position:sticky;top:calc(var(--header-h, 64px) + 18px)}

  /* Native select arrows were the browser's, and on a dark control they
     render as a black triangle in a black box. Replaced with the same
     chevron the rest of the UCP uses, drawn as a background image so it
     works on a real <select> rather than needing a fake one. */
  .sel{appearance:none;-webkit-appearance:none;-moz-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238a7f70' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 13px center;background-size:15px;
    padding-right:38px}
  .sel::-ms-expand{display:none}
  /* A dark <option> list is the OS's, not ours; this is the most a page can
     say about it, and it stops white-on-white on Windows. */
  .sel option,.sel optgroup{background:var(--charcoal-2);color:var(--parchment)}
  input[type=datetime-local]::-webkit-calendar-picker-indicator{filter:invert(.62) sepia(.2)}

  .lede{font-size:13.5px;color:var(--text-faint);line-height:1.65;text-wrap:pretty}
  .qs{margin-top:6px}
  .formfoot{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-top:20px;
    padding-top:18px;border-top:1px solid var(--rule)}
  .formfoot p{flex:1;min-width:260px;font-size:12.5px;color:var(--text-dim);line-height:1.6;
    text-wrap:pretty}
  .formfoot p b{color:var(--text-faint);font-weight:600}

  /* ---- who it is about ----
     Rows, not pills. A pill had to carry a name, a group and a remove
     button in one line of 12px text, and all three ended up at the same
     weight — the name no louder than the rank it was in. */
  .picked{display:flex;flex-direction:column;border:1px solid var(--rule);border-radius:12px;
    overflow:hidden;margin-top:12px}
  .picked.none{padding:13px 15px;font-size:12.5px;color:var(--text-dim);font-style:italic;
    background:var(--charcoal)}
  .prow{display:flex;align-items:center;gap:13px;padding:11px 13px 11px 15px;
    background:var(--charcoal)}
  .prow + .prow{border-top:1px solid var(--rule)}
  .prow .pd{width:8px;height:8px;border-radius:50%;flex:none;background:var(--tone-text,var(--stone))}
  .prow.unk .pd{background:transparent;border:1.5px dashed var(--stone)}
  .prow .pb{min-width:0;flex:1;display:flex;flex-direction:column;gap:2px}
  .prow .pn{font-size:13.5px;font-weight:700;color:var(--parchment)}
  .prow .pg{font-size:11.5px;color:var(--tone-text,var(--text-dim))}
  .prow.unk .pn{color:var(--text-faint)}
  .prow.unk .pg{color:var(--text-dim)}
  .prow .px{width:28px;height:28px;flex:none;display:grid;place-items:center;border-radius:8px;
    border:1px solid transparent;background:transparent;color:var(--text-dim);cursor:pointer;
    transition:.14s}
  .prow .px:hover{color:#dfa294;background:rgba(193,85,63,.1);border-color:rgba(193,85,63,.3)}
  .prow .px svg{width:13px;height:13px;stroke-width:2.4}

  /* A quiet second way to answer the same question. Not a checkbox in the
     row above it: "I don't know" is a different answer, not a modifier of
     the one they were about to give. */
  .linkbtn{display:inline-flex;align-items:center;gap:8px;margin-top:11px;padding:0;
    border:0;background:none;font-family:inherit;font-size:12.5px;font-weight:600;
    color:var(--text-faint);cursor:pointer;transition:.14s}
  .linkbtn:hover{color:var(--gold)}
  .linkbtn svg{width:14px;height:14px;stroke-width:2}

  /* The title, shown rather than typed. */
  .tprevwrap{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin-top:12px;
    padding:11px 14px;border-radius:11px;background:var(--charcoal-3);
    border:1px solid var(--border-soft)}
  .tprevwrap .k{font-size:9.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
    color:var(--stone);flex:none}
  .tprev{font-size:13.5px;font-weight:600;color:var(--parchment);min-width:0;
    overflow-wrap:anywhere;background:none;border:0;padding:0;display:inline}
  .tprev.blank{color:var(--text-dim);font-weight:500;font-style:italic}

  /* ---- the rail ---- */
  .tline{display:flex;flex-direction:column}
  .tstep{display:flex;gap:12px;padding:0 0 16px;position:relative}
  .tstep:last-child{padding-bottom:0}
  .tstep .d{flex:none;width:11px;height:11px;border-radius:50%;margin-top:3px;
    background:var(--charcoal-3);border:2px solid var(--charcoal-4);z-index:1}
  .tstep.on .d{background:var(--gold);border-color:var(--gold);
    box-shadow:0 0 0 4px rgba(226,182,92,.13)}
  .tstep::before{content:"";position:absolute;left:5px;top:14px;bottom:-2px;width:1px;
    background:var(--rule)}
  .tstep:last-child::before{display:none}
  .tstep .b{min-width:0}
  .tstep .t{font-size:12.5px;font-weight:700;color:var(--parchment)}
  .tstep.on .t{color:var(--gold)}
  .tstep .s{font-size:11.5px;color:var(--text-dim);line-height:1.55;margin-top:3px;
    text-wrap:pretty}

  /* The four categories, in the colours they keep on the concluded report —
     so the label on the verdict is one the reporter has already seen. */
  .cats{display:flex;flex-direction:column;gap:1px;background:var(--rule);
    border:1px solid var(--rule);border-radius:11px;overflow:hidden}
  .cat{background:var(--charcoal-2);padding:10px 12px}
  .cat .n{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700}
  .cat .n::before{content:"";width:6px;height:6px;border-radius:50%;flex:none;
    background:currentColor}
  .cat.c1 .n{color:#d98a78} .cat.c2 .n{color:#e3bd72}
  .cat.c3 .n{color:#93a7cb} .cat.c4 .n{color:#9a9082}
  .cat p{font-size:11px;color:var(--text-dim);line-height:1.5;margin-top:4px;text-wrap:pretty}

  .rbox{padding:12px 14px;border-radius:11px;border:1px solid rgba(226,182,92,.24);
    background:rgba(226,182,92,.05);margin-top:18px}
  .rbox h5{font-size:12px;font-weight:700;color:#e3bd72}
  .rbox p{font-size:11.5px;color:var(--text-faint);line-height:1.6;margin-top:5px;
    text-wrap:pretty}
  /* The live character count. Not floated — a float dropped it onto the
     line below its own sentence and read as a stray number. */
  .count{font-variant-numeric:tabular-nums}
  .count.ok{color:var(--text-faint)}

  /* ---- checkboxes ----
     The native box does not take a colour on a dark control, and the label
     sat hard against it because a bare <input> has no margin of its own.
     Drawn instead: a rounded square that fills gold with a tick. */
  .chkline{display:inline-flex;align-items:center;gap:10px;margin-top:11px;
    font-size:12.5px;color:var(--text-faint);cursor:pointer;user-select:none;line-height:1.4}
  .chkline:hover{color:var(--parchment)}
  .chkline input{position:absolute;opacity:0;width:0;height:0}
  .chkline .bx{width:16px;height:16px;flex:none;border-radius:5px;
    border:1.5px solid var(--charcoal-4);background:var(--charcoal);display:grid;
    place-items:center;transition:.14s}
  .chkline .bx svg{width:10px;height:10px;stroke-width:3.2;color:#1a1611;opacity:0;
    transition:.12s}
  .chkline input:checked + .bx{background:var(--gold);border-color:var(--gold)}
  .chkline input:checked + .bx svg{opacity:1}
  .chkline input:focus-visible + .bx{box-shadow:0 0 0 3px rgba(226,182,92,.22)}
  .chkline span:last-child{min-width:0}
  /* A Save that belongs to the card rather than to one field inside it.
     `.panelfield .go` only reaches the ones nested in a field. */
  .card-b > .go{display:flex;justify-content:flex-end;margin-top:12px}
  /* The per-page control is a 60px select, and the chevron padding meant
     for a full-width one left no room for the number. */
  .perpage .sel{width:auto;padding:8px 30px 8px 11px;font-size:12.5px;
    background-position:right 9px center;background-size:13px}
  /* A footnote inside a rail card, not a card of its own. */
  .railnote{display:flex;gap:11px;margin-top:16px;padding-top:14px;
    border-top:1px solid var(--rule);font-size:11.5px;color:var(--text-dim);line-height:1.6;
    text-wrap:pretty}
  .railnote .i{flex:none;width:15px;margin-top:1px;color:var(--stone)}
  .railnote .i svg{width:14px;height:14px;stroke-width:2}
  .railnote b{color:var(--text-faint);font-weight:700}
  /* The compact variant of the history list: one line, three columns, and
     the title truncated rather than wrapped. */
  .hrow.tight{display:flex;align-items:center;gap:11px;padding:9px 12px}
  .hrow.tight .ht{flex:1;min-width:0;font-size:12.5px;font-weight:600;color:var(--parchment);
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hrow.tight .hst{flex:none}
  .hrow.tight .hw{flex:none;font-size:11.5px;color:var(--text-dim);white-space:nowrap}
  .hrow.tight .hgo{flex:none}
  .hlist.tight{gap:6px}
  .pager.tight{margin-top:12px;gap:6px}
  .pager.tight .pinfo{font-size:11.5px}

  /* ---- the notification list ----
     Separate boxes with a gap, not one bordered block divided by hairlines.
     Each notification is a separate event about a separate thing; run
     together they read as one paragraph and the eye has to find the seams. */
  .nlist{display:flex;flex-direction:column;gap:10px}
  .nrow{display:flex;align-items:flex-start;gap:14px;padding:14px 15px;background:var(--charcoal);
    border:1px solid var(--border);border-radius:12px;cursor:pointer;transition:.13s}
  .nrow:hover{background:var(--charcoal-3);border-color:var(--charcoal-4)}
  /* Unread is a tint and a weight, not a colour: a list where every unread
     row is gold is a list where nothing stands out. */
  .nrow.unread{background:rgba(226,182,92,.045);border-color:rgba(226,182,92,.22)}
  .nrow .nic{flex:none;width:34px;height:34px;display:grid;place-items:center;border-radius:10px;
    background:var(--charcoal-3);border:1px solid var(--border);color:var(--text-dim)}
  .nrow.unread .nic{color:#e3bd72;background:rgba(226,182,92,.1);
    border-color:rgba(226,182,92,.28)}
  .nrow .nic svg{width:15px;height:15px;stroke-width:2}
  .nrow .nb{min-width:0;flex:1;display:flex;flex-direction:column;gap:3px}
  .nrow .nt{font-size:13.5px;font-weight:500;color:var(--text-faint);line-height:1.45}
  .nrow.unread .nt{color:var(--parchment);font-weight:700}
  .nrow .ns{font-size:12.5px;color:var(--text-dim);line-height:1.5;text-wrap:pretty}
  .nrow .nw{font-size:11.5px;color:var(--text-dim);margin-top:2px}
  .ntick{flex:none;width:28px;height:28px;display:grid;place-items:center;border-radius:8px;
    border:1px solid var(--border);background:var(--charcoal-2);color:var(--text-dim);
    cursor:pointer;transition:.14s}
  .ntick svg{width:14px;height:14px;stroke-width:2.6}
  .ntick:hover:not(:disabled){color:var(--ok);border-color:rgba(127,160,90,.45);
    background:rgba(127,160,90,.1)}
  .ntick:disabled{color:var(--ok);border-color:rgba(127,160,90,.3);
    background:rgba(127,160,90,.08);cursor:default}
  .pager{margin-top:14px}
  /* The count and its button on one line in the card header. */
  .card-h .aside{display:inline-flex;align-items:center;gap:12px}
  .card-h .aside .btn{margin-left:2px}
  /* Two actions per row: keep it (tick) or throw it away (bin). Separate
     controls because they are separate intentions — one control that did
     both would be wrong for whichever the reader meant. */
  .nacts{display:flex;align-items:center;gap:6px;flex:none}
  .ndel{width:28px;height:28px;display:grid;place-items:center;border-radius:8px;
    border:1px solid var(--border);background:var(--charcoal-2);color:var(--text-dim);
    cursor:pointer;transition:.14s}
  .ndel svg{width:14px;height:14px;stroke-width:2}
  .ndel:hover{color:#dfa294;border-color:rgba(193,85,63,.45);background:rgba(193,85,63,.1)}
</style>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>

      <div class="ahead">
        <span class="qi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/></svg></span>
        <div class="tx">
          <h1>Notifications</h1>
          <p>Everything the UCP has told you, newest first. Opening one takes you to what it
            is about; the tick keeps it and the bin throws it away.</p>
        </div>
      </div>

      <div class="qtabs" id="qtabs" role="tablist" aria-label="Notification filters"></div>
      <div id="qbody"></div>

    </main>
  </div>
<div class="toast" id="toast"><span id="toastMsg"></span></div>

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
     NOTIFICATIONS — the full list

     The bell in the top bar is built by assets/js/ucp.js and is on every
     page. This is the page behind it, and it is the ONLY copy: the
     dashboard used to carry its own list, its own styles and twelve
     hardcoded rows. One document, the same reasoning as the sidebar.

     Everything here is drawn from the components this stylesheet already
     has — cards, filter pills, the row list, the chevron pager — so the
     page inherits every future change to them rather than restating them.
     ===================================================================== */
  /* Ten to a page, the same as a player's administrative record — the two
   lists are the same shape of thing and should not page differently. */
  var NOTES = [], NFILTER = 'all', NPAGE = 1, NPER = 10, NLOADED = false;

  var NI = {
    appeal:'<path d="M3 21h8"/><path d="M6.5 17.5l7-7"/><path d="M11 4l6 6-2.5 2.5-6-6z"/>'+
           '<path d="M15 14l4.5 4.5"/>',
    report:'<path d="M5 21V4h13l-2.5 4L18 12H5"/>',
    system:'<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    tick:'<path d="M20 6L9 17l-5-5"/>',
    bin:'<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6 7l1 13h10l1-13"/>'+
        '<path d="M10 11v6M14 11v6"/>',
    chev:'<path d="M9 6l6 6-6 6"/>'
  };
  function ni(n){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">'+NI[n]+'</svg>'; }

  function nWhen(ts){
    if(window.UCP && UCP.relTime) return UCP.relTime(ts);
    var d = new Date(ts*1000), p = function(x){ return String(x).padStart(2,'0'); };
    return p(d.getUTCDate())+'/'+p(d.getUTCMonth()+1)+'/'+d.getUTCFullYear();
  }

  function nUnread(){ return NOTES.filter(function(n){ return !n.read; }).length; }

  function nFiltered(){
    return NOTES.filter(function(n){
      return NFILTER === 'all' ? true : (NFILTER === 'unread' ? !n.read : n.read);
    });
  }

  function nRow(n){
    /* The whole row is the link, and the tick is a button inside it —
       acknowledging something is not the same as going to read it, and one
       control that did both would make the list unusable for whichever of
       the two you didn't mean. */
    return '<div class="nrow'+(n.read ? '' : ' unread')+'" data-id="'+n.id+'"'+
      (n.url ? ' data-url="'+escapeHtml(n.url)+'"' : '')+'>'+
      '<span class="nic">'+ni(NI[n.area] ? n.area : 'system')+'</span>'+
      '<span class="nb">'+
        '<span class="nt">'+escapeHtml(n.title)+'</span>'+
        (n.body ? '<span class="ns">'+escapeHtml(n.body)+'</span>' : '')+
        '<span class="nw">'+escapeHtml(nWhen(n.at))+
          (n.actor ? ' · '+escapeHtml(n.actor) : '')+'</span>'+
      '</span>'+
      '<span class="nacts">'+
        '<button class="ntick" type="button" data-tick="'+n.id+'"'+(n.read ? ' disabled' : '')+
          ' title="'+(n.read ? 'Read' : 'Mark as read')+'"'+
          ' aria-label="'+(n.read ? 'Read' : 'Mark as read')+'">'+ni('tick')+'</button>'+
        '<button class="ndel" type="button" data-del="'+n.id+'" title="Delete"'+
          ' aria-label="Delete this notification">'+ni('bin')+'</button>'+
      '</span>'+
    '</div>';
  }

  function nPager(pages, all_){
    if(pages <= 1) return '';
    var out = [], seen = {};
    function push(p){
      if(p < 1 || p > pages || seen[p]) return; seen[p] = 1;
      out.push('<button class="pg'+(p === NPAGE ? ' on' : '')+'" data-np="'+p+'">'+p+'</button>');
    }
    push(1);
    if(NPAGE - 2 > 2) out.push('<span class="pg-gap">…</span>');
    for(var i = NPAGE - 1; i <= NPAGE + 1; i++) push(i);
    if(NPAGE + 2 < pages - 1) out.push('<span class="pg-gap">…</span>');
    push(pages);

    return '<div class="pager">'+
      '<span class="pinfo">'+all_+(all_ === 1 ? ' notification' : ' notifications')+
      ' · page '+NPAGE+' of '+pages+'</span>'+
      '<button class="pg" data-np="'+(NPAGE-1)+'"'+(NPAGE <= 1 ? ' disabled' : '')+
        ' aria-label="Previous page">'+
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">'+
        '<path d="M15 6l-6 6 6 6"/></svg></button>'+
      out.join('')+
      '<button class="pg" data-np="'+(NPAGE+1)+'"'+(NPAGE >= pages ? ' disabled' : '')+
        ' aria-label="Next page">'+ni('chev')+'</button>'+
    '</div>';
  }

  function renderNotes(){
    var host = document.getElementById('qbody');
    var unread = nUnread(), all = nFiltered();
    var pages = Math.max(1, Math.ceil(all.length / NPER));
    if(NPAGE > pages) NPAGE = pages;
    var slice = all.slice((NPAGE - 1) * NPER, NPAGE * NPER);

    var tabs = [['all','All',NOTES.length],
                ['unread','Unread',unread],
                ['read','Read',NOTES.length - unread]].map(function(t){
      return '<button class="qtab" data-nf="'+t[0]+'" aria-selected="'+
        (t[0] === NFILTER ? 'true' : 'false')+'">'+t[1]+
        ' <span class="pill '+(t[0] === 'unread' ? 'pending' : 'accepted')+'">'+t[2]+
        '</span></button>';
    }).join('');

    var body = slice.length
      ? '<div class="nlist">'+slice.map(nRow).join('')+'</div>' + nPager(pages, all.length)
      : '<div class="blank"><span class="ei">'+ni('system')+'</span><h4>'+
        (NLOADED ? 'Nothing here' : 'Loading…')+'</h4><p>'+
        (!NLOADED ? 'One moment.'
          : NOTES.length ? 'No '+NFILTER+' notifications.'
          /* Deliberately not a list of what CAN appear here. This is the
             whole UCP's notification area, and naming the two queues that
             use it today would be wrong the week a third one does. */
          : 'Anything that needs your attention will turn up here.')+'</p></div>';

    /* "Mark all read" belongs in the header, beside the count it changes.
       Floated above the first row it read as that row's own control and sat
       hard against it. */
    host.innerHTML =
      '<div class="qfilters">'+tabs+'</div>'+
      '<div class="card"><div class="card-h"><h3>Everything</h3>'+
        '<span class="aside">'+unread+' unread of '+NOTES.length+
          (unread ? ' <button class="btn sm" id="readall">Mark all read</button>' : '')+
          (NOTES.length ? ' <button class="btn sm" id="delall">Delete all</button>' : '')+
        '</span></div>'+
        '<div class="card-b">'+ body +
          '<div class="fieldnote">Kept for 14 days, then removed on their own. Deleting one '+
          'here doesn’t change whatever it points at.</div>'+
        '</div></div>';

    Array.prototype.forEach.call(host.querySelectorAll('[data-nf]'), function(b){
      b.addEventListener('click', function(){
        NFILTER = b.getAttribute('data-nf'); NPAGE = 1; renderNotes();
      });
    });
    Array.prototype.forEach.call(host.querySelectorAll('[data-np]'), function(b){
      b.addEventListener('click', function(){
        NPAGE = +b.getAttribute('data-np'); renderNotes(); window.scrollTo(0,0);
      });
    });
    Array.prototype.forEach.call(host.querySelectorAll('[data-tick]'), function(b){
      b.addEventListener('click', function(e){
        e.stopPropagation();
        markRead(+b.getAttribute('data-tick'));
      });
    });
    Array.prototype.forEach.call(host.querySelectorAll('[data-del]'), function(b){
      b.addEventListener('click', function(e){
        e.stopPropagation();
        removeNote(+b.getAttribute('data-del'));
      });
    });
    var da = document.getElementById('delall');
    if(da) da.addEventListener('click', function(){
      if(!confirm('Delete every notification? The appeals and reports they point at are not '+
                  'touched.')) return;
      NOTES = []; renderNotes();
      UCP.post('notification-delete.php', { all: true }).then(function(){
        if(UCP.notifications) UCP.notifications();
      });
    });
    Array.prototype.forEach.call(host.querySelectorAll('.nrow[data-url]'), function(r){
      r.addEventListener('click', function(){
        var id = +r.getAttribute('data-id');
        markRead(id, true);
        window.location.href = r.getAttribute('data-url');
      });
    });
    var ra = document.getElementById('readall');
    if(ra) ra.addEventListener('click', markAllRead);
  }

  /* Marked here AND on the bell, so the badge and the list never disagree
     about what is unread. */
  function markRead(id, quiet){
    var n = null;
    for(var i=0;i<NOTES.length;i++) if(NOTES[i].id === id) n = NOTES[i];
    if(!n || n.read) return;
    n.read = true;
    if(!quiet) renderNotes();
    UCP.post('notification-read.php', { id: id }).then(function(){
      if(UCP.notifications) UCP.notifications();
    });
  }

  /* Gone from the list before the request lands. Undoing a delete is not a
     thing this offers, and waiting on the network to watch a row you have
     plainly finished with disappear is worse than the tiny risk of it
     coming back on a failed request and a refresh. */
  function removeNote(id){
    NOTES = NOTES.filter(function(n){ return n.id !== id; });
    renderNotes();
    UCP.post('notification-delete.php', { id: id }).then(function(){
      if(UCP.notifications) UCP.notifications();
    });
  }

  function markAllRead(){
    NOTES.forEach(function(n){ n.read = true; });
    renderNotes();
    UCP.post('notification-read.php', { all: true }).then(function(){
      if(UCP.notifications) UCP.notifications();
    });
  }

  function loadNotes(){
    return UCP.get('notifications.php?limit=50').then(function(d){
      NLOADED = true;
      NOTES = (d && d.ok === true && d.notifications) ? d.notifications : [];
      renderNotes();
    }).catch(function(){ NLOADED = true; renderNotes(); });
  }

  renderNotes();

  UCP.get('session.php').then(function(d){
    if(!d || d.authenticated !== true){
      window.location.replace('/login?return='+encodeURIComponent('/dashboard/notifications'));
      return;
    }
    var an = document.getElementById('acctName'), ar = document.getElementById('acctRole');
    if(an) an.textContent = d.name || '';
    if(ar) ar.textContent = d.role || 'Member';
    if(window.UCP && UCP.rememberMe) UCP.rememberMe(d);
    return loadNotes();
  }).catch(function(){ toast('Could not reach the server'); });

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
</body>
</html>
