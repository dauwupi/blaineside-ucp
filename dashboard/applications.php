<?php
/**
 * Application Panel.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'Application Panel · BlaineSide';
$PAGE_HEADING = 'Application Panel';
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
     APPLICATION PANEL

     One page, two views, chosen by the hash:

       (no hash)   the queue — Outstanding Applications / Application Archive
       #id=123     one application, being reviewed

     The same file draws both because they share the whole shell and half
     the components, and because "back to the list" should not be a reload.
     ===================================================================== */
  .qhead{display:flex;gap:15px;align-items:flex-start;margin-bottom:22px}
  .qhead .qi{width:42px;height:42px;flex:none;border-radius:12px;display:grid;place-items:center;
    background:rgba(212,146,58,.1);border:1px solid rgba(212,146,58,.26)}
  .qhead .qi svg{width:20px;height:20px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .qhead h1{font-size:23px;font-weight:700;letter-spacing:-.02em}
  /* No max-width. A measure cap is right for body copy inside a card, but
     this line sits alone under the page title with the full content width
     behind it — capping it there just wraps a short sentence early and
     leaves the right half of the header empty. */
  /* max-width:none, not simply omitted: the shell's own .qhead p rule
     higher up this stylesheet caps the measure at 66ch, and a later
     rule that says nothing about max-width leaves that cap standing. */
  .qhead p{font-size:13.5px;color:var(--text-faint);margin-top:4px;max-width:none}

  .card{background:var(--charcoal-2);border:1px solid var(--border-soft);border-radius:14px}
  .card + .card{margin-top:16px}
  /* The answers card lives inside #answersHost so it can be redrawn on its
     own without losing what is half-typed in the feedback box. That wrapper
     is a plain div, so `.card + .card` stopped matching across it and the
     gap above the card vanished. Spacing the wrapper restores it — and
     anything else that ends up in a wrapper here gets it too. */
  .split > div > * + *{margin-top:16px}
  .card-h{display:flex;align-items:center;gap:11px;padding:14px 18px;border-bottom:1px solid var(--border-soft)}
  .card-h h3{font-size:13.5px;font-weight:700}
  .card-h .r{margin-left:auto;display:flex;gap:9px;align-items:center}
  .card-b{padding:18px}
  .lede{font-size:12.5px;color:var(--text-faint);line-height:1.75}
  .empty{border:1px dashed var(--border);border-radius:12px;padding:30px 18px;text-align:center;
    color:var(--text-dim);font-size:12.5px}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;
    border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);font:inherit;
    font-size:12.5px;font-weight:700;cursor:pointer;transition:.14s}
  .btn:hover{color:var(--parchment);border-color:var(--charcoal-4)}
  .btn.primary{background:linear-gradient(145deg,var(--amber),var(--gold));color:#1a1206;border:none}
  .btn.primary:hover{filter:brightness(1.05);color:#1a1206}
  .btn.ok{background:rgba(127,160,90,.14);border-color:rgba(127,160,90,.45);color:#a8c483}
  .btn.ok.on{background:rgba(127,160,90,.3);color:#cfe0b8}
  .btn.bad{background:rgba(193,85,63,.14);border-color:rgba(193,85,63,.45);color:#eab3a6}
  .btn.bad.on{background:rgba(193,85,63,.3);color:#f4d0c6}
  .btn.sm{padding:6px 11px;font-size:11.5px}
  .btn[disabled]{opacity:.45;cursor:not-allowed}

  /* ---- counters: neutral cards, colour only in the percentage ---- */
  .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
  @media (max-width:1100px){ .grid4{grid-template-columns:repeat(2,1fr)} }
  .stat{background:var(--charcoal-2);border:1px solid var(--border-soft);border-radius:14px;padding:16px 18px}
  .stat .k{font-size:10.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;color:var(--text-dim)}
  .stat .v{font-size:30px;font-weight:800;margin-top:7px;letter-spacing:-.03em;color:var(--parchment);
    font-variant-numeric:tabular-nums;display:flex;align-items:baseline;gap:9px}
  .stat .v small{font-size:11.5px;font-weight:700;color:var(--tone-text,#968e7e);
    border:1px solid color-mix(in srgb,var(--tone,#8a7f70) 40%,transparent);
    background:color-mix(in srgb,var(--tone,#8a7f70) 14%,transparent);padding:3px 8px;border-radius:100px}
  .stat .d{font-size:11.5px;color:var(--text-dim);margin-top:6px}
  .stat.a{--tone:#d4923a;--tone-text:#dda157}
  .stat.g{--tone:#7fa05a;--tone-text:#9dbd77}
  .stat.r{--tone:#c1553f;--tone-text:#d98a75}

  .tabs{display:flex;gap:4px;background:var(--charcoal-3);border:1px solid var(--border-soft);
    border-radius:11px;padding:4px}
  .tab{padding:7px 15px;border-radius:8px;font-size:12.5px;font-weight:700;color:var(--text-faint);
    cursor:pointer;display:flex;align-items:center;gap:8px;border:none;background:none;font-family:inherit}
  .tab.on{background:var(--charcoal);color:var(--parchment)}
  .tab .n{font-size:10.5px;font-weight:800;color:var(--amber);font-variant-numeric:tabular-nums}

  table{width:100%;border-collapse:collapse}
  th{text-align:left;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
    color:var(--text-dim);padding:11px 14px;border-bottom:1px solid var(--border-soft);white-space:nowrap}
  td{padding:13px 14px;font-size:13px;border-bottom:1px solid var(--border-soft)}
  tbody tr:last-child td{border-bottom:none}
  tbody tr{cursor:pointer;transition:background .12s}
  tbody tr:hover{background:var(--charcoal-3)}
  .mono{font-variant-numeric:tabular-nums;color:var(--text-faint)}
  .cc{text-align:center}

  /* Status is a dot and a word — a pill here made every row shout. */
  .st{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;color:var(--text-faint)}
  .st s{width:7px;height:7px;border-radius:50%;text-decoration:none;flex:none;background:var(--stone)}
  .st.wait s{background:var(--amber);box-shadow:0 0 0 3px rgba(212,146,58,.14)} .st.wait{color:#dda157}
  .st.claimed s{background:#7ba3bf;box-shadow:0 0 0 3px rgba(59,98,128,.16)} .st.claimed{color:#9db8cc}
  .st.mine s{background:var(--ok);box-shadow:0 0 0 3px rgba(127,160,90,.16)} .st.mine{color:#9dbd77}
  .st.pass s{background:var(--ok)} .st.pass{color:#9dbd77}
  .st.fail s{background:var(--danger)} .st.fail{color:#d98a75}
  .free{font-style:italic;color:var(--text-faint)}

  .pager{display:flex;align-items:center;gap:7px}
  .pager .pg{min-width:34px;height:34px;padding:0 10px;display:grid;place-items:center;border-radius:9px;
    border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);
    font-size:12px;font-weight:700;cursor:pointer;font-variant-numeric:tabular-nums}
  .pager .pg.on{background:var(--charcoal);color:var(--parchment);border-color:var(--charcoal-4)}
  .pager .pg[disabled]{opacity:.35;cursor:not-allowed}
  .sel{appearance:none;background:var(--charcoal) url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23968e7e' stroke-width='2'><path d='M6 9l6 6 6-6'/></svg>") no-repeat right 12px center/15px;
    border:1px solid var(--border);border-radius:10px;color:var(--parchment);font:inherit;font-size:12.5px;
    padding:9px 38px 9px 12px}
  textarea,input[type=text]{width:100%;background:var(--charcoal);border:1px solid var(--border);border-radius:10px;
    color:var(--parchment);font:inherit;font-size:13px;padding:12px 13px;resize:vertical;line-height:1.7}
  textarea:focus,input[type=text]:focus{outline:none;border-color:rgba(226,182,92,.45)}

  /* ---- review ---- */
  /* 340, not 380. The decision box holds two buttons, a select and a
     textarea and needs no more; every pixel it gives back goes to the
     answers. */
  .split{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px;align-items:start}
  @media (max-width:1150px){ .split{grid-template-columns:1fr} }

  .warn{display:flex;gap:13px;align-items:flex-start;background:rgba(193,85,63,.09);
    border:1px solid rgba(193,85,63,.34);border-radius:13px;padding:14px 16px;margin-bottom:16px}
  .warn .ic{width:30px;height:30px;flex:none;border-radius:9px;display:grid;place-items:center;
    background:rgba(193,85,63,.16)}
  .warn .ic svg{width:16px;height:16px;stroke:#d98a75;fill:none;stroke-width:2}
  .warn h5{font-size:12.5px;font-weight:800;color:#eab3a6}
  .warn p{font-size:12.5px;color:var(--text-faint);margin-top:3px;line-height:1.7}
  .hits{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
  .hit{font-size:11.5px;padding:4px 10px;border-radius:100px;background:rgba(193,85,63,.12);
    border:1px solid rgba(193,85,63,.3);color:#eab3a6;font-weight:600}

  .fold .foldh{display:flex;align-items:center;gap:11px;padding:14px 18px;cursor:pointer;user-select:none}
  .fold .foldh h3{font-size:13.5px;font-weight:700}
  .fold .foldh .r{margin-left:auto;display:flex;gap:10px;align-items:center;color:var(--text-dim);font-size:12px}
  .fold .foldh .chev{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.2;transition:transform .16s}
  .fold.open .foldh .chev{transform:rotate(180deg)}
  .fold .foldb{display:none}
  .fold.open .foldb{display:block;border-top:1px solid var(--border-soft);
    animation:bsOpen .17s ease-out both}

  .fg{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border-soft);
    border:1px solid var(--border-soft);border-radius:12px;overflow:hidden}
  @media (max-width:900px){ .fg{grid-template-columns:repeat(2,1fr)} }
  .fg .c{background:var(--charcoal-2);padding:12px 14px;min-width:0}
  .fg .c .k{font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--text-dim)}
  .fg .c .v{font-size:13px;margin-top:5px;font-weight:600;overflow-wrap:anywhere}
  .flab{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);
    margin:16px 0 9px}
  .iprow{display:flex;align-items:center;gap:11px;padding:10px 13px;background:var(--charcoal-3);
    border:1px solid var(--border-soft);border-radius:11px;margin-top:9px;font-size:12.5px;flex-wrap:wrap}
  .iprow .r{margin-left:auto;display:flex;gap:11px;align-items:center;color:var(--text-dim);font-size:11.5px}
  .ipm{font-variant-numeric:tabular-nums;font-weight:600}

  .pill{display:inline-flex;align-items:center;justify-content:center;font-size:10.5px;font-weight:800;
    letter-spacing:.1em;text-transform:uppercase;padding:5px 11px;border-radius:100px;line-height:1;
    text-indent:.05em;color:var(--tone-text,#968e7e);
    background:color-mix(in srgb,var(--tone,#8a7f70) 17%,transparent);
    border:1px solid color-mix(in srgb,var(--tone,#8a7f70) 44%,transparent)}
  .pill.pend{--tone:#d4923a;--tone-text:#dda157}
  .pill.pass{--tone:#7fa05a;--tone-text:#9dbd77}
  .pill.fail{--tone:#c1553f;--tone-text:#d98a75}
  .pill.draft{--tone:#8a7f70;--tone-text:#968e7e}
  .pill.claim{--tone:#3b6280;--tone-text:#7ba3bf}

  .aplist{padding:16px 18px;display:flex;flex-direction:column;gap:9px}
  .aprow{display:flex;align-items:center;gap:12px;padding:11px 13px;background:var(--charcoal-3);
    border:1px solid var(--border-soft);border-radius:11px;font-size:12.5px;cursor:pointer;transition:.13s}
  .aprow:hover{background:var(--charcoal-4);border-color:rgba(226,182,92,.26)}
  .aprow .r{margin-left:auto;display:flex;gap:11px;align-items:center;color:var(--text-dim)}

  .items{display:flex;flex-direction:column;gap:12px}
  .item{background:var(--charcoal-2);border:1px solid var(--border);border-radius:12px;overflow:hidden}
  .ihead{display:flex;align-items:center;gap:14px;padding:14px 16px}
  .ihead .idx{width:26px;height:26px;flex:none;border-radius:7px;display:grid;place-items:center;
    background:var(--charcoal-3);border:1px solid var(--border-soft);color:var(--text-faint);
    font-size:11.5px;font-weight:800;font-variant-numeric:tabular-nums}
  .ihead .tx b{display:block;font-size:13.5px;font-weight:600}
  .ihead .r{margin-left:auto;flex:none;display:flex;align-items:center;gap:10px;color:var(--text-dim);font-size:11.5px}
  /* Indented to 56px it lined up under the title, which looked tidy and
     wasted a column of empty space down the left of every answer. Long
     answers are the thing this page exists to read; they get the width. */
  .ibody{padding:0 16px 16px;border-top:1px solid var(--border-soft);
    animation:bsOpen .16s ease-out both}
  .ibody .prompt{font-size:12.5px;color:var(--text-faint);line-height:1.75;padding-top:14px}
  .ihead[data-answer]{cursor:pointer;user-select:none}
  .ib{width:29px;height:29px;flex:none;border-radius:8px;display:grid;place-items:center;
    border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint)}
  .ib svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2}
  /* Answer well. The answer text and its Assist share one container so they
     read as one object; Assist hangs off the bottom edge like a drawer. */
  .well{background:var(--charcoal);border:1px solid var(--border);border-radius:10px;
    margin-top:11px;overflow:hidden}
  .well .txt{padding:13px 14px;font-size:13px;line-height:1.8;white-space:pre-wrap;
    overflow-wrap:anywhere}

  /* Length meter in the answer header: fills toward the minimum, rust when short. */
  .len{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;color:var(--text-dim);
    font-variant-numeric:tabular-nums;line-height:1}
  .len .meter{width:74px;height:4px;border-radius:100px;background:var(--charcoal-4);
    overflow:hidden;flex:none}
  .len .meter i{display:block;height:100%;background:var(--text-faint);border-radius:100px}
  .len.no .meter i{background:#a9503c}
  .len .n{font-weight:600;color:var(--text-faint)}
  .len.no .n{color:#d98a75}

  /* ---- Assist ----
     A reading aid under an answer, staff only. Collapsed on arrival — note
     included: on a six-question application it would otherwise be six panels
     of small print between the reviewer and the next answer. */
  /* 11.25/8.75 rather than 10/10: the runs have no descenders, so their
     visual block is cap-top to baseline, which sits 1.25px above the box
     centre. Measured off a 4x render, not guessed. */
  .assist .ah{display:flex;align-items:center;gap:10px;padding:11.25px 14px 8.75px;
    border-top:1px solid var(--border);background:var(--charcoal-2);
    cursor:pointer;user-select:none;font-size:11.5px;color:var(--text-dim);line-height:1}
  /* Centring the boxes is not centring the letters: 10px caps and 11.5px
     lowercase have different cap-heights, so equal boxes still read as
     stepped. The three runs sit on one baseline instead, and the baseline
     group as a whole is what gets centred in the row. */
  .assist .ah .lbl{display:flex;align-items:baseline;gap:10px;min-width:0}
  .assist .ah .lbl > span{line-height:1}
  .assist .ah .t{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
    color:var(--text-faint);text-indent:.14em}
  .assist .ah .score{font-variant-numeric:tabular-nums;font-weight:600;color:var(--text-faint)}
  .assist .ah .cv{margin-left:auto;width:14px;height:14px;stroke:currentColor;fill:none;
    stroke-width:2.2;transition:transform .16s}
  .assist.open .ah .cv{transform:rotate(180deg)}
  .assist .ab{display:none;background:var(--charcoal-2);border-top:1px solid rgba(56,50,43,.6)}
  .assist.open .ab{display:block;animation:bsOpen .16s ease-out both}
  .assist .chk{display:flex;align-items:center;gap:10px;padding:8px 14px;font-size:12.5px;
    color:var(--text-dim)}
  .assist .chk + .chk{border-top:1px solid rgba(56,50,43,.45)}
  .assist .chk svg{width:14px;height:14px;flex:none;fill:none;stroke-width:2.4;stroke:currentColor}
  .assist .chk.y{color:var(--parchment)}
  .assist .chk.y svg{stroke:#9dbd77}
  .assist .chk .why{margin-left:auto;font-size:11px;font-style:italic;color:var(--text-dim);
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:45%}
  .assist .meta{display:flex;gap:22px;flex-wrap:wrap;padding:9px 14px;
    border-top:1px solid var(--border);font-size:11.5px;color:var(--text-dim)}
  .assist .meta b{font-weight:600;color:var(--text-faint);font-variant-numeric:tabular-nums}
  .assist .note{padding:9px 14px;border-top:1px solid var(--border);font-size:11px;
    color:var(--text-dim);line-height:1.7}

  .fb{border-radius:12px;padding:14px 16px;background:rgba(193,85,63,.08);border:1px solid rgba(193,85,63,.26)}
  .fb.good{background:rgba(127,160,90,.08);border-color:rgba(127,160,90,.26)}
  .fb h5{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#d98a75;margin-bottom:8px}
  .fb.good h5{color:#9dbd77}
  .fb .body{font-size:12.5px;color:var(--text-faint);line-height:1.75;white-space:pre-wrap}

  .backline{display:flex;align-items:center;gap:10px;margin-bottom:16px}
  .backline .btn svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2.2}

</style>
<link rel="stylesheet" href="/assets/css/tones.css?v=2.6.1">
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <div id="body"><div class="empty">Loading…</div></div>
</main>
  </div>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

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
      window.location.replace('/login?return=' + encodeURIComponent('/dashboard/applications'));
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
     APPLICATION PANEL

     Nothing on this page is a permission. api/applications.php decides who
     sees the queue, api/application-claim.php decides who may hold one,
     and api/application-decide.php decides who may decide it — this file
     draws what those three answered and disables what they refused.
     ===================================================================== */
  var Q = {tab:'outstanding', filter:'all', page:1, per:10};
  var VIEW = null;          // the application currently open, or null
  var PICKED = null;        // 'pass' | 'deny' while reviewing
  var USED_TMPL = 0;        // last template dropped in, for its usage count

  function el(id){ return document.getElementById(id); }
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
  function day(ts){
    if(!ts) return '—';
    return new Date(ts*1000).toLocaleDateString(undefined,{day:'2-digit',month:'short',year:'numeric'});
  }
  function nth(n){
    var s = ['th','st','nd','rd'], v = n % 100;
    return n + (s[(v-20)%10] || s[v] || s[0]);
  }
  function num(n){ return (n|0).toLocaleString(); }
  /* The database stores these lowercase ('active', 'locked'). They are
     labels here, so they are capitalised here rather than in the column —
     the value is the fact, the capital is presentation. */
  function cap(s){ s = String(s || ''); return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—'; }

  /* ---------------- the queue ---------------- */
  function statCards(c){
    return '<div class="grid4">' +
      '<div class="stat a"><div class="k">Waiting now</div><div class="v">' + num(c.waiting) +
        (c.oldest ? ' <small>oldest ' + ago(c.oldest).replace(' ago','') + '</small>' : '') + '</div>' +
        '<div class="d">' + num(c.claimed) + ' claimed, ' + num(c.waiting - c.claimed) + ' unclaimed</div></div>' +
      '<div class="stat g"><div class="k">Passed</div><div class="v">' + num(c.passed) +
        ' <small>' + c.pass_pct + '%</small></div><div class="d">in total, all time</div></div>' +
      '<div class="stat r"><div class="k">Denied</div><div class="v">' + num(c.denied) +
        ' <small>' + c.deny_pct + '%</small></div><div class="d">in total, all time</div></div>' +
      '<div class="stat"><div class="k">Handled by you</div><div class="v">' + num(c.mine) +
        ' <small>' + c.mine_pct + '%</small></div><div class="d">in total, all time</div></div></div>';
  }

  /* Window of three plus arrows — the same pager as the administrative
     record, so paging feels identical everywhere in the UCP. */
  function pager(page, pages){
    if(pages <= 1) return '';
    var h = '<div class="pager">';
    h += '<button class="pg" data-page="' + (page-1) + '"' + (page<=1?' disabled':'') + '>&lt;</button>';
    var start = Math.max(1, Math.min(page-1, pages-2)), end = Math.min(pages, start+2);
    if(start > 1) h += '<button class="pg" data-page="1">1</button>' +
                       (start > 2 ? '<span style="color:var(--text-dim)">…</span>' : '');
    for(var i=start;i<=end;i++)
      h += '<button class="pg' + (i===page?' on':'') + '" data-page="' + i + '">' + i + '</button>';
    if(end < pages) h += (end < pages-1 ? '<span style="color:var(--text-dim)">…</span>' : '') +
                         '<button class="pg" data-page="' + pages + '">' + pages + '</button>';
    h += '<button class="pg" data-page="' + (page+1) + '"' + (page>=pages?' disabled':'') + '>&gt;</button>';
    return h + '</div>';
  }

  function claimCell(r, meId){
    if(r.status !== 'pending'){
      return r.decided ? escapeHtml(r.decided.name) : '<span class="free">—</span>';
    }
    if(!r.claimed) return '<span class="free">Available</span>';
    return escapeHtml(r.claimed.id === meId ? 'You' : r.claimed.name) +
           (r.claimed.stale ? ' <span style="color:var(--text-dim)">(idle)</span>' : '');
  }

  function statusCell(r, meId){
    if(r.status === 'passed') return '<span class="st pass"><s></s>Passed</span>';
    if(r.status === 'denied') return '<span class="st fail"><s></s>Denied</span>';
    if(!r.claimed)            return '<span class="st wait"><s></s>Waiting</span>';
    return '<span class="st ' + (r.claimed.id === meId ? 'mine' : 'claimed') + '"><s></s>Claimed</span>';
  }

  function queueHTML(d){
    var out = Q.tab === 'outstanding';
    var meId = d.me.id;

    var filters = out
      ? '<option value="all">Everyone\'s</option><option value="unclaimed">Unclaimed only</option>' +
        '<option value="mine">Claimed by me</option>'
      : '<option value="all">All outcomes</option><option value="passed">Passed</option>' +
        '<option value="denied">Denied</option>';

    var head = out
      ? '<tr><th style="width:92px">#</th><th>Player</th><th class="cc" style="width:92px">Attempt</th>' +
        '<th style="width:160px">Submitted</th><th style="width:150px">Status</th>' +
        '<th style="width:170px">Claimed by</th><th style="width:110px"></th></tr>'
      : '<tr><th style="width:92px">#</th><th>Player</th><th class="cc" style="width:92px">Attempt</th>' +
        '<th style="width:180px">Decided</th><th style="width:150px">Outcome</th>' +
        '<th style="width:170px">Handled by</th><th style="width:110px"></th></tr>';

    var rows = d.rows.map(function(r){
      var mine = r.claimed && r.claimed.id === meId;
      var act  = !out ? 'Open'
               : (mine ? 'Resume' : (r.claimed && !r.claimed.stale ? 'View' : 'Claim'));
      return '<tr data-id="' + r.id + '">' +
        '<td class="mono">#' + r.id + '</td>' +
        '<td><b>' + escapeHtml(r.player.name) + '</b></td>' +
        '<td class="cc">' + nth(r.attempt) + '</td>' +
        '<td class="mono">' + (out ? ago(r.submitted_at) : dt(r.decided ? r.decided.at : null)) + '</td>' +
        '<td>' + statusCell(r, meId) + '</td>' +
        '<td>' + claimCell(r, meId) + '</td>' +
        '<td style="text-align:right"><span class="btn sm">' + act + '</span></td></tr>';
    }).join('');

    return statCards(d.counts) +
      '<div class="card" style="margin-top:16px">' +
      '<div class="card-h" style="padding:12px 14px">' +
        '<div class="tabs">' +
          '<button class="tab' + (out?' on':'') + '" data-tab="outstanding">Outstanding Applications ' +
            '<span class="n">' + num(d.counts.waiting) + '</span></button>' +
          '<button class="tab' + (out?'':' on') + '" data-tab="archive">Application Archive ' +
            '<span class="n">' + num(d.counts.decided) + '</span></button>' +
        '</div>' +
        '<div class="r"><select class="sel" id="filter">' + filters + '</select>' +
          pager(d.page, d.pages) + '</div></div>' +
      (rows
        ? '<table><thead>' + head + '</thead><tbody>' + rows + '</tbody></table>'
        : '<div class="card-b"><div class="empty">' +
          (out ? 'Nothing is waiting. The queue is clear.' : 'Nothing has been decided yet.') +
          '</div></div>') +
      '</div>';
  }

  function renderQueue(d){
    paint('body',
      '<div class="qhead">' +
      '<span class="qi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
      '<path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6M9 17h3"/></svg></span>' +
      '<div><h1>Application Panel</h1><p>Claim one to review it. While you hold it nobody else can ' +
      'decide it or write the feedback — except Staff Management, Management and Founders, who can ' +
      'take it over.</p></div></div>' + queueHTML(d));

    var f = el('filter'); if(f){ f.value = Q.filter; f.addEventListener('change', function(){
      Q.filter = this.value; Q.page = 1; loadQueue(); }); }

    document.querySelectorAll('[data-tab]').forEach(function(b){
      b.addEventListener('click', function(){
        Q.tab = this.getAttribute('data-tab'); Q.filter = 'all'; Q.page = 1; loadQueue();
      });
    });
    document.querySelectorAll('[data-page]').forEach(function(b){
      b.addEventListener('click', function(){
        if(this.disabled) return;
        Q.page = parseInt(this.getAttribute('data-page'), 10) || 1; loadQueue();
      });
    });
    document.querySelectorAll('tbody tr[data-id]').forEach(function(tr){
      tr.addEventListener('click', function(){ location.hash = 'id=' + this.getAttribute('data-id'); });
    });
  }

  function loadQueue(){
    UCP.get('applications.php?tab=' + Q.tab + '&filter=' + Q.filter +
            '&page=' + Q.page + '&per=' + Q.per).then(function(d){
      if(!d || !d.ok){
        el('body').innerHTML = '<div class="card"><div class="card-b"><p class="lede">' +
          escapeHtml((d && d.error) || 'Could not load the panel.') + '</p></div></div>';
        return;
      }
      renderQueue(d);
    });
  }

  /* ---------------- one application ---------------- */
  function warnCard(d){
    if(!d.matches || !d.matches.length) return '';
    var ips = {};
    d.matches.forEach(function(m){ ips[m.ip] = true; });
    return '<div class="warn"><div class="ic"><svg viewBox="0 0 24 24">' +
      '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L2.4 18a1.8 1.8 0 0 0 1.6 2.7h16a1.8 1.8 0 0 0 1.6-2.7' +
      'L13.7 3.9a1.8 1.8 0 0 0-3.4 0z"/></svg></div><div><h5>This address is shared with other accounts</h5>' +
      '<p>' + escapeHtml(Object.keys(ips).join(', ')) + ' ' +
      (Object.keys(ips).length === 1 ? 'has' : 'have') + ' also been used by ' + d.matches.length +
      ' other account' + (d.matches.length === 1 ? '' : 's') + '. That is not proof of anything on its own — ' +
      'shared houses and mobile networks do it too — but it is worth a look before you decide.</p>' +
      '<div class="hits">' + d.matches.map(function(m){
        return '<span class="hit">' + escapeHtml(m.name) +
               (m.status && m.status !== 'active' ? ' · ' + escapeHtml(cap(m.status)) : '') +
               ' · last seen ' + ago(m.last_seen) + '</span>';
      }).join('') + '</div></div></div>';
  }

  function applicantCard(d){
    var a = d.applicant || {};
    var ips = (d.ips || []).map(function(ip){
      return '<div class="iprow"><span class="pill ' + (ip.current ? 'pend' : 'draft') + '">' +
        (ip.current ? 'Current' : 'Past') + '</span><span class="ipm">' + escapeHtml(ip.ip) + '</span>' +
        '<span class="r"><span>used ' + num(ip.hits) + ' time' + (ip.hits===1?'':'s') +
        ' · since ' + day(ip.first_seen) + '</span>' +
        '<a class="btn sm" target="_blank" rel="noopener" href="' + escapeHtml(ip.lookup) + '">Look it up ↗</a>' +
        '</span></div>';
    }).join('') || '<div class="empty">No addresses recorded for this account yet.</div>';

    return '<div class="card fold" id="foldApplicant"><div class="foldh" data-fold="foldApplicant">' +
      '<h3>Applicant</h3><div class="r"><span>' + escapeHtml(a.name || '') + ' · #' + (a.id||0) +
      ' · ' + ((d.ips||[]).length) + ' address' + ((d.ips||[]).length===1?'':'es') + '</span>' +
      '<svg class="chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></div></div>' +
      '<div class="foldb"><div class="card-b">' +
      '<div class="fg">' +
        cell('Account', '#' + (a.id||0) + ' · ' + escapeHtml(a.name||'')) +
        cell('Registered', a.created_at ? escapeHtml(String(a.created_at).replace('T',' ')) : '—') +
        cell('Email', escapeHtml(a.email||'—')) +
        cell('Submitted', ago(d.submitted_at)) +
        cell('Handled by', d.claimed ? escapeHtml(d.claimed.id === MEID ? 'You' : d.claimed.name) +
             ', since ' + ago(d.claimed.at) : '<span class="free">Nobody yet</span>') +
        /* Just which attempt this is. There is no cap on how many times
           somebody may apply, so "3rd of 3" states a total that does not
           exist and goes stale the moment they apply again. */
        cell('This attempt', nth(d.attempt)) +
        cell('Punishments', a.punishments === null || a.punishments === undefined
             ? 'Not available' : (a.punishments ? num(a.punishments) + ' on record' : 'None on record')) +
        cell('Account status', escapeHtml(cap(a.status))) +
        cell('Last login', a.last_login ? escapeHtml(String(a.last_login).replace('T',' ')) : '—') +
      '</div>' +
      '<div class="flab">Addresses seen</div>' + ips +
      '</div></div></div>';
  }
  function cell(k, v){ return '<div class="c"><div class="k">' + k + '</div><div class="v">' + v + '</div></div>'; }

  function historyCard(list){
    var rows = (list||[]).map(function(h){
      var cls = h.status === 'passed' ? 'pass' : (h.status === 'denied' ? 'fail' : 'pend');
      var lab = h.status === 'passed' ? 'Passed' : (h.status === 'denied' ? 'Denied' : 'Waiting');
      return '<div class="aprow" data-open="' + h.id + '"><span class="pill ' + cls + '">' + lab + '</span>' +
        '<span>Attempt ' + h.attempt + ' · ' + dt(h.decided ? h.decided.at : h.submitted_at) + '</span>' +
        '<span class="r"><span>' + escapeHtml(h.decided ? h.decided.name : '—') + '</span>' +
        '<span class="btn sm">Open</span></span></div>';
    }).join('');
    var denied = (list||[]).filter(function(h){ return h.status === 'denied'; }).length;
    return '<div class="card fold" id="foldHistory"><div class="foldh" data-fold="foldHistory">' +
      '<h3>Previous applications</h3><div class="r">' +
      (list && list.length
        ? '<span class="pill ' + (denied ? 'fail' : 'draft') + '">' +
          (denied ? denied + ' denied' : list.length + ' earlier') + '</span>'
        : '<span class="pill draft">None</span>') +
      '<svg class="chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></div></div>' +
      '<div class="foldb">' + (rows ? '<div class="aplist">' + rows + '</div>'
        : '<div class="card-b"><div class="empty">This is their first application.</div></div>') + '</div></div>';
  }

  /* Assist. Drawn only when the server sent one, which it does only for
     staff and only for questions that have criteria behind them — see
     assist_attach() in api/_applications.php. */
  var ASSIST_OPEN = {};
  var TICK = '<svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>';
  var DASH = '<svg viewBox="0 0 24 24"><path d="M6 12h12"/></svg>';

  function assistBlock(a){
    var s = a.assist;
    if(!s) return '';
    var open = !!ASSIST_OPEN[a.id];
    var st = s.stats || {};

    var checks = (s.checks || []).map(function(c){
      return '<div class="chk ' + (c.found ? 'y' : 'n') + '">' + (c.found ? TICK : DASH) +
        '<span>' + escapeHtml(c.label) + '</span>' +
        (c.found && c.match ? '<span class="why">\u201C' + escapeHtml(c.match) + '\u201D</span>' : '') +
        '</div>';
    }).join('');

    var meta = '<div class="meta"><span><b>' + num(st.chars) + '</b> characters</span>' +
      '<span><b>' + num(st.paragraphs) + '</b> paragraph' + (st.paragraphs === 1 ? '' : 's') + '</span>' +
      '<span><b>' + num(st.longest) + '</b> words in the longest run</span></div>';

    return '<div class="assist' + (open ? ' open' : '') + '">' +
      '<div class="ah" data-assist="' + a.id + '"><span class="lbl">' +
      '<span class="t">Application Assist</span>' +
      '<span class="score">' + s.found + ' of ' + s.total + '</span>' +
      '<span class="cf">criteria found</span></span>' +
      '<svg class="cv" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></div>' +
      '<div class="ab">' + checks + meta +
      '<div class="note">Assist looks for wording, not understanding. A missing line means those ' +
      'words were not found \u2014 it does not mean the answer is wrong, and a tick does not mean it ' +
      'is right.</div></div></div>';
  }

  /* Every answer starts closed. An application with three long answers is
     three screens of scrolling before you can see what was asked, and the
     first thing a reviewer does is decide which one to read. */
  /* Length reads as a meter, not a sentence: it fills toward the minimum and
     goes rust when the answer falls short. No minimum set, no meter. */
  function lenChip(a){
    if(!a.min_chars){
      return '<span class="len"><span class="n">' + num(a.chars) + '</span> character' +
        (a.chars === 1 ? '' : 's') + '</span>';
    }
    var pct = Math.max(4, Math.min(100, Math.round(a.chars / a.min_chars * 100)));
    var short = a.chars < a.min_chars;
    return '<span class="len' + (short ? ' no' : '') + '">' +
      '<span class="meter"><i style="width:' + pct + '%"></i></span>' +
      '<span class="n">' + num(a.chars) + '</span> of ' + num(a.min_chars) + '</span>';
  }

  var ANS_OPEN = {};

  function answersCard(d){
    var n = (d.answers||[]).length;
    var allOpen = n > 0 && (d.answers||[]).every(function(a){ return ANS_OPEN[a.id]; });

    var items = (d.answers||[]).map(function(a, i){
      var open = !!ANS_OPEN[a.id];
      return '<div class="item"><div class="ihead" data-answer="' + a.id + '">' +
        '<span class="idx">' + (i+1) + '</span>' +
        '<span class="tx"><b>' + escapeHtml(a.title) + '</b></span>' +
        '<span class="r">' + lenChip(a) +
        '<span class="ib"><svg viewBox="0 0 24 24"><path d="M6 ' +
        (open ? '15l6-6 6 6' : '9l6 6 6-6') + '"/></svg></span></span></div>' +
        (open
          ? '<div class="ibody"><div class="prompt">' + escapeHtml(a.prompt) + '</div>' +
            '<div class="well"><div class="txt">' + escapeHtml(a.body || '') + '</div>' +
            assistBlock(a) + '</div></div>'
          : '') + '</div>';
    }).join('');

    return '<div class="card"><div class="card-h"><h3>Answers</h3>' +
      '<div class="r"><button class="btn sm" id="expandAll">' +
      (allOpen ? 'Collapse all' : 'Expand all') + '</button>' +
      '<span class="pill draft">' + n + ' question' + (n===1?'':'s') + '</span></div></div>' +
      '<div class="card-b"><div class="items">' + items + '</div></div></div>';
  }

  function decisionCard(d){
    /* Already decided: the record, not the form. */
    if(d.status !== 'pending'){
      var good = d.status === 'passed';
      return '<div class="card"><div class="card-h"><h3>Decision</h3><div class="r">' +
        '<span class="pill ' + (good?'pass':'fail') + '">' + (good?'Passed':'Denied') + '</span></div></div>' +
        '<div class="card-b"><div class="lede">' +
        escapeHtml(d.decided ? d.decided.name : 'Somebody') + ' decided this ' + ago(d.decided ? d.decided.at : null) +
        '.</div>' + (d.feedback
          ? '<div style="margin-top:13px" class="fb' + (good?' good':'') + '"><h5>Feedback sent</h5>' +
            '<div class="body">' + escapeHtml(d.feedback) + '</div></div>'
          : '<div style="margin-top:13px" class="empty">No feedback was written.</div>') +
        '</div></div>';
    }

    var mine  = d.claimed && d.claimed.id === MEID;
    var held  = d.claimed && !mine && !d.claimed.stale;
    /* Grouped by outcome rather than labelled '(deny)' in brackets. A
       reviewer picking from a flat list can drop an acceptance into a
       denial without noticing; a group heading makes that a wrong section
       rather than a missed suffix. */
    function group(kind, label){
      var rows = (d.templates||[]).filter(function(t){ return t.use_for === kind; });
      if(!rows.length) return '';
      return '<optgroup label="' + label + '">' + rows.map(function(t){
        return '<option value="' + t.id + '">' + escapeHtml(t.title) + '</option>';
      }).join('') + '</optgroup>';
    }
    var opts = group('pass', 'Pass') + group('deny', 'Deny') + group('either', 'Either');

    return '<div class="card"><div class="card-h"><h3>Your decision</h3><div class="r">' +
      (d.claimed
        ? '<span class="pill claim">' + escapeHtml(mine ? 'Claimed by you' : 'Claimed by ' + d.claimed.name) + '</span>'
        : '<span class="pill pend">Unclaimed</span>') + '</div></div><div class="card-b">' +

      (d.claimed
        ? (mine
            ? '<button class="btn" id="releaseBtn" style="width:100%">Give it back to the queue</button>'
            : '<div class="lede">' + escapeHtml(d.claimed.name) + ' claimed this ' + ago(d.claimed.at) + '.' +
              (d.may ? ' You can take it over.' : ' Only they, or Management, can decide it.') + '</div>' +
              (d.may ? '<button class="btn" id="claimBtn" style="width:100%;margin-top:11px">Take it over</button>' : ''))
        : '<button class="btn primary" id="claimBtn" style="width:100%">Claim this application</button>') +

      (d.may
        ? '<div style="display:flex;gap:9px;margin-top:15px">' +
            '<button class="btn ok" data-pick="pass" style="flex:1">Pass</button>' +
            '<button class="btn bad" data-pick="deny" style="flex:1">Deny</button></div>' +
          '<div class="flab">Insert a template</div>' +
          '<select class="sel" id="tmpl" style="width:100%"><option value="">Choose a saved response…</option>' +
            opts + '</select>' +
          '<div class="flab">Feedback to the player</div>' +
          '<textarea id="feedback" rows="8" placeholder="What they need to change…"></textarea>' +
          '<div class="lede" style="margin-top:8px;font-size:11.5px">Sent with the decision. The player always ' +
          'sees this. Required on a denial.</div>' +
          '<button class="btn primary" id="decideBtn" style="width:100%;margin-top:13px" disabled>' +
          'Record decision</button>'
        : '') +
      '</div></div>';
  }

  var MEID = 0;

  function renderOne(d){
    VIEW = d; PICKED = null; USED_TMPL = 0; ANS_OPEN = {}; ASSIST_OPEN = {};
    paint('body',
      '<div class="backline"><button class="btn" id="backBtn">' +
        '<svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></svg>Back to the panel</button>' +
        '<div style="font-size:12.5px;color:var(--text-dim)">Application <b style="color:var(--parchment)">#' +
        d.id + '</b> · ' + escapeHtml(d.player.name) + ' · ' + nth(d.attempt) + ' attempt · submitted ' +
        ago(d.submitted_at) + '</div></div>' +
      warnCard(d) +
      '<div class="split"><div>' +
        applicantCard(d) + historyCard(d.history) +
        '<div id="answersHost">' + answersCard(d) + '</div>' +
      '</div><div>' + decisionCard(d) + '</div></div>');

    el('backBtn').addEventListener('click', function(){ location.hash = ''; });

    document.querySelectorAll('[data-fold]').forEach(function(h){
      h.addEventListener('click', function(){
        var c = el(this.getAttribute('data-fold'));
        if(c) c.classList.toggle('open');
      });
    });
    document.querySelectorAll('[data-open]').forEach(function(r){
      r.addEventListener('click', function(){ location.hash = 'id=' + this.getAttribute('data-open'); });
    });

    wireAnswers();

    var claimBtn = el('claimBtn');
    if(claimBtn) claimBtn.addEventListener('click', function(){ claim('claim'); });
    var relBtn = el('releaseBtn');
    if(relBtn) relBtn.addEventListener('click', function(){ claim('release'); });

    document.querySelectorAll('[data-pick]').forEach(function(b){
      b.addEventListener('click', function(){
        PICKED = this.getAttribute('data-pick');
        document.querySelectorAll('[data-pick]').forEach(function(x){ x.classList.remove('on'); });
        this.classList.add('on');
        var dec = el('decideBtn');
        if(dec){ dec.disabled = false;
          dec.textContent = PICKED === 'pass' ? 'Pass this application' : 'Deny this application'; }
      });
    });

    /* The template is INSERTED, never sent. It lands at the cursor if the
       box already has words in it, so a saved paragraph can be dropped into
       the middle of something written by hand. */
    var tm = el('tmpl');
    if(tm) tm.addEventListener('change', function(){
      var id = parseInt(this.value, 10) || 0;
      if(!id) return;
      var t = (VIEW.templates||[]).filter(function(x){ return x.id === id; })[0];
      var box = el('feedback');
      if(!t || !box) return;
      var at = box.selectionStart || box.value.length;
      var pre = box.value.slice(0, at), post = box.value.slice(at);
      box.value = pre + (pre && !/\n\n$/.test(pre) ? '\n\n' : '') + t.body + (post ? '\n\n' + post : '');
      box.focus();
      USED_TMPL = id;
      this.value = '';
    });

    var dec = el('decideBtn');
    if(dec) dec.addEventListener('click', decide);
  }

  /* Redraw the answers card ALONE. Re-rendering the whole review would
     throw away whatever is half-written in the feedback box, which is the
     one thing on this page nobody can get back. */
  function wireAnswers(){
    document.querySelectorAll('[data-answer]').forEach(function(h){
      h.addEventListener('click', function(){
        var id = parseInt(this.getAttribute('data-answer'), 10);
        ANS_OPEN[id] = !ANS_OPEN[id];
        redrawAnswers();
      });
    });
    /* The assist header sits INSIDE the answer body, so its click must not
       bubble up to the answer header and close the thing it belongs to. */
    document.querySelectorAll('[data-assist]').forEach(function(h){
      h.addEventListener('click', function(e){
        e.stopPropagation();
        var id = parseInt(this.getAttribute('data-assist'), 10);
        ASSIST_OPEN[id] = !ASSIST_OPEN[id];
        redrawAnswers();
      });
    });
    var ea = el('expandAll');
    if(ea) ea.addEventListener('click', function(){
      var open = (VIEW.answers||[]).every(function(a){ return ANS_OPEN[a.id]; });
      (VIEW.answers||[]).forEach(function(a){ ANS_OPEN[a.id] = !open; });
      redrawAnswers();
    });
  }

  function redrawAnswers(){
    var host = document.getElementById('answersHost');
    if(!host) return;
    host.innerHTML = answersCard(VIEW);
    wireAnswers();
  }

  function claim(action){
    var b = el(action === 'release' ? 'releaseBtn' : 'claimBtn');
    if(b) b.disabled = true;
    UCP.post('application-claim.php', {id: VIEW.id, action: action}).then(function(res){
      var d = res.data || {};
      if(!d.ok){ if(b) b.disabled = false; return toast(d.error || 'Could not claim it'); }
      loadOne(VIEW.id);
    }).catch(function(){ if(b) b.disabled = false; toast('Could not reach the server'); });
  }

  function decide(){
    if(!PICKED) return;
    var box = el('feedback'), b = el('decideBtn');
    var text = box ? box.value.trim() : '';
    if(PICKED === 'deny' && text.length < 20)
      return toast('Tell them what to change — a denial with no feedback means they apply again unchanged.');
    b.disabled = true;
    UCP.post('application-decide.php', {
      id: VIEW.id, outcome: PICKED, feedback: text,
      template_id: USED_TMPL
    }).then(function(res){
      var d = res.data || {};
      if(!d.ok){ b.disabled = false; return toast(d.error || 'Could not record that'); }
      toast(d.status === 'passed' ? 'Passed. They have been notified.' : 'Denied. Feedback sent.');
      loadOne(VIEW.id);
    }).catch(function(){ b.disabled = false; toast('Could not reach the server'); });
  }

  function loadOne(id){
    UCP.get('application.php?id=' + id).then(function(d){
      if(!d || !d.ok){
        el('body').innerHTML = '<div class="backline"><button class="btn" id="backBtn">' +
          'Back to the panel</button></div><div class="card"><div class="card-b"><p class="lede">' +
          escapeHtml((d && d.error) || 'Could not load that application.') + '</p></div></div>';
        var bb = el('backBtn'); if(bb) bb.addEventListener('click', function(){ location.hash = ''; });
        return;
      }
      MEID = (window.UCP && UCP.me && UCP.me.id) ? UCP.me.id : MEID;
      renderOne(d);
    });
  }

  function route(){
    var m = (location.hash || '').match(/id=(\d+)/);
    if(m) loadOne(parseInt(m[1], 10));
    else   loadQueue();
  }
  window.addEventListener('hashchange', route);

  UCP.get('session.php').then(function(s){
    if(s && s.ok) MEID = s.id | 0;
    route();
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
