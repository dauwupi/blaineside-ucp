<?php
/**
 * County Bulletin.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'BlaineSide — County Bulletin';
$PAGE_HEADING = 'County Bulletin';
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
</style>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <!-- ============ LISTING VIEW ============ -->
      <div class="view active" id="view-list">
        <a class="page-back" href="/dashboard">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
          Back to dashboard
        </a>
        <div class="phead">
          <div>
            <h2>County Bulletin</h2>
            <p>Post news, events and notices. Choose which ones appear on the <b>dashboard</b>.</p>
          </div>
          <button class="btn primary" onclick="openEditor()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg>
            New bulletin
          </button>
        </div>

        <div class="slotbar">
          <span class="si"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h13v14H4z"/><path d="M17 8h3v9a2 2 0 0 1-2 2M7 9h7M7 13h7M7 17h4"/></svg>On dashboard</span>
          <span class="cnt"><b id="slotUsed">0</b> / <span id="slotMax">5</span></span>
          <span class="track"><i id="slotFill" style="width:0%"></i></span>
          <span class="note" id="slotNote">Up to 5 bulletins rotate on the dashboard.</span>
        </div>

        <div class="grid" id="grid"></div>
        <div class="pager" id="pager"></div>
      </div>

      <!-- ============ CREATE / EDIT VIEW ============ -->
      <div class="view" id="view-edit">
        <a class="page-back" href="#" onclick="closeEditor();return false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
          Back to all bulletins
        </a>
        <div class="phead"><div><h2 id="editTitle">New bulletin</h2><p>Fill in the details. The preview on the right updates as you type.</p></div></div>

        <div class="editor">
          <div class="form-card">
            <div class="field">
              <label><span class="lt">Type <span class="req">*</span></span></label>
              <div class="seg" id="typeSeg">
                <button data-type="event" class="on"><span class="swatch" style="background:var(--danger)"></span>Event</button>
                <button data-type="update"><span class="swatch" style="background:var(--stone)"></span>Update</button>
                <button data-type="notice"><span class="swatch" style="background:var(--gold)"></span>Notice</button>
              </div>
            </div>

            <div class="field">
              <label><span class="lt">Title <span class="req">*</span></span><span class="count"><span id="titleCount">0</span>/70</span></label>
              <input type="text" id="fTitle" maxlength="70" placeholder="e.g. Sandy Shores Rodeo returns this weekend" oninput="syncPreview()">
            </div>

            <div class="field">
              <label><span class="lt">Description <span class="req">*</span></span><span class="count"><span id="bodyCount">0</span>/240</span></label>
              <textarea id="fBody" maxlength="240" placeholder="What's happening, when, and what players need to know." oninput="syncPreview()"></textarea>
            </div>

            <div class="field">
              <label><span class="lt">Link <span class="opt">(optional)</span></span></label>
              <input type="text" id="fLink" placeholder="https://… — players who click the bulletin go here" oninput="syncPreview()">
              <span class="field-hint" id="linkHint"></span>
            </div>

            <div class="field">
              <label><span class="lt">Author</span></label>
              <div class="locked-field" title="Automatically set to your UCP name">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 11V8a5 5 0 0 1 10 0v3M5 11h14v9H5z"/></svg>
                <span id="fByLabel">dustin_hale</span>
                <span class="lock-note">Your UCP name</span>
              </div>
            </div>

            <div class="field">
              <label><span class="lt">Image <span class="opt">(optional)</span></span></label>
              <div class="uploader" id="uploader">
                <div class="drop" id="drop">
                  <span class="di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 16l5-5 4 4 3-3 4 4M4 20h16V4H4z"/></svg></span>
                  <h5>Drop an image here or <span class="browse">browse</span></h5>
                  <p>PNG or JPG · a wide image works best for the banner</p>
                </div>
                <input type="file" id="fileInput" accept="image/png,image/jpeg,image/webp" hidden>
              </div>
            </div>

            <div class="form-actions">
              <button class="btn ghost" onclick="closeEditor()">Cancel</button>
              <button class="btn primary" id="saveBtn" onclick="saveBulletin()">Publish bulletin</button>
            </div>
          </div>

          <div class="preview-wrap">
            <div class="preview-label"><span class="dotlbl"></span>Live preview</div>
            <div class="pv-slide g1" id="pv">
              <div class="pvbg" id="pvbg"></div>
              <div class="cap">
                <span class="tag tg evt" id="pvTag">Event</span>
                <h4 id="pvTitle">Your title appears here</h4>
                <p id="pvBody">Your description will show here as you write it, giving players the key details at a glance.</p>
                <div class="m" id="pvMeta">Just now · Staff Team</div>
                <span class="pv-link" id="pvLink" hidden>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                  Read more
                </span>
              </div>
            </div>
            <p class="pv-note">Exactly how it looks on the dashboard when shown — including the Read more button, which appears whenever a link is set. Drag the image in its frame above to reposition it.</p>
          </div>
        </div>
      </div>

    </main>
  </div>

  <div class="toast" id="toast"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6L9 17l-5-5"/></svg><span id="toastMsg">Saved</span></div>

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
  let UCP_NAME="";                // filled from api/session.php
  let MAX_SHOWN=5;                // both confirmed by the server on load
  let PER_PAGE=6;
  const THEME_BY_TYPE={event:1,update:2,notice:3};
  const TAGCLASS={event:'evt',update:'upd',notice:''};
  const TAGLABEL={event:'Event',update:'Update',notice:'Notice'};

  /* One page of bulletins, straight from api/bulletins.php. The listing
     deliberately carries no image data — six banners of base64 would be a
     slow payload for a thumbnail — so cards draw the type wash instead and
     the editor fetches the full row when it opens. */
  let BULLETINS=[];
  let TOTAL_PAGES=1, SHOWN_COUNT=0, LOADING=false;

  /* "2 hours ago" from a unix timestamp. */
  function whenFrom(ts){
    if(window.UCP && UCP.relTime) return UCP.relTime(ts);
    const d=Math.floor(Date.now()/1000)-ts;
    if(d<3600) return Math.max(1,Math.floor(d/60))+' min ago';
    if(d<86400) return Math.floor(d/3600)+'h ago';
    return Math.floor(d/86400)+'d ago';
  }

  const grid=document.getElementById('grid');

  function shownCount(){ return SHOWN_COUNT; }

  function renderSlotbar(){
    const used=shownCount();
    document.getElementById('slotUsed').textContent=used;
    document.getElementById('slotMax').textContent=MAX_SHOWN;
    document.getElementById('slotFill').style.width=(used/MAX_SHOWN*100)+'%';
    const note=document.getElementById('slotNote');
    note.textContent = used>=MAX_SHOWN
      ? 'Dashboard is full — turn one off to feature another.'
      : `${MAX_SHOWN-used} more can be shown on the dashboard.`;
    note.style.color = used>=MAX_SHOWN ? 'var(--warn)' : 'var(--text-dim)';
  }

  function cardHTML(b){
    const full=shownCount()>=MAX_SHOWN;
    const lockOff = !b.shown && full; /* can't turn on when full */
    /* The listing carries a small thumbnail, not the full banner. Cards
       without one (no image, or an older row) fall back to the type wash. */
    const thumb = b.thumb
      ? `<div class="thumb"><img src="${b.thumb}" style="object-position:center ${b.imgpos}%"><div class="grad"></div></div>`
      : `<div class="thumb noimg g${THEME_BY_TYPE[b.type]}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h13v14H4z"/><path d="M17 8h3v9M7 9h7M7 13h7"/></svg></div>`;
    return `<div class="bcard ${b.shown?'shown':''}" data-id="${b.id}">
      <div class="thumb-wrap" style="position:relative">
        ${thumb}
        <span class="tag ${TAGCLASS[b.type]}">${TAGLABEL[b.type]}</span>
        ${b.shown?`<span class="shown-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6L9 17l-5-5"/></svg>On dashboard</span>`:''}
      </div>
      <div class="body">
        <h3>${escapeHtml(b.title)}</h3>
        <p>${escapeHtml(b.body)}</p>
        <div class="meta">${escapeHtml(whenFrom(b.at))} · ${escapeHtml(b.by||'Staff')}${b.link?` · <span class="link-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>Link</span>`:''}</div>
      </div>
      <div class="foot" data-foot>
        <label class="toggle ${b.shown?'on':''} ${lockOff?'disabled':''}" ${lockOff?'title="Dashboard is full"':''} onclick="toggleShown(${b.id},event)">
          <span class="sw"></span>${b.shown?'Showing':'Show on dashboard'}
        </label>
        <button class="icon-act" title="Edit" onclick="openEditor(${b.id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20h4L18 10l-4-4L4 16z"/><path d="M13 5l4 4"/></svg></button>
        <button class="icon-act del" title="Delete" onclick="askDelete(${b.id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 7h14M9 7V5h6v2M6 7l1 13h10l1-13"/></svg></button>
      </div>
    </div>`;
  }

  let curPage=1;

  function renderList(){
    const pager=document.getElementById('pager');
    if(!BULLETINS.length){
      grid.style.display='block';
      grid.innerHTML=`<div class="empty">
        <span class="ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h13v14H4z"/><path d="M17 8h3v9a2 2 0 0 1-2 2M7 9h7M7 13h7M7 17h4"/></svg></span>
        <h4>No bulletins yet</h4>
        <p>Create your first bulletin to share county news, events and notices with players.</p>
        <button class="btn primary" onclick="openEditor()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg>New bulletin</button>
      </div>`;
      pager.innerHTML='';
      renderSlotbar();
      return;
    }
    grid.style.display='grid';
    grid.innerHTML=BULLETINS.map(cardHTML).join('');
    renderPager(TOTAL_PAGES);
    renderSlotbar();
  }

  /* Pulls one page from the server. Every action that changes something
     calls this afterwards rather than patching the local copy, so what is
     on screen is what is in the database — including whatever another
     manager did while this page sat open. */
  function loadPage(n){
    if(LOADING) return Promise.resolve();
    LOADING=true;
    curPage = n || curPage;
    return UCP.get('bulletins.php?scope=all&page='+curPage).then(function(d){
      LOADING=false;
      if(!d || d.ok!==true){
        if(d && d.authenticated===false){ window.location.replace('/login?return='+encodeURIComponent('/dashboard/bulletin')); return; }
        toast('Could not load bulletins');
        return;
      }
      BULLETINS   = d.bulletins||[];
      TOTAL_PAGES = d.pages||1;
      SHOWN_COUNT = d.shown||0;
      MAX_SHOWN   = d.max_shown||MAX_SHOWN;
      PER_PAGE    = d.per_page||PER_PAGE;
      curPage     = d.page||curPage;
      renderList();
    }).catch(function(){ LOADING=false; toast('Could not reach the server'); });
  }

  function renderPager(totalPages){
    const pager=document.getElementById('pager');
    if(totalPages<=1){ pager.innerHTML=''; return; }
    const prev=`<button class="pg" data-pg="${curPage-1}" ${curPage===1?'disabled':''} aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg></button>`;
    const next=`<button class="pg" data-pg="${curPage+1}" ${curPage===totalPages?'disabled':''} aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 6l6 6-6 6"/></svg></button>`;
    const pages=[];
    for(let i=1;i<=totalPages;i++){ if(i===1||i===totalPages||Math.abs(i-curPage)<=1) pages.push(i); }
    const nums=[]; let last=0;
    pages.forEach(p=>{ if(p-last>1) nums.push('<span class="pg-gap">…</span>'); nums.push(`<button class="pg ${p===curPage?'on':''}" data-pg="${p}">${p}</button>`); last=p; });
    pager.innerHTML=prev+nums.join('')+next;
  }

  document.getElementById('pager').addEventListener('click',e=>{
    const p=e.target.closest('[data-pg]'); if(!p)return;
    loadPage(parseInt(p.dataset.pg,10)).then(function(){
      document.querySelector('.content').scrollIntoView({behavior:'smooth',block:'start'});
    });
  });

  function toggleShown(id,e){
    if(e) e.preventDefault();
    const b=BULLETINS.find(x=>x.id===id); if(!b) return;
    if(!b.shown && SHOWN_COUNT>=MAX_SHOWN){ toast('Dashboard is full — turn one off first'); return; }

    UCP.post('bulletin-toggle.php',{id:id, on:!b.shown}).then(function(res){
      const d=res.data||{};
      if(!d.ok){ toast(d.error||'That did not work'); loadPage(); return; }
      toast(d.message);
      loadPage();
    }).catch(function(){ toast('Could not reach the server'); });
  }

  /* inline delete confirm */
  function askDelete(id){
    const card=grid.querySelector(`.bcard[data-id="${id}"]`); if(!card) return;
    const foot=card.querySelector('[data-foot]');
    if(card.querySelector('.confirm')) return;
    const c=document.createElement('div'); c.className='confirm';
    c.innerHTML=`<span class="q">Delete this bulletin?</span>
      <button class="btn sm ghost" data-cancel>Cancel</button>
      <button class="btn sm danger" data-confirm>Delete</button>`;
    foot.style.display='none';
    foot.after(c);
    c.querySelector('[data-cancel]').onclick=()=>{c.remove();foot.style.display='';};
    c.querySelector('[data-confirm]').onclick=()=>{
      c.querySelector('[data-confirm]').textContent='Deleting…';
      UCP.post('bulletin-delete.php',{id:id}).then(function(res){
        const d=res.data||{};
        if(!d.ok){ toast(d.error||'That did not work'); c.remove(); foot.style.display=''; return; }
        toast(d.message);
        loadPage();
      }).catch(function(){ toast('Could not reach the server'); c.remove(); foot.style.display=''; });
    };
  }

  /* ===================== EDITOR ===================== */
  let editingId=null;
  let draft={type:'event',title:'',body:'',link:'',image:null,thumb:null,imgpos:50,imageTouched:false};

  function showView(name){
    document.getElementById('view-list').classList.toggle('active',name==='list');
    document.getElementById('view-edit').classList.toggle('active',name==='edit');
    document.querySelector('.page-title h1').textContent = name==='edit' ? 'County Bulletin — Editor' : 'County Bulletin';
    window.scrollTo(0,0);
  }

  function openEditor(id){
    if(id){
      /* The listing carries no image data, so fetch the full row before
         opening — otherwise a save would look like the picture was removed. */
      const b=BULLETINS.find(x=>x.id===id); if(!b) return;
      editingId=id;
      draft={type:b.type,title:b.title,body:b.body,link:b.link||'',image:null,thumb:null,imgpos:b.imgpos,imageTouched:false};
      if(b.has_image){
        UCP.get('bulletins.php?id='+id).then(function(d){
          if(d && d.ok===true && d.bulletin && editingId===id){
            draft.image=d.bulletin.image;
            draft.imgpos=d.bulletin.imgpos;
            renderUploader(); syncPreview();
          }
        });
      }
      document.getElementById('editTitle').textContent='Edit bulletin';
      document.getElementById('saveBtn').textContent='Save changes';
    } else {
      editingId=null;
      draft={type:'event',title:'',body:'',link:'',image:null,thumb:null,imgpos:50,imageTouched:false};
      document.getElementById('editTitle').textContent='New bulletin';
      document.getElementById('saveBtn').textContent='Publish bulletin';
    }
    /* fill form */
    document.getElementById('fTitle').value=draft.title;
    document.getElementById('fBody').value=draft.body;
    document.getElementById('fLink').value=draft.link;
    document.getElementById('fByLabel').textContent=UCP_NAME;  /* author always locked to UCP name */
    document.querySelectorAll('#typeSeg button').forEach(btn=>btn.classList.toggle('on',btn.dataset.type===draft.type));
    renderUploader();
    syncPreview();
    showView('edit');
  }
  function closeEditor(){ showView('list'); renderList(); }

  /* type segmented control */
  document.getElementById('typeSeg').addEventListener('click',e=>{
    const btn=e.target.closest('button'); if(!btn) return;
    draft.type=btn.dataset.type;
    document.querySelectorAll('#typeSeg button').forEach(b=>b.classList.toggle('on',b===btn));
    syncPreview();
  });

  /* normalise + validate a link. Returns {url, ok, msg} */
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

  /* counts + preview */
  function syncPreview(){
    draft.title=document.getElementById('fTitle').value;
    draft.body=document.getElementById('fBody').value;
    draft.link=document.getElementById('fLink').value;
    document.getElementById('titleCount').textContent=draft.title.length;
    document.getElementById('bodyCount').textContent=draft.body.length;

    /* link hint */
    const lc=checkLink(draft.link);
    const hint=document.getElementById('linkHint');
    hint.textContent=lc.msg;
    hint.className='field-hint '+(draft.link.trim()?(lc.ok?'ok':'err'):'');

    const theme=THEME_BY_TYPE[draft.type];
    const pv=document.getElementById('pv');
    pv.className='pv-slide g'+theme+(draft.image?' has-img':'');
    const tag=document.getElementById('pvTag');
    tag.className='tag tg '+(TAGCLASS[draft.type]||'');
    tag.textContent=TAGLABEL[draft.type];
    document.getElementById('pvTitle').textContent=draft.title||'Your title appears here';
    document.getElementById('pvBody').textContent=draft.body||'Your description will show here as you write it, giving players the key details at a glance.';
    document.getElementById('pvMeta').textContent='Just now · '+UCP_NAME;
    document.getElementById('pvLink').hidden = !(lc.ok && lc.url);

    const pvbg=document.getElementById('pvbg');
    if(draft.image){
      pvbg.innerHTML=`<img src="${draft.image}" style="object-position:center ${draft.imgpos}%">`;
    } else { pvbg.innerHTML=''; }

    const linkValid = !draft.link.trim() || lc.ok;
    document.getElementById('saveBtn').disabled = !(draft.title.trim() && draft.body.trim() && linkValid);
  }

  /* ===================== IMAGE UPLOAD + REPOSITION ===================== */
  const fileInput=document.getElementById('fileInput');
  const drop=document.getElementById('drop');
  drop.addEventListener('click',()=>fileInput.click());
  drop.addEventListener('dragover',e=>{e.preventDefault();drop.classList.add('drag');});
  drop.addEventListener('dragleave',()=>drop.classList.remove('drag'));
  drop.addEventListener('drop',e=>{e.preventDefault();drop.classList.remove('drag');if(e.dataTransfer.files[0])loadImage(e.dataTransfer.files[0]);});
  fileInput.addEventListener('change',e=>{if(e.target.files[0])loadImage(e.target.files[0]);});

  /* Downscale before it ever leaves the browser: a phone photo is several
     megabytes, and this ends up as a data: URL in one database column.
     1600px wide, re-encoded as JPEG, puts a banner at roughly 150-300 KB —
     well inside the server's 1.2 MB ceiling. */
  function loadImage(file){
    if(!file) return;
    if(!file.type.startsWith('image/')){toast('That file isn\u2019t an image');return;}
    const r=new FileReader();
    r.onload=()=>{
      const img=new Image();
      img.onload=()=>{
        const MAX=1600, scale=Math.min(1, MAX/img.width);
        const c=document.createElement('canvas');
        c.width=Math.round(img.width*scale); c.height=Math.round(img.height*scale);
        c.getContext('2d').drawImage(img,0,0,c.width,c.height);
        let out; try{ out=c.toDataURL('image/jpeg',0.82); }catch(e){ out=r.result; }
        /* Small PNGs sometimes grow when re-encoded — keep whichever is smaller. */
        draft.image = out.length < r.result.length ? out : r.result;

        /* And a card-sized copy in the same pass, so the management listing
           can show six thumbnails without shipping six banners. */
        const tw=560, ts=Math.min(1, tw/img.width);
        const t=document.createElement('canvas');
        t.width=Math.round(img.width*ts); t.height=Math.round(img.height*ts);
        t.getContext('2d').drawImage(img,0,0,t.width,t.height);
        try{ draft.thumb=t.toDataURL('image/jpeg',0.72); }catch(e){ draft.thumb=null; }

        draft.imgpos=50; draft.imageTouched=true;
        renderUploader(); syncPreview();
      };
      img.onerror=()=>toast('That image could not be read');
      img.src=r.result;
    };
    r.readAsDataURL(file);
  }

  function renderUploader(){
    const up=document.getElementById('uploader');
    if(!draft.image){
      up.innerHTML=`<div class="drop" id="drop">
          <span class="di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 16l5-5 4 4 3-3 4 4M4 20h16V4H4z"/></svg></span>
          <h5>Drop an image here or <span class="browse">browse</span></h5>
          <p>PNG or JPG · a wide image works best for the banner</p>
        </div>
        <input type="file" id="fileInput" accept="image/png,image/jpeg,image/webp" hidden>`;
      wireDrop();
      return;
    }
    up.innerHTML=`<div class="imgframe" id="imgframe">
        <img id="frameImg" src="${draft.image}" style="object-position:center ${draft.imgpos}%" draggable="false">
        <div class="hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7-7 7 7"/><path d="M5 12l7 7 7-7"/></svg>Drag up or down to reposition</div>
      </div>
      <div class="img-actions">
        <button class="btn sm ghost" id="replaceImg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 16l5-5 4 4 3-3 4 4M4 20h16V4H4z"/></svg>Replace</button>
        <button class="btn sm danger" id="removeImg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 7h14M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>Remove</button>
      </div>
      <input type="file" id="fileInput" accept="image/png,image/jpeg,image/webp" hidden>`;
    wireFrame();
  }

  function wireDrop(){
    const fi=document.getElementById('fileInput'), dp=document.getElementById('drop');
    dp.addEventListener('click',()=>fi.click());
    dp.addEventListener('dragover',e=>{e.preventDefault();dp.classList.add('drag');});
    dp.addEventListener('dragleave',()=>dp.classList.remove('drag'));
    dp.addEventListener('drop',e=>{e.preventDefault();dp.classList.remove('drag');if(e.dataTransfer.files[0])loadImage(e.dataTransfer.files[0]);});
    fi.addEventListener('change',e=>{if(e.target.files[0])loadImage(e.target.files[0]);});
  }

  function wireFrame(){
    const fi=document.getElementById('fileInput');
    document.getElementById('replaceImg').onclick=()=>fi.click();
    fi.addEventListener('change',e=>{if(e.target.files[0])loadImage(e.target.files[0]);});
    document.getElementById('removeImg').onclick=()=>{draft.image=null;draft.thumb=null;draft.imgpos=50;draft.imageTouched=true;renderUploader();syncPreview();};

    const frame=document.getElementById('imgframe'), img=document.getElementById('frameImg');
    let dragging=false, startY=0, startPos=draft.imgpos;
    const START=e=>{dragging=true;startY=(e.touches?e.touches[0].clientY:e.clientY);startPos=draft.imgpos;frame.classList.add('dragging');};
    const MOVE=e=>{
      if(!dragging)return;
      const y=(e.touches?e.touches[0].clientY:e.clientY);
      const dy=y-startY;
      /* map drag distance to 0-100%; frame height ~190px, dragging full height = full range */
      let pos=startPos - (dy/190)*100;
      pos=Math.max(0,Math.min(100,pos));
      draft.imgpos=pos;
      img.style.objectPosition='center '+pos+'%';
      const pvimg=document.querySelector('#pvbg img'); if(pvimg) pvimg.style.objectPosition='center '+pos+'%';
      e.preventDefault();
    };
    const END=()=>{dragging=false;frame.classList.remove('dragging');};
    frame.addEventListener('mousedown',START);
    window.addEventListener('mousemove',MOVE);
    window.addEventListener('mouseup',END);
    frame.addEventListener('touchstart',START,{passive:false});
    frame.addEventListener('touchmove',MOVE,{passive:false});
    frame.addEventListener('touchend',END);
  }

  /* ===================== SAVE ===================== */
  function saveBulletin(){
    if(!draft.title.trim()||!draft.body.trim()) return;
    const lc=checkLink(draft.link);
    if(draft.link.trim() && !lc.ok){ toast('Fix the link before publishing'); return; }

    const btn=document.getElementById('saveBtn');
    const label=btn.textContent;
    btn.disabled=true; btn.textContent='Saving…';

    const payload={
      type:draft.type,
      title:draft.title.trim(),
      body:draft.body.trim(),
      link:lc.url||'',
      imgpos:draft.imgpos
    };
    if(editingId) payload.id=editingId;
    /* Only send `image` when it is part of this edit. Leaving the key out
       tells the server to keep what it has; sending null is how the Remove
       button clears it. */
    if(!editingId || draft.imageTouched){
      payload.image=draft.image;
      payload.thumb=draft.thumb||null;
    }

    UCP.post('bulletin-save.php',payload).then(function(res){
      btn.disabled=false; btn.textContent=label;
      const d=res.data||{};
      if(!d.ok){ toast(d.error||'That did not work'); return; }
      toast(d.message);
      /* The byline is set server-side from the session and is never touched
         on an edit, so a post keeps the name of whoever wrote it. */
      if(!editingId) curPage=1;
      closeEditor();
      loadPage();
    }).catch(function(){
      btn.disabled=false; btn.textContent=label;
      toast('Could not reach the server');
    });
  }


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
     BOOT — who is this, and may they be here?

     Every endpoint behind this page checks the rank itself; this is so the
     page doesn't render a management console to someone who would only be
     refused by every button on it.
     ===================================================================== */
  function lockOut(){
    document.querySelector('.content').innerHTML=`
      <div class="empty" style="margin:60px auto;max-width:520px">
        <span class="ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></span>
        <h4>Management only</h4>
        <p>The County Bulletin is managed by Management and Founders. If you think you should have access, speak to a Founder.</p>
        <a class="btn primary" href="/dashboard">Back to dashboard</a>
      </div>`;
  }

  UCP.get('session.php').then(function(d){
    if(!d || d.authenticated!==true){
      window.location.replace('/login?return='+encodeURIComponent('/dashboard/bulletin'));
      return;
    }
    UCP_NAME = d.name || '';
    const byLabel=document.getElementById('fByLabel');
    if(byLabel) byLabel.textContent = UCP_NAME;
    const an=document.getElementById('acctName'), ar=document.getElementById('acctRole');
    if(an) an.textContent = UCP_NAME;
    if(ar) ar.textContent = d.role || 'Member';
    /* Keep it for the next page load — this is what stops the flicker. */
    if(window.UCP && UCP.rememberMe) UCP.rememberMe(d);

    /* 8 = Management, 9 = Founder — the same line the server draws. */
    if((d.rank|0) < 8){ lockOut(); return; }

    const was = [IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join();
    IS_MANAGER=true; IS_FOUNDER=(d.rank|0) >= 9; IS_ADMINISTRATOR=true;
    MY_RANK = d.rank | 0; MY_TEAMS = d.teams || [];
    /* Only redraw if the seed above was wrong — redrawing identical HTML is
       what the eye reads as a flash. */
    if([IS_ADMINISTRATOR, IS_MANAGER, IS_FOUNDER, MY_RANK, MY_TEAMS.join('|')].join() !== was) renderSidebar(SIDEBAR);

    loadPage(1);
  }).catch(function(){ toast('Could not reach the server'); });

</script>
</body>
</html>
