<?php
/**
 * Credit Store.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'Credit Store · BlaineSide';
$PAGE_HEADING = 'Credit Store';
$PAGE_ICON = 'store';
$PAGE_LEDE = 'Buy credits, spend them, and keep the receipts.';
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

</style>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <div class="stack">
        </div>

        <div class="tabs" id="tabs"></div>
        <div id="body"><div class="empty">Loading&hellip;</div></div>
      </div>
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
</body>
</html>
