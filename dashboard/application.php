<?php
/**
 * Application.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'Application · BlaineSide';
$PAGE_HEADING = 'Application';
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

  /* The pager, identical to the Administrative Record's — count on the
     left, arrows and numbers on the right. One pattern everywhere. */
  .pager{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    margin-top:15px;padding-top:14px;border-top:1px solid var(--border-soft)}
  .pcount{font-size:12px;color:var(--text-dim);font-variant-numeric:tabular-nums}
  .pcount b{color:var(--parchment);font-weight:600}
  .pnav{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
  .pnav button{min-width:33px;height:33px;padding:0 9px;border-radius:9px;
    border:1px solid var(--border-soft);background:var(--charcoal);color:var(--text-dim);
    font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer;display:grid;
    place-items:center;font-variant-numeric:tabular-nums;transition:.14s}
  .pnav button:hover:not([disabled]){color:var(--parchment);border-color:var(--charcoal-4)}
  .pnav button[aria-current="true"]{background:var(--charcoal-4);color:var(--parchment);
    border-color:rgba(226,182,92,.38)}
  .pnav button[disabled]{opacity:.3;cursor:default}
  .pnav .arrow{min-width:33px;padding:0}
  .pnav .arrow svg{width:15px;height:15px;stroke-width:2.3;fill:none;stroke:currentColor}
  @media (max-width:700px){
    .pager{flex-direction:column;align-items:stretch;gap:12px}
    .pnav{justify-content:center}
  }

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
     APPLICATION — the player's own page.

     One job: write the answers, send them, and read what came back. Every
     rule about who may review what lives on the server; nothing on this
     page is a permission.
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

  .split{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px;align-items:start}
  @media (max-width:1150px){ .split{grid-template-columns:1fr} }

  .card{background:var(--charcoal-2);border:1px solid var(--border-soft);border-radius:14px}
  .card + .card{margin-top:16px}
  .card-h{display:flex;align-items:center;gap:11px;padding:14px 18px;border-bottom:1px solid var(--border-soft)}
  .card-h h3{font-size:13.5px;font-weight:700}
  .card-h .r{margin-left:auto;display:flex;gap:9px;align-items:center}
  .card-b{padding:18px}

  /* Each question is its own panel — see the Question Manager for the same
     component. Nothing runs together, and a long answer cannot make the
     next question look like part of it. */
  .items{display:flex;flex-direction:column;gap:12px}
  .item{background:var(--charcoal-2);border:1px solid var(--border);border-radius:12px;overflow:hidden}
  .ihead{display:flex;align-items:center;gap:14px;padding:14px 16px}
  .ihead .idx{width:26px;height:26px;flex:none;border-radius:7px;display:grid;place-items:center;
    background:var(--charcoal-3);border:1px solid var(--border-soft);color:var(--text-faint);
    font-size:11.5px;font-weight:800;font-variant-numeric:tabular-nums}
  .ihead .tx b{display:block;font-size:13.5px;font-weight:600}
  .ihead .r{margin-left:auto;flex:none;display:flex;align-items:center;gap:10px}
  .mark{display:inline-flex;align-items:center;justify-content:center;font-size:10.5px;font-weight:800;
    letter-spacing:.1em;text-transform:uppercase;text-indent:.05em;line-height:1;padding:5px 10px;
    border-radius:7px;border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-dim)}
  .mark.on{color:var(--gold);border-color:rgba(226,182,92,.34);background:rgba(226,182,92,.08)}
  /* Full width, not indented under the title — see the same note on the
     review page. An answer is the content, not a caption. */
  .ibody{padding:0 16px 16px;border-top:1px solid var(--border-soft);
    animation:bsOpen .16s ease-out both}
  .ibody .prompt{font-size:12.5px;color:var(--text-faint);line-height:1.75;padding-top:14px}
  .ibody textarea{margin-top:11px}
  textarea,input[type=text]{width:100%;background:var(--charcoal);border:1px solid var(--border);
    border-radius:10px;color:var(--parchment);font:inherit;font-size:13px;padding:12px 13px;resize:vertical;
    line-height:1.7}
  textarea:focus,input[type=text]:focus{outline:none;border-color:rgba(226,182,92,.45)}
  .count{font-size:11.5px;color:var(--text-dim);margin-top:8px}
  .count.short{color:#d98a75}
  .count.ok{color:#9dbd77}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;
    border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint);font:inherit;
    font-size:12.5px;font-weight:700;cursor:pointer;transition:.14s}
  .btn:hover{color:var(--parchment);border-color:var(--charcoal-4)}
  .btn.primary{background:linear-gradient(145deg,var(--amber),var(--gold));color:#1a1206;border:none}
  .btn.primary:hover{filter:brightness(1.05);color:#1a1206}
  .btn[disabled]{opacity:.45;cursor:not-allowed}
  .btn.sm{padding:6px 11px;font-size:11.5px}

  .saved{font-size:11.5px;color:var(--text-dim);display:inline-flex;align-items:center;gap:7px}
  .saved s{width:6px;height:6px;border-radius:50%;background:var(--ok);text-decoration:none}
  .saved.busy s{background:var(--amber)}
  .saved.bad s{background:var(--danger)}

  .pill{display:inline-flex;align-items:center;justify-content:center;font-size:10.5px;font-weight:800;
    letter-spacing:.1em;text-transform:uppercase;padding:5px 11px;border-radius:100px;line-height:1;
    text-indent:.05em;color:var(--tone-text,#968e7e);
    background:color-mix(in srgb,var(--tone,#8a7f70) 17%,transparent);
    border:1px solid color-mix(in srgb,var(--tone,#8a7f70) 44%,transparent)}
  .pill.pend{--tone:#d4923a;--tone-text:#dda157}
  .pill.pass{--tone:#7fa05a;--tone-text:#9dbd77}
  .pill.fail{--tone:#c1553f;--tone-text:#d98a75}
  .pill.draft{--tone:#8a7f70;--tone-text:#968e7e}

  .arow{display:flex;align-items:center;gap:11px;padding:11px 13px;background:var(--charcoal-3);
    border:1px solid var(--border-soft);border-radius:11px;font-size:12.5px}
  .arow + .fb{margin-top:10px}
  /* Attempt rows carry a second line naming who reviewed it. */
  .arow b{display:block;font-size:12.8px;font-weight:600}
  .arow .d{display:block;font-size:11.5px;color:var(--text-faint);margin-top:2px;line-height:1.45}
  .arow .r{white-space:nowrap}
  .arow .r{margin-left:auto;display:flex;gap:9px;align-items:center;color:var(--text-dim)}
  .alist{display:flex;flex-direction:column;gap:7px}

  /* Feedback can be very long. Six lines, a fade, and a control that opens
     the rest — so one wall of text cannot push everything else off screen,
     and nothing is ever truncated away permanently. */
  .fb{border-radius:12px;padding:14px 16px;background:rgba(193,85,63,.08);
    border:1px solid rgba(193,85,63,.26)}
  .fb.good{background:rgba(127,160,90,.08);border-color:rgba(127,160,90,.26)}
  .fb h5{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#d98a75;margin-bottom:8px}
  .fb.good h5{color:#9dbd77}
  .fb .body{font-size:12.5px;color:var(--text-faint);line-height:1.75;white-space:pre-wrap;
    max-height:120px;overflow:hidden;position:relative}
  .fb .body.open{max-height:none}
  .fb .body:not(.open).clipped::after{content:"";position:absolute;left:0;right:0;bottom:0;height:38px;
    background:linear-gradient(180deg,rgba(24,19,18,0),#1a1512)}
  .fb.good .body:not(.open).clipped::after{background:linear-gradient(180deg,rgba(20,24,18,0),#161a12)}
  .fb .more{font-size:11.5px;font-weight:700;color:var(--gold);margin-top:9px;display:inline-block;cursor:pointer}

  .state{display:flex;gap:15px;align-items:center;background:var(--charcoal-2);
    border:1px solid var(--border-soft);border-left:3px solid var(--amber);border-radius:14px;padding:16px 18px}
  .state.good{border-left-color:var(--ok)}
  .state .ic{width:34px;height:34px;flex:none;border-radius:10px;display:grid;place-items:center;
    background:rgba(212,146,58,.11);border:1px solid rgba(212,146,58,.26)}
  .state.good .ic{background:rgba(127,160,90,.11);border-color:rgba(127,160,90,.3)}
  .state svg{width:17px;height:17px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .state.good svg{stroke:#9dbd77}
  .state h4{font-size:13.5px;font-weight:700}
  .state p{font-size:12.5px;color:var(--text-faint);margin-top:2px}
  .state .act{margin-left:auto;flex:none}

  .arow.open{cursor:pointer;transition:background .13s,border-color .13s}
  .arow.open:hover{background:var(--charcoal-4);border-color:rgba(226,182,92,.26)}
  .arow .go{width:14px;height:14px;stroke:currentColor;fill:none;flex:none}
  .ihead[data-q]{cursor:pointer;user-select:none}
  .ihead .cnt{font-size:11.5px;color:var(--text-dim)}
  .ib{width:29px;height:29px;flex:none;border-radius:8px;display:grid;place-items:center;
    border:1px solid var(--border);background:var(--charcoal-3);color:var(--text-faint)}
  .ib svg{width:14px;height:14px}
  .answer{background:var(--charcoal);border:1px solid var(--border);border-radius:10px;padding:13px 14px;
    font-size:13px;line-height:1.8;white-space:pre-wrap;margin-top:11px;overflow-wrap:anywhere}
  .backline{display:flex;align-items:center;gap:11px;margin-bottom:16px;flex-wrap:wrap}
  .backline .btn svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2.2}
  /* ---- the passed view ---- */
  .hero{background:linear-gradient(180deg,rgba(127,160,90,.10),rgba(127,160,90,.02));
    border:1px solid rgba(127,160,90,.34);border-radius:16px;padding:34px 30px;text-align:center}
  .hero .tick{width:56px;height:56px;margin:0 auto 16px;border-radius:50%;display:grid;
    place-items:center;background:rgba(127,160,90,.16);border:1px solid rgba(127,160,90,.4)}
  .hero .tick svg{width:26px;height:26px;stroke:#9dbd77;fill:none;stroke-width:2.6}
  .hero h2{font-size:27px;font-weight:800;letter-spacing:-.03em}
  .hero p{font-size:13.5px;color:var(--text-faint);margin:9px auto 0;max-width:62ch;line-height:1.7}
  .herobtns{display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap}
  .nextgrid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:16px}
  @media (max-width:880px){ .nextgrid{grid-template-columns:1fr} }
  .tile{background:var(--charcoal-2);border:1px solid var(--border);border-radius:13px;
    padding:17px 18px;transition:border-color .14s}
  .tile:hover{border-color:var(--charcoal-4)}
  .tile .ti{width:32px;height:32px;border-radius:9px;display:grid;place-items:center;
    background:var(--charcoal-3);border:1px solid var(--border);margin-bottom:11px}
  .tile .ti svg{width:16px;height:16px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .tile b{display:block;font-size:13.5px;font-weight:700}
  .tile span{display:block;font-size:12.5px;color:var(--text-faint);line-height:1.7;margin-top:5px}


  /* =====================================================================
     THE "NOT IN YET" VIEW  —  no application, or the last one denied

     One column, full width. The status band says plainly that the game
     server is closed and the rest of the community is not; the figures
     turn "wait" into a number; the guidance answers the questions that
     otherwise arrive as tickets.
     ===================================================================== */
  .band{border:1px solid var(--border);border-radius:16px;background:var(--charcoal-2);
    overflow:hidden;margin-bottom:15px;--acc:var(--gold);--accsoft:rgba(226,182,92,.10)}
  .band.denied{--acc:#c2604b;--accsoft:rgba(194,96,75,.10)}
  .band-top{position:relative;display:flex;align-items:flex-start;gap:20px;padding:19px 22px 20px;
    background:linear-gradient(92deg,var(--accsoft),transparent 60%)}
  .band-top::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--acc)}
  .eyebrow{display:inline-flex;align-items:center;font-size:10px;font-weight:700;letter-spacing:.15em;
    text-transform:uppercase;color:var(--acc)}
  .band-top h2{font-size:18px;font-weight:700;line-height:1.3;margin:8px 0 5px;letter-spacing:-.01em}
  .band-top p{margin:0;color:var(--text-faint);font-size:12.8px;max-width:640px;line-height:1.6}
  .band-cta{margin-left:auto;flex:none;align-self:center;display:flex;flex-direction:column;
    align-items:center;gap:8px;text-align:center}
  .band-cta .mins{font-size:11.5px;color:var(--text-dim)}

  /* Two cards on one row: the figures, and the one thing support can help
     with. Sized so neither has slack to distribute. */
  .row2{display:grid;grid-template-columns:2fr 1fr;gap:15px;align-items:stretch;margin-bottom:15px}
    /* The page stacks cards with `.card + .card{margin-top}`. Inside this
     row that rule pushed the second card 16px below the first, so the two
     tops did not line up however their heights were matched. */
  .row2 .card,.row2 .card + .card{margin:0;height:100%;display:flex;flex-direction:column}
  .row2 .card .card-b,.row2 .card .stats4{flex:1}
  .stats4{display:grid;grid-template-columns:repeat(4,1fr);align-items:stretch}
  .stats4 .s{padding:14px 18px 15px;border-right:1px solid var(--border-soft);
    display:flex;flex-direction:column;justify-content:center}
  .stats4 .s:last-child{border-right:none}
  /* Two lines reserved, so a label that wraps does not drop its figure
     below the others. */
  .stats4 .k{font-size:10px;font-weight:600;line-height:1.35;letter-spacing:.1em;text-transform:uppercase;
    color:var(--text-dim);min-height:27px}
  .stats4 .v{font-size:23px;font-weight:700;line-height:1.1;margin-top:6px;
    font-variant-numeric:tabular-nums;letter-spacing:-.01em}
  .stats4 .n{font-size:11.5px;color:var(--text-faint);margin-top:5px;line-height:1.45}
  .qfoot{padding:12px 18px;border-top:1px solid var(--border-soft);font-size:11.8px;color:var(--text-dim)}
  .fix{display:flex;flex-direction:column;height:100%}
  .fix p{margin:0 0 12px;color:var(--text-faint);font-size:12.5px;line-height:1.6}
  .fix p + p{margin-bottom:14px}
  .fix .btn{margin-top:auto;width:100%}

  /* What is open to them and what is not. Fixed height per row so the four
     read as one set; a description that would wrap is clipped instead. */
  .acc2{display:grid;grid-template-columns:1fr 1fr;gap:9px}
  .accrow{display:flex;align-items:center;gap:13px;padding:0 15px;height:66px;
    border:1px solid var(--border-soft);border-radius:11px;background:var(--charcoal-3)}
  .accrow.open{cursor:pointer}
  .accrow.open:hover{border-color:var(--charcoal-4)}
  .accrow .i{width:32px;height:32px;border-radius:9px;flex:none;display:grid;place-items:center;
    background:var(--charcoal-4);color:var(--text-dim)}
  .accrow.open .i{color:var(--gold)}
  .accrow .i svg{width:16px;height:16px;fill:currentColor;stroke:none}
  .accrow .tx{min-width:0;flex:1}
  .accrow b{display:block;font-size:12.8px;font-weight:600}
  .accrow .d{display:block;font-size:11.8px;color:var(--text-faint);line-height:1.45;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .accrow .st{margin-left:auto;flex:none;display:flex;align-items:center;gap:14px}
  .accrow .st .pill{width:74px}
  .accrow .ch{width:15px;height:15px;color:var(--text-dim)}
  .accrow .ch svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;display:block}
  .accrow .ch.none{visibility:hidden}

  /* The guidance. One open at a time — two open answers is a wall of text
     and the second is never the one being read. */
  .g{border:1px solid var(--border-soft);border-radius:11px;background:var(--charcoal-3);
    margin-bottom:8px;overflow:hidden}
  .g:last-child{margin-bottom:0}
  .g .gh{display:flex;align-items:center;gap:12px;padding:13px 15px;cursor:pointer;user-select:none}
  .g .gn{font-size:10.5px;font-weight:700;color:var(--text-dim);width:18px;flex:none;
    font-variant-numeric:tabular-nums}
  .g .gt{font-size:13px;font-weight:600}
  .g .gv{margin-left:auto;width:14px;height:14px;flex:none;color:var(--text-dim)}
  .g .gv svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2.2;display:block}
  .g.on{border-color:var(--charcoal-4)}
  .g.on .gn,.g.on .gt{color:var(--parchment)}
  .g.on .gv{color:var(--gold)}
  .g .gb{padding:0 15px 15px 45px;font-size:12.6px;color:var(--text-faint);line-height:1.65}
  .g .gb p{margin:0 0 9px}
  .g .gb p:last-child{margin-bottom:0}
  .g .gb ul{margin:9px 0 0;padding-left:17px}
  .g .gb li{margin-bottom:5px}
  .g .gb em{color:var(--parchment);font-style:normal;font-weight:600}

  @media (max-width:1000px){
    .row2{grid-template-columns:1fr}
    .stats4{grid-template-columns:1fr 1fr}
    .stats4 .s:nth-child(2){border-right:none}
    .acc2{grid-template-columns:1fr}
  }
  @media (max-width:700px){
    .band-top{flex-direction:column;gap:14px}
    .band-cta{margin-left:0;align-items:flex-start;text-align:left}
    .stats4{grid-template-columns:1fr}
    .stats4 .s{border-right:none;border-bottom:1px solid var(--border-soft)}
  }

  .lede{font-size:12.5px;color:var(--text-faint);line-height:1.75}
  .empty{border:1px dashed var(--border);border-radius:12px;padding:26px 18px;text-align:center;
    color:var(--text-dim);font-size:12.5px}

</style>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <div class="qhead">
        <span class="qi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6M9 17h3"/></svg></span>
        <div>
          <h1>Application</h1>
          <p>Everybody answers a short set of questions before they can play in Blaine County.
             Support Staff read every one by hand, and you get written feedback either way.</p>
        </div>
      </div>

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
      window.location.replace('/login?return=' + encodeURIComponent('/dashboard/application'));
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
     APPLICATION (player)

     The page has five shapes and one renderer, chosen by `state` from
     api/application-mine.php:

       none     nothing yet          -> the invitation to start
       draft    open, unsent         -> the form
       pending  sent                 -> the waiting notice, answers read-only
       denied   last one refused     -> feedback, and the invitation to retry
       passed   done                 -> a confirmation and nothing to do

     AUTOSAVE. Two seconds after typing stops, and again on the way out of
     the page. It writes only answers, only on a draft, only the caller's
     own — see api/application-save.php, which enforces all three regardless
     of what this file sends.
     ===================================================================== */
  var DATA = null, SAVE_TIMER = null, SAVING = false, DIRTY = false, LAST_SAVE = 0;
  var SAVE_DELAY = 2000;

  function el(id){ return document.getElementById(id); }
  function ago(ts){
    if(!ts) return '';
    var s = Math.max(1, Math.floor(Date.now()/1000) - ts);
    if(s < 60)    return s + ' second' + (s===1?'':'s') + ' ago';
    var m = Math.floor(s/60);  if(m < 60) return m + ' minute' + (m===1?'':'s') + ' ago';
    var h = Math.floor(m/60);  if(h < 24) return h + ' hour'   + (h===1?'':'s') + ' ago';
    var d = Math.floor(h/24);  if(d < 30) return d + ' day'    + (d===1?'':'s') + ' ago';
    return new Date(ts*1000).toLocaleDateString(undefined,{day:'numeric',month:'short',year:'numeric'});
  }
  function shortDate(ts){
    if(!ts) return '—';
    return new Date(ts*1000).toLocaleDateString(undefined,{day:'2-digit',month:'short',year:'numeric'});
  }
  /* Characters, counted the same way api/_applications.php does: runs of
     whitespace collapse to one and the ends are trimmed, so a minimum
     cannot be met with newlines. */
  function chars(s){ return (s||'').replace(/\s+/g,' ').trim().length; }

  /* ---------- feedback, however long it turns out to be ---------- */
  function feedbackCard(app){
    if(!app.feedback) return '';
    var good = app.status === 'passed';
    return '<div class="fb' + (good?' good':'') + '">' +
      '<h5>Feedback' + (app.decided && app.decided.name ? ' from ' + escapeHtml(app.decided.name) : '') + '</h5>' +
      '<div class="body">' + escapeHtml(app.feedback) + '</div>' +
      '<span class="more" data-more>Read all of it</span></div>';
  }
  /* The "read all" control is only drawn when the text actually overflows —
     otherwise every short note carries a link that does nothing. */
  function wireHistory(){
    document.querySelectorAll('[data-open]').forEach(function(r){
      r.addEventListener('click', function(){ location.hash = 'id=' + this.getAttribute('data-open'); });
      r.addEventListener('keydown', function(e){
        if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); this.click(); }
      });
    });
  }

  function wireFeedback(root){
    (root || document).querySelectorAll('.fb').forEach(function(fb){
      var body = fb.querySelector('.body'), more = fb.querySelector('[data-more]');
      if(!body || !more) return;
      if(body.scrollHeight <= body.clientHeight + 4){ more.style.display = 'none'; return; }
      body.classList.add('clipped');
      more.addEventListener('click', function(){
        var open = body.classList.toggle('open');
        more.textContent = open ? 'Show less' : 'Read all of it';
      });
    });
  }


  /* =====================================================================
     THE "NOT IN YET" VIEW

     Reached with no application at all, or with the last one denied. Both
     want the same page: what is closed, what is not, how long the wait is,
     and the answers to the questions people otherwise open a ticket to ask.

     Everything here degrades: the figures card only appears if the server
     sends `queue`, and the two outside links only appear if they are set
     below, so nothing renders a dead end.
     ===================================================================== */

  /* Set these to the real destinations. An empty string hides that row
     rather than linking somewhere that does not exist. */
  var DISCORD_URL = 'https://discord.gg/8GUuTBcEsD';
  var FORUM_RULES_URL = 'https://forum.blaineside.com';

  var GATE_ICONS = {
    game:'<path d="M17.5 5.5h-11A5.5 5.5 0 0 0 1 11v3.7a3.3 3.3 0 0 0 6 1.9l.6-.9h8.8l.6.9a3.3 3.3 0 0 0 6-1.9V11a5.5 5.5 0 0 0-5.5-5.5ZM8.8 12.4H7.4v1.4H6v-1.4H4.6V11H6V9.6h1.4V11h1.4Zm5.6 1.1a1.1 1.1 0 1 1 1.1-1.1 1.1 1.1 0 0 1-1.1 1.1Zm2.6-2.6a1.1 1.1 0 1 1 1.1-1.1 1.1 1.1 0 0 1-1.1 1.1Z"/>',
    discord:'<path d="M20.32 4.37a19.79 19.79 0 0 0-4.89-1.51.07.07 0 0 0-.8.04c-.2.37-.44.86-.6 1.25a18.27 18.27 0 0 0-5.49 0c-.16-.4-.4-.88-.61-1.25a.08.08 0 0 0-.08-.04A19.74 19.74 0 0 0 3.68 4.4a.07.07 0 0 0-.3.03C.53 9.05-.32 13.58.1 18.06a.08.08 0 0 0 .3.06 19.9 19.9 0 0 0 5.99 3.03.08.08 0 0 0 .09-.03c.46-.63.87-1.3 1.22-1.99a.08.08 0 0 0-.04-.11 13.1 13.1 0 0 1-1.87-.89.08.08 0 0 1 0-.13l.37-.29a.07.07 0 0 1 .08-.01c3.93 1.79 8.18 1.79 12.06 0a.07.07 0 0 1 .8.01l.37.29a.08.08 0 0 1 0 .13c-.6.35-1.22.65-1.88.9a.08.08 0 0 0-.4.1c.36.7.78 1.36 1.23 2a.08.08 0 0 0 .8.02 19.84 19.84 0 0 0 6-3.03.08.08 0 0 0 .04-.05c.5-5.18-.84-9.68-3.55-13.66a.06.06 0 0 0-.03-.03ZM8.02 15.33c-1.18 0-2.16-1.08-2.16-2.42 0-1.33.96-2.41 2.16-2.41 1.21 0 2.18 1.09 2.16 2.41 0 1.34-.96 2.42-2.16 2.42Zm7.97 0c-1.18 0-2.15-1.08-2.15-2.42 0-1.33.95-2.41 2.15-2.41 1.21 0 2.18 1.09 2.16 2.41 0 1.34-.95 2.42-2.16 2.42Z"/>',
    forum:'<path d="M3 3.8h13a1.8 1.8 0 0 1 1.8 1.8v7.6A1.8 1.8 0 0 1 16 15h-1.6l-3.3 2.9a.6.6 0 0 1-1-.45V15H3a1.8 1.8 0 0 1-1.8-1.8V5.6A1.8 1.8 0 0 1 3 3.8Zm2.2 3.4v1.5h8.6V7.2Zm0 3.2v1.5h5.8v-1.5Z"/><path d="M19.6 7.6h1.4A1.8 1.8 0 0 1 22.8 9.4V17a1.8 1.8 0 0 1-1.8 1.8h-.9v2.4a.6.6 0 0 1-1 .45L15.8 18.8h-1.3a1.8 1.8 0 0 1-1.55-.9h3.05A3.2 3.2 0 0 0 19.2 14.7V7.6Z"/>',
    ucp:'<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm4.6 3.4a2.4 2.4 0 1 0 0 4.8 2.4 2.4 0 0 0 0-4.8ZM4.4 17.2h8.4v-.6a4.2 4.2 0 0 0-8.4 0ZM14.6 8.6h5.2v1.6h-5.2Zm0 3.6h5.2v1.6h-5.2Z"/>'
  };
  var CHEV = '<svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>';

  /* Written as answers to the question somebody actually has. The subject
     is the rules and how roleplay works here — not who their character is,
     which is decided in game and never from this. */
  var GUIDE = [
    ['What is this application for?',
     '<p>It is a <em>knowledge check</em>, not an audition. We are confirming you understand the ' +
     'server rules and how roleplay is expected to work here before you are let loose in a world ' +
     'with other people in it.</p>' +
     '<p>You are not scored on writing ability, imagination, or how dramatic a backstory you can ' +
     'invent. There is no minimum word count and no bonus for length.</p>'],
    ['Does anything I write here affect my character?',
     '<p>No. Everything in this application is used once, to decide whether you have understood the ' +
     'rules, and never again. It does not become canon, it is not held against your character, and ' +
     'no member of staff will hold you to it in game.</p>' +
     '<p>Your character is made in game after you are accepted — name, job, history, all of it, and ' +
     'all yours.</p>'],
    ['How is my application judged?',
     '<p>One question at a time, against one test: does the answer show you understood what was ' +
     'asked? A reviewer is looking for three things.</p><ul>' +
     '<li>You answered <em>the question that was asked</em>, not a nearby one.</li>' +
     '<li>You gave the reasoning, not just the outcome. "I would wait" says nothing; "I would wait ' +
     'because the scene is still running and leaving would cut it short for everyone else" says ' +
     'everything.</li>' +
     '<li>Where a rule applies, you can say which one and what it means in practice.</li></ul>'],
    ['Why do applications get denied?',
     '<p>Almost every denial is one of four things, and all four are avoidable.</p><ul>' +
     '<li><em>One-line answers.</em> "Yes", "I would not do that", or the question repeated back.</li>' +
     '<li><em>Copied text.</em> A rule pasted in word for word, or an answer lifted from a guide, ' +
     'shows nothing about your understanding.</li>' +
     '<li><em>Answering a different question.</em> Read each one twice — several look alike and are not.</li>' +
     '<li><em>Contradicting a rule.</em> Usually the new-life or crime-zone rules, and usually ' +
     'because they were skimmed rather than read.</li></ul>'],
    ['How long does a decision take?',
     '<p>Most applications are decided within 24 hours. A busy weekend can push that to two days. ' +
     'The live figures are above, and they are the same ones staff see.</p>' +
     '<p>You are notified in this UCP the moment a decision is made. Accepted, and this page changes ' +
     'to show you how to connect; returned, and the feedback naming what to change is on the ' +
     'attempt itself.</p>'],
    ['What should I do if I am denied?',
     '<p>Open the attempt, read the feedback, and change what it names. There is no limit on ' +
     'attempts, no waiting period, and no penalty for having been returned before.</p>' +
     '<p>What does not work is sending the same answers again. If the feedback is not clear, the ' +
     'rulebook is the place to settle it — staff cannot write or check an answer for you.</p>'],
    ['How should I prepare before I start?',
     '<p>Read the rulebook once. Most questions here are answerable directly from it, and the two ' +
     'rules that cause the most denials — new life and crime zones — are worth reading twice.</p>' +
     '<p>Then set aside ten quiet minutes. Nothing is sent until you press submit and your draft is ' +
     'saved as you type, so you can stop and come back to it.</p>']
  ];

  /* ---------- the pieces ---------- */
  function bandHTML(denied){
    return '<div class="band' + (denied ? ' denied' : '') + '"><div class="band-top"><div>' +
      '<span class="eyebrow">' + (denied ? 'Application denied' : 'Not applied yet') + '</span>' +
      '<h2>' + (denied ? 'Your last application was returned' : 'You cannot join the server yet') + '</h2>' +
      '<p>' + (denied
        ? 'Open your last attempt, read the feedback, and change what it names. There is no limit on ' +
          'attempts and no waiting period — Discord, the forums and this UCP stay open to you throughout.'
        : 'The game server is whitelisted, so an accepted application is what opens it. Discord, the ' +
          'forums and this UCP are open to you right now, and stay open whatever the decision is.') +
      '</p></div><div class="band-cta">' +
      '<button class="btn primary" id="startBtn">' +
      (denied ? 'Start another application' : 'Start your application') + '</button>' +
      '<span class="mins">About 10 minutes · saved as you type</span></div></div></div>';
  }

  /* The figures come from the server or not at all. A page that invents
     "under 24 hours" is worse than a page that does not say. */
  function queueHTML(q){
    if(!q) return '';
    function s(k, v, n){
      return '<div class="s"><div class="k">' + escapeHtml(k) + '</div>' +
        '<div class="v">' + escapeHtml(String(v)) + '</div>' +
        '<div class="n">' + escapeHtml(n) + '</div></div>';
    }
    /* A figure the server could not work out is left out entirely. Four
       tiles is the usual case; a new server with nothing decided yet shows
       the two it can stand behind. */
    var tiles = '';
    if(q.waiting !== null && q.waiting !== undefined)
      tiles += s('Waiting', q.waiting, 'Applications in the queue ahead of a new one.');
    if(q.processed_24h !== null && q.processed_24h !== undefined)
      tiles += s('Processed in the last 24h', q.processed_24h, 'Decided by Support Staff, by hand.');
    if(q.typical_wait) tiles += s('Typical wait', q.typical_wait, 'Median from submitted to decided this week.');
    if(q.first_try_rate) tiles += s('Accepted first try', q.first_try_rate, 'Of applications sent in the last 30 days.');
    if(!tiles) return '';
    return '<div class="card"><div class="card-h"><h3>The queue right now</h3>' +
      '<div class="r"><span class="pill draft">LIVE</span></div></div>' +
      '<div class="stats4" style="grid-template-columns:repeat(' +
        (tiles.match(/class="s"/g) || []).length + ',1fr)">' + tiles +
      '</div><div class="qfoot">Read by hand, in the order received — no filter decides this and ' +
      'nobody is bumped up the queue.</div></div>';
  }

  function supportHTML(){
    return '<div class="card"><div class="card-h"><h3>Something not working?</h3></div>' +
      '<div class="card-b fix">' +
      '<p>Form will not save or submit, or an answer vanished? Open a ticket and say which question ' +
      'you were on.</p>' +
      '<p>Technical problems only — staff cannot help with the answers themselves.</p>' +
      '<a class="btn" href="/dashboard/reports">Open a support ticket</a></div></div>';
  }

  function gateRow(icon, name, note, open, href){
    return '<div class="accrow' + (open ? ' open' : '') + '"' +
      (open && href ? ' data-href="' + escapeHtml(href) + '" role="link" tabindex="0"' : '') + '>' +
      '<span class="i"><svg viewBox="0 0 24 24">' + GATE_ICONS[icon] + '</svg></span>' +
      '<span class="tx"><b>' + escapeHtml(name) + '</b><span class="d">' + escapeHtml(note) + '</span></span>' +
      '<span class="st"><span class="pill ' + (open ? 'pass' : 'fail') + '">' +
      (open ? 'Open' : 'Locked') + '</span>' +
      '<span class="ch' + (open && href ? '' : ' none') + '">' + CHEV + '</span></span></div>';
  }

  function gatesHTML(){
    var rows = [gateRow('game', 'Blaine County game server',
                        'Opens as soon as an application is accepted.', false, '')];
    if(DISCORD_URL)     rows.push(gateRow('discord','Discord','Your decision is announced here first.', true, DISCORD_URL));
    if(FORUM_RULES_URL) rows.push(gateRow('forum','Forums','The rulebook, factions and event posts.', true, FORUM_RULES_URL));
    rows.push(gateRow('ucp','This UCP','Profile, notifications, tickets and appeals.', true, '/profile'));
    var open = rows.length - 1;
    return '<div class="card"><div class="card-h"><h3>What you can use right now</h3>' +
      '<div class="r"><span class="pill draft">' + open + ' of ' + rows.length + ' open</span></div></div>' +
      '<div class="card-b"><div class="acc2">' + rows.join('') + '</div></div></div>';
  }

  function guideHTML(){
    var items = GUIDE.map(function(g, i){
      return '<div class="g" data-g="' + i + '"><div class="gh"><span class="gn">' +
        (i < 9 ? '0' : '') + (i+1) + '</span><span class="gt">' + escapeHtml(g[0]) + '</span>' +
        '<span class="gv"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span></div></div>';
    }).join('');
    return '<div class="card"><div class="card-h"><h3>Everything you need to know before applying</h3>' +
      '<div class="r"><span class="pill draft">' + GUIDE.length + ' topics</span></div></div>' +
      '<div class="card-b">' + items + '</div></div>';
  }

  /* One open at a time. Opening a second closes the first, so the page
     never becomes two walls of text with the wanted one below the fold. */
  function wireGuide(){
    document.querySelectorAll('[data-g]').forEach(function(g){
      g.querySelector('.gh').addEventListener('click', function(){
        var was = g.classList.contains('on');
        document.querySelectorAll('[data-g].on').forEach(function(o){
          o.classList.remove('on');
          var b = o.querySelector('.gb'); if(b) b.remove();
          o.querySelector('.gv').innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>';
        });
        if(was) return;
        g.classList.add('on');
        g.querySelector('.gv').innerHTML = '<svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>';
        var d = document.createElement('div');
        d.className = 'gb';
        d.innerHTML = GUIDE[g.getAttribute('data-g') | 0][1];
        g.appendChild(d);
      });
    });
  }

  function wireGates(){
    document.querySelectorAll('[data-href]').forEach(function(r){
      r.addEventListener('click', function(){
        var h = this.getAttribute('data-href');
        if(/^https?:/.test(h)) window.open(h, '_blank', 'noopener');
        else location.href = h;
      });
      r.addEventListener('keydown', function(e){
        if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); this.click(); }
      });
    });
  }

  /* ---------- history ---------- */
  /* A row, not a row plus its feedback. The feedback lives inside the
     attempt — one wall of text per page rather than one per row, and the
     list stays a list. */
  var HIST_PAGE = 1, HIST_SIZE = 5;

  function historyRows(list){
    return list.map(function(h){
      var cls = h.status === 'passed' ? 'pass' : (h.status === 'denied' ? 'fail' : 'pend');
      var lab = h.status === 'passed' ? 'Passed' : (h.status === 'denied' ? 'Denied' : 'Waiting');
      var who = h.decided && h.decided.name ? h.decided.name : '';
      /* The feedback is named, not printed. Some of it runs to a paragraph
         or more, and a list of those is not a list. */
      var sub = h.status === 'denied'
        ? (who ? 'Feedback from ' + escapeHtml(who) + ' — open it to read what to change'
               : 'Open it to read the feedback')
        : (who ? 'Decided by ' + escapeHtml(who) : '');
      return '<div class="arow open" data-open="' + h.id + '" role="link" tabindex="0">' +
        '<span class="pill ' + cls + '">' + lab + '</span>' +
        '<span><b>Attempt ' + h.attempt + '</b>' +
        (sub ? '<span class="d">' + sub + '</span>' : '') + '</span>' +
        '<span class="r">' + shortDate(h.decided && h.decided.at ? h.decided.at : h.submitted_at) +
        '<svg class="go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">' +
        '<path d="M9 6l6 6-6 6"/></svg></span></div>';
    }).join('');
  }

  function pagerHTML(total, page, size){
    if(total <= size) return '';
    var pages = Math.ceil(total / size),
        first = (page - 1) * size + 1,
        last  = Math.min(page * size, total),
        b = [];
    b.push('<button class="arrow" data-page="' + (page - 1) + '"' + (page === 1 ? ' disabled' : '') +
      ' aria-label="Previous"><svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg></button>');
    for(var i = 1; i <= pages; i++){
      b.push('<button data-page="' + i + '"' + (i === page ? ' aria-current="true"' : '') + '>' + i + '</button>');
    }
    b.push('<button class="arrow" data-page="' + (page + 1) + '"' + (page === pages ? ' disabled' : '') +
      ' aria-label="Next"><svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg></button>');
    return '<div class="pager"><span class="pcount">Showing <b>' + first + '–' + last +
      '</b> of <b>' + total + '</b></span><div class="pnav">' + b.join('') + '</div></div>';
  }

  function historyCard(list){
    list = list || [];
    var total = list.length,
        pages = Math.max(1, Math.ceil(total / HIST_SIZE));
    if(HIST_PAGE > pages) HIST_PAGE = pages;
    var slice = list.slice((HIST_PAGE - 1) * HIST_SIZE, HIST_PAGE * HIST_SIZE);
    return '<div class="card" id="histCard"><div class="card-h"><h3>Your previous attempts</h3>' +
      '<div class="r"><span class="pill draft">' + total + '</span></div></div>' +
      '<div class="card-b">' + (total ? '<div class="alist">' + historyRows(slice) + '</div>' +
        pagerHTML(total, HIST_PAGE, HIST_SIZE)
        : '<div class="empty">Nothing yet. This is your first application.</div>') + '</div></div>';
  }

  /* Repaints the card in place — the page around it does not move, which
     is the whole point of paging rather than scrolling. */
  function wirePager(list){
    var card = el('histCard');
    if(!card) return;
    card.querySelectorAll('[data-page]').forEach(function(b){
      if(b.disabled) return;
      b.addEventListener('click', function(){
        HIST_PAGE = this.getAttribute('data-page') | 0;
        card.outerHTML = historyCard(list);
        wireHistory(); wirePager(list);
      });
    });
  }

  var NEXT_ICONS = {
    user:'<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
    book:'<path d="M4 5h7v15H4z"/><path d="M13 5h7v15h-7z"/><path d="M11 5v15"/>',
    chat:'<path d="M4 5h16v11H8l-4 3z"/>',
    gavel:'<path d="M3 21h8"/><path d="M6.5 17.5l7-7"/><path d="M11 4l6 6-2.5 2.5-6-6z"/><path d="M15 14l4.5 4.5"/>'
  };
  function nextTile(icon, title, body){
    return '<div class="tile"><span class="ti"><svg viewBox="0 0 24 24" fill="none" ' +
      'stroke="currentColor">' + (NEXT_ICONS[icon] || '') + '</svg></span>' +
      '<b>' + escapeHtml(title) + '</b><span>' + escapeHtml(body) + '</span></div>';
  }

  function nextCard(){
    return '<div class="card"><div class="card-h"><h3>What happens next</h3></div><div class="card-b">' +
      '<p class="lede">A Support Staff member reads your answers in full. If something is missing you get it ' +
      'back with a note saying exactly what to change — there is no limit on how many times you can apply, ' +
      'and the UCP and forums stay open to you throughout.</p></div></div>';
  }

  /* ---------- the form ---------- */
  function questionItem(a, i, readonly){
    return '<div class="item"><div class="ihead">' +
      '<span class="idx">' + (i+1) + '</span>' +
      '<span class="tx"><b>' + escapeHtml(a.title) + '</b></span>' +
      '<span class="r">' + (a.pinned ? '<span class="mark on">Always asked</span>' : '') + '</span></div>' +
      '<div class="ibody"><div class="prompt">' + escapeHtml(a.prompt) + '</div>' +
      (readonly
        ? '<div class="prompt" style="color:var(--parchment);white-space:pre-wrap">' +
            escapeHtml(a.body || '') + '</div>'
        : '<textarea rows="6" data-answer="' + a.id + '" data-min="' + a.min_chars + '" ' +
            'placeholder="Your answer…">' + escapeHtml(a.body || '') + '</textarea>' +
          '<div class="count" data-count="' + a.id + '"></div>') +
      '</div></div>';
  }

  function paintCount(id){
    var ta  = document.querySelector('[data-answer="' + id + '"]');
    var out = document.querySelector('[data-count="' + id + '"]');
    if(!ta || !out) return;
    var n = chars(ta.value), min = parseInt(ta.getAttribute('data-min'), 10) || 0;
    out.className = 'count' + (min ? (n >= min ? ' ok' : (n ? ' short' : '')) : '');
    out.textContent = min
      ? (n.toLocaleString() + ' / ' + min.toLocaleString() + ' characters minimum')
      : (n.toLocaleString() + ' character' + (n===1?'':'s'));
  }

  function setSaved(text, cls){
    var s = el('saveline');
    if(s){ s.className = 'saved ' + (cls || ''); s.innerHTML = '<s></s>' + escapeHtml(text); }
  }

  function collect(){
    var out = {};
    document.querySelectorAll('[data-answer]').forEach(function(ta){
      out[ta.getAttribute('data-answer')] = ta.value;
    });
    return out;
  }

  function saveNow(silent){
    if(!DATA || !DATA.current || DATA.state !== 'draft' || SAVING) return Promise.resolve();
    SAVING = true; DIRTY = false;
    if(!silent) setSaved('Saving…', 'busy');
    return UCP.post('application-save.php', {id: DATA.current.id, answers: collect()})
      .then(function(res){
        SAVING = false;
        if(res.data && res.data.ok){ LAST_SAVE = Math.floor(Date.now()/1000); setSaved('Saved automatically', ''); }
        else { setSaved('Not saved — ' + ((res.data && res.data.error) || 'try again'), 'bad'); }
      })
      .catch(function(){ SAVING = false; setSaved('Not saved — you appear to be offline', 'bad'); });
  }

  function queueSave(){
    DIRTY = true;
    setSaved('Unsaved changes', 'busy');
    clearTimeout(SAVE_TIMER);
    SAVE_TIMER = setTimeout(function(){ saveNow(false); }, SAVE_DELAY);
  }

  /* ---------- render ---------- */
  function render(){
    var d = DATA, h = '';

    if(!d.available){
      paint('body', '<div class="card"><div class="card-b"><p class="lede">' +
        escapeHtml(d.why || 'Applications aren\'t switched on yet.') + '</p></div></div>');
      return;
    }

    /* =================================================================
       PASSED

       The one page in the UCP somebody reaches exactly once, so it is
       worth more than a green tick. No attempt list here: a player who is
       in does not need a record of the times they were not, and the whole
       history is one click away on any of those rows anyway — this view
       is about what happens next.
       ================================================================= */
    if(d.state === 'passed'){
      var when = d.current && d.current.decided ? d.current.decided.at : null;
      var who  = d.current && d.current.decided ? d.current.decided.name : null;

      h = '<div class="hero">' +
        '<div class="tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
          '<path d="M5 13l4 4L19 7"/></svg></div>' +
        '<h2>You\'re in.</h2>' +
        '<p>Your application was accepted' + (who ? ' by ' + escapeHtml(who) : '') +
          (when ? ' on ' + shortDate(when) : '') + '. Blaine County is open to you — ' +
          'connect and start playing whenever you like.</p>' +
        '<div class="herobtns">' +
          '<a class="btn primary" href="/dashboard">Go to the dashboard</a>' +
          '<a class="btn" href="#" id="copyIp">Copy the server address</a>' +
        '</div></div>' +

        '<div class="nextgrid">' +
          nextTile('user', 'Make your character',
            'Your first character is created in game, and none of it is fixed by what you ' +
            'wrote here — the application only checks that you understand how roleplay works. ' +
            'Pick whatever name and history you actually want to play.') +
          nextTile('book', 'Read the rules once more',
            'You have shown you know them. The full set is on the forums, and the parts ' +
            'people trip over most are the crime-zone and chain-robbing rules.') +
          nextTile('chat', 'Say hello on the forums',
            'Factions recruit there, events are posted there, and it is the fastest way ' +
            'to find people to play with on your first night.') +
          nextTile('gavel', 'If something goes wrong',
            'Ban appeals, staff reports and refund requests all live in this UCP, in the ' +
            'menu on the left. Every one of them is read by a person.') +
        '</div>';

      paint('body', h);
      var ci = el('copyIp');
      if(ci) ci.addEventListener('click', function(e){
        e.preventDefault();
        /* No clipboard permission prompt on a failure path: if the browser
           refuses, the address is still shown rather than silently lost. */
        var addr = 'play.blaineside.com';
        if(navigator.clipboard && navigator.clipboard.writeText){
          navigator.clipboard.writeText(addr)
            .then(function(){ toast('Copied ' + addr); })
            .catch(function(){ toast(addr); });
        } else { toast(addr); }
      });
      return;
    }

    /* =================================================================
       NOT IN YET  —  never applied, or the last attempt denied

       The same page serves both: only the wording of the band and the
       presence of the attempt list differ.
       ================================================================= */
    if(d.state === 'none' || d.state === 'denied'){
      var denied = d.state === 'denied';
      h = bandHTML(denied) +
          /* No figures from the server means no card, and then the
             support box is the whole row rather than a third of one. */
          (d.queue ? '<div class="row2">' + queueHTML(d.queue) + supportHTML() + '</div>'
                   : supportHTML()) +
          (denied || (d.history || []).length ? historyCard(d.history) : '') +
          gatesHTML() + guideHTML();
      paint('body', h);
      wireHistory(); wirePager(d.history || []); wireGates(); wireGuide();
      el('startBtn').addEventListener('click', startApplication);
      return;
    }

    /* draft or pending */
    var readonly = d.state === 'pending';
    var items = (d.answers || []).map(function(a, i){ return questionItem(a, i, readonly); }).join('');

    h = (readonly
      ? '<div class="state"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
        '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><div>' +
        '<h4>Your application is with Support Staff</h4><p>Sent ' +
        ago(d.current.submitted_at) + '. You\'ll get a notification the moment it is decided ' +
        '— there is nothing you need to do in the meantime.</p></div>' +
        '<div class="act"><span class="pill pend">Waiting</span></div></div><div style="height:16px"></div>'
      : '') +
      '<div class="split"><div>' +
        '<div class="card"><div class="card-h"><h3>' +
          (readonly ? 'What you sent' : 'Your application') + '</h3><div class="r">' +
          (readonly ? '' : '<span class="saved" id="saveline"><s></s>Saved automatically</span>') +
          '<span class="pill ' + (readonly ? 'pend' : 'draft') + '">' +
          (readonly ? 'Attempt ' + d.current.attempt : 'Draft') + '</span></div></div>' +
        '<div class="card-b"><div class="items">' + items + '</div>' +
        (readonly ? '' :
          '<div style="display:flex;align-items:center;gap:11px;margin-top:18px">' +
          '<span class="saved"><s></s>Drafts are kept for 30 days</span>' +
          '<button class="btn primary" id="submitBtn" style="margin-left:auto">Submit application</button></div>') +
        '</div></div>' +
      '</div><div>' + historyCard(d.history) + nextCard() + '</div></div>';

    paint('body', h);
    wireFeedback(); wireHistory();

    if(!readonly){
      document.querySelectorAll('[data-answer]').forEach(function(ta){
        paintCount(ta.getAttribute('data-answer'));
        ta.addEventListener('input', function(){
          paintCount(this.getAttribute('data-answer'));
          queueSave();
        });
      });
      el('submitBtn').addEventListener('click', submitApplication);
    }
  }

  /* ---------- actions ---------- */
  function startApplication(){
    var b = el('startBtn'); if(b) b.disabled = true;
    UCP.post('application-start.php', {}).then(function(res){
      var d = res.data || {};
      if(!d.ok){ if(b) b.disabled = false; return toast(d.error || 'Could not start an application'); }
      load();
    }).catch(function(){ if(b) b.disabled = false; toast('Could not reach the server'); });
  }

  function submitApplication(){
    var b = el('submitBtn'); if(b) b.disabled = true;
    clearTimeout(SAVE_TIMER);
    /* Submit carries the answers too, so nothing typed in the last two
       seconds is lost between the autosave and the button. */
    UCP.post('application-submit.php', {id: DATA.current.id, answers: collect()})
      .then(function(res){
        var d = res.data || {};
        if(!d.ok){ if(b) b.disabled = false; return toast(d.error || 'Could not send it'); }
        toast('Sent. Support Staff will read it shortly.');
        load();
      })
      .catch(function(){ if(b) b.disabled = false; toast('Could not reach the server'); });
  }

  /* =====================================================================
     ONE PAST ATTEMPT

     Reached from the history list. Read-only, every answer collapsed, and
     the feedback shown in full — this is the page somebody opens BECAUSE
     they want to read the feedback, so it is not clipped here.
     ===================================================================== */
  var ONE = null, ONE_OPEN = {};

  function oneItem(a, i){
    var open = !!ONE_OPEN[a.id];
    return '<div class="item"><div class="ihead" data-q="' + a.id + '">' +
      '<span class="idx">' + (i+1) + '</span>' +
      '<span class="tx"><b>' + escapeHtml(a.title) + '</b></span>' +
      '<span class="r"><span class="cnt">' + a.chars.toLocaleString() + ' character' +
        (a.chars === 1 ? '' : 's') + '</span>' +
      '<span class="ib"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
      '<path d="M6 ' + (open ? '15l6-6 6 6' : '9l6 6 6-6') + '"/></svg></span></span></div>' +
      (open
        ? '<div class="ibody"><div class="prompt">' + escapeHtml(a.prompt) + '</div>' +
          '<div class="answer">' + escapeHtml(a.body || '') + '</div></div>'
        : '') + '</div>';
  }

  function renderOne(d){
    ONE = d;
    var good = d.status === 'passed';
    var cls  = good ? 'pass' : (d.status === 'denied' ? 'fail' : 'pend');
    var lab  = good ? 'Passed' : (d.status === 'denied' ? 'Denied' : 'Waiting');

    el('body').innerHTML =
      '<div class="backline"><button class="btn" id="backBtn">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">' +
      '<path d="M15 6l-6 6 6 6"/></svg>Back</button>' +
      '<span class="pill ' + cls + '">' + lab + '</span>' +
      '<span style="font-size:12.5px;color:var(--text-dim)">Attempt ' + d.attempt +
      ' · sent ' + shortDate(d.submitted_at) + '</span></div>' +
      (d.feedback
        ? '<div class="card"><div class="card-h"><h3>Feedback</h3></div><div class="card-b">' +
          '<div class="fb' + (good ? ' good' : '') + '"><h5>What you were told</h5>' +
          '<div class="body open">' + escapeHtml(d.feedback) + '</div></div></div></div>'
        : '') +
      '<div class="card"><div class="card-h"><h3>What you sent</h3>' +
      '<div class="r"><span class="pill draft">' + (d.answers||[]).length + ' question' +
      ((d.answers||[]).length === 1 ? '' : 's') + '</span></div></div>' +
      '<div class="card-b"><div class="items">' +
      (d.answers||[]).map(oneItem).join('') + '</div></div></div>';

    el('backBtn').addEventListener('click', function(){ location.hash = ''; });
    document.querySelectorAll('[data-q]').forEach(function(h){
      h.addEventListener('click', function(){
        var id = parseInt(this.getAttribute('data-q'), 10);
        ONE_OPEN[id] = !ONE_OPEN[id];
        renderOne(ONE);
      });
    });
  }

  function loadOne(id){
    UCP.get('application.php?id=' + id).then(function(d){
      if(!d || !d.ok){
        el('body').innerHTML = '<div class="card"><div class="card-b"><p class="lede">' +
          escapeHtml((d && d.error) || 'Could not open that application.') + '</p></div></div>';
        return;
      }
      ONE_OPEN = {};
      renderOne(d);
    });
  }

  function route(){
    var m = (location.hash || '').match(/id=(\d+)/);
    if(m) loadOne(parseInt(m[1], 10));
    else   load();
  }
  window.addEventListener('hashchange', route);

  function load(){
    UCP.get('application-mine.php').then(function(d){
      if(!d || !d.ok){
        el('body').innerHTML = '<div class="card"><div class="card-b"><p class="lede">' +
          escapeHtml((d && d.error) || 'Could not load your application.') + '</p></div></div>';
        return;
      }
      DATA = d;
      render();
    });
  }

  /* Last chance to keep what they typed: the browser will not wait for a
     fetch on unload, so this one is fired and forgotten. */
  window.addEventListener('beforeunload', function(){
    if(DIRTY && DATA && DATA.state === 'draft' && DATA.current){
      try {
        var body = JSON.stringify({id: DATA.current.id, answers: collect(),
                                   csrf: (window.UCP && UCP.csrf) ? UCP.csrf : undefined});
        navigator.sendBeacon('/api/application-save.php', new Blob([body], {type:'application/json'}));
      } catch(e){}
    }
  });
  document.addEventListener('visibilitychange', function(){
    if(document.visibilityState === 'hidden' && DIRTY) saveNow(true);
  });

  route();

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
