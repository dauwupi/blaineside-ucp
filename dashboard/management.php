<?php
/**
 * Management.
 *
 * The shell — backdrop, sidebar, top bar, credit box — comes from
 * partials/shell-top.php. Nothing about it is repeated here.
 */
$PAGE_TITLE = 'Management · BlaineSide';
$PAGE_HEADING = 'Management';
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
     MANAGEMENT

     A hub, not a tool. Every tile is a link to a page that already exists
     and already enforces its own gate — so nothing here is a permission,
     and the worst a wrong tile can do is send somebody to a page that
     tells them no.

     It exists because the sidebar was growing a child per tool. Five
     entries under one menu item is already awkward; the sixth would have
     been worse. A tile is cheaper to add than a line in a menu that
     everybody scrolls past.
     ===================================================================== */
  .qhead{display:flex;gap:15px;align-items:flex-start;margin-bottom:22px}
  .qhead .qi{width:42px;height:42px;flex:none;border-radius:12px;display:grid;place-items:center;
    background:rgba(212,146,58,.1);border:1px solid rgba(212,146,58,.26)}
  .qhead .qi svg{width:20px;height:20px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .qhead h1{font-size:23px;font-weight:700;letter-spacing:-.02em}
  .qhead p{font-size:13.5px;color:var(--text-faint);margin-top:4px;max-width:none}

  /* grid-auto-rows:1fr is what keeps the second row the same height as the
     first. Without it every row sizes to its own tallest tile, so one
     two-line description made row one taller than row two. */
  .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;grid-auto-rows:1fr}
  @media (max-width:1240px){ .grid{grid-template-columns:repeat(3,1fr)} }
  @media (max-width:900px){  .grid{grid-template-columns:repeat(2,1fr)} }
  @media (max-width:560px){  .grid{grid-template-columns:1fr} }

  .tile{display:flex;flex-direction:column;background:var(--charcoal-2);border:1px solid var(--border);
    border-radius:12px;padding:15px 16px;cursor:pointer;color:inherit;
    transition:border-color .15s,background .15s}
  .tile:hover{border-color:rgba(226,182,92,.42);background:var(--charcoal-3)}
  .tile:focus-visible{outline:2px solid rgba(226,182,92,.55);outline-offset:2px}
  .tile .top{display:flex;align-items:center;gap:11px}
  .tile .ic{width:30px;height:30px;flex:none;border-radius:9px;display:grid;place-items:center;
    background:var(--charcoal-3);border:1px solid var(--border);transition:background .15s,border-color .15s}
  .tile:hover .ic{background:rgba(226,182,92,.1);border-color:rgba(226,182,92,.3)}
  .tile .ic svg{width:15px;height:15px;stroke:var(--gold);fill:none;stroke-width:1.9}
  .tile h3{font-size:13.5px;font-weight:700;letter-spacing:-.005em}
  .tile .go{margin-left:auto;width:14px;height:14px;stroke:#4a443b;fill:none;stroke-width:2.3;
    transition:transform .15s,stroke .15s}
  .tile:hover .go{stroke:var(--gold);transform:translateX(2px)}
  /* flex:1 pushes the meta line to the bottom of every tile, so the meta
     lines align across a row however long the description runs. */
  .tile p{font-size:12px;color:var(--text-faint);line-height:1.6;margin-top:10px;flex:1}
  /* One chip, and it never wraps.

     This was a hairline rule with two labels either side of a dot. In a
     quarter-width tile "New player applications · Applicants see it" broke
     across two lines and each half landed at a different height, so the
     bottom of every tile looked different. A single short chip cannot do
     that at any width, and the thing it dropped — where the tool applies —
     is already the first clause of the description above it. */
  .tile .aud{margin-top:13px}
  .tile .aud span{display:inline-flex;align-items:center;justify-content:center;
    font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;text-indent:.05em;
    line-height:1;padding:5px 9px;border-radius:6px;white-space:nowrap;
    color:var(--text-dim);border:1px solid var(--border);background:var(--charcoal-3)}
  .tile:hover .aud span{color:var(--text-faint);border-color:var(--charcoal-4)}

  /* ---- search ---- */
  .searchrow{display:flex;align-items:center;gap:11px;margin-bottom:16px;flex-wrap:wrap}
  .sbox{position:relative;flex:1;min-width:240px;max-width:420px}
  .sbox svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:15px;height:15px;
    stroke:var(--text-dim);fill:none;stroke-width:2}
  .sbox input{width:100%;background:var(--charcoal-2);border:1px solid var(--border);border-radius:10px;
    color:var(--parchment);font:inherit;font-size:13px;padding:11px 13px 11px 38px}
  .sbox input:focus{outline:none;border-color:rgba(226,182,92,.45)}
  .sbox input::placeholder{color:var(--text-dim)}
  .scount{font-size:12px;color:var(--text-dim)}

  .card{background:var(--charcoal-2);border:1px solid var(--border-soft);border-radius:14px}
  .card-b{padding:22px}
  .lede{font-size:12.5px;color:var(--text-faint);line-height:1.75}
  .empty{border:1px dashed var(--border);border-radius:12px;padding:30px 18px;text-align:center;
    color:var(--text-dim);font-size:12.5px}

</style>
</head>

HTML;
require __DIR__ . '/../partials/shell-top.php';
?>


      <div class="qhead">
        <span class="qi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/></svg></span>
        <div>
          <h1>Management</h1>
          <p>Everything Management runs, in one place.</p>
        </div>
      </div>

      <div id="body"><div class="empty">Loading…</div></div>
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
      window.location.replace('/login?return=' + encodeURIComponent('/dashboard/management'));
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
     MANAGEMENT

     The tiles are a list here and nowhere else. Adding a tool later is an
     entry in TOOLS — no sidebar edit, no new menu child, nothing to keep
     in step across fourteen pages.

     `min` is the rank the DESTINATION requires, and it decides whether the
     tile is drawn. It is not the gate: every page behind these links asks
     the server for itself, exactly as it did when the sidebar linked
     straight to it. Hiding a tile is politeness, not security.

     Deliberately absent: Administrative Search (it lives under
     Administrators), the Application Panel (under Support Staff) and the
     Founder tools (under Founder). A hub that lists another section's
     tools gives two answers to "where does this live".
     ===================================================================== */
  var ICONS = {
    bulletin:'<path d="M4 5h16v11H8l-4 3z"/><path d="M8 9h8M8 12h5"/>',
    megaphone:'<path d="M4 10v4h4l5 4V6L8 10z"/><path d="M17 8a5 5 0 0 1 0 8"/>',
    question:'<circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 1 1 4 2.8c-.7.3-1 .9-1 1.7v.5"/><path d="M12 17h.01"/>',
    template:'<path d="M8 4h9l3 3v13H8z"/><path d="M4 8v12h9"/><path d="M11 11h5M11 15h5"/>',
    people:'<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>'
  };

  /* `where` is the honest answer to "what does this actually change?" —
     the thing a reader is really asking when they hesitate over a tile.
     `find` is extra search terms that are not in the title or the text:
     what somebody would type looking for this rather than what we call it. */
  var TOOLS = [
    {icon:'bulletin',  min:8, title:'County Bulletin',
     href:'/dashboard/bulletin',
     blurb:'The rolling board on the dashboard. Events, notices and updates, each with an ' +
           'image, a tag and a place in the slot order.',
     where:'Dashboard', who:'Everyone',
     find:'event events notice notices news post posts slide slides carousel board bulletin'},

    {icon:'megaphone', min:8, title:'Announcements',
     href:'/dashboard/announcements',
     blurb:'The banner across the top of every page. One live at a time, and the loudest thing ' +
           'in the UCP — used for downtime and rule changes.',
     where:'Every page', who:'Everyone',
     find:'announce announcement banner alert notice downtime maintenance broadcast message'},

    {icon:'question',  min:8, title:'Question Manager',
     href:'/dashboard/app-questions',
     blurb:'What every applicant is asked. Pinned questions, the random pool, character ' +
           'minimums, and how many are drawn per application.',
     where:'New player applications', who:'Applicants',
     find:'question questions scenario scenarios pinned pool draw minimum apply application applicant applicants prompt'},

    {icon:'template',  min:8, title:'Response Templates',
     href:'/dashboard/app-templates',
     blurb:'The saved replies Support Staff drop into application feedback. Inserted and then ' +
           'edited — never sent on their own.',
     where:'Application feedback', who:'Support Staff',
     find:'template templates reply replies response responses canned saved feedback deny denial pass accept application'},

    {icon:'people',    min:8, title:'Group Management',
     href:'/dashboard/groups',
     blurb:'Ranks and sub-groups. Who is Support Staff, who is an Admin, and who holds ' +
           'Staff Management.',
     where:'Staff accounts', who:'Staff only',
     find:'rank ranks group groups role roles permission permissions promote demote subgroup subgroups staff team member members'}
  ];

  function el(id){ return document.getElementById(id); }

  function tile(t){
    return '<a class="tile" href="' + t.href + '">' +
      '<div class="top"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' +
        (ICONS[t.icon] || '') + '</svg></span>' +
      '<h3>' + escapeHtml(t.title) + '</h3>' +
      '<svg class="go" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg></div>' +
      '<p>' + escapeHtml(t.blurb) + '</p>' +
      '<div class="aud"><span>' + escapeHtml(t.who) + '</span></div></a>';
  }

  var MINE = [], Q = '';

  function matches(t, q){
    if(!q) return true;
    var hay = (t.title + ' ' + t.blurb + ' ' + t.where + ' ' + t.who + ' ' + t.find).toLowerCase();
    /* Every word has to appear somewhere, in any order — so "application
       templates" finds Response Templates, and so does "deny reply". */
    return q.split(/\s+/).every(function(w){ return hay.indexOf(w) > -1; });
  }

  function grid(){
    var hits = MINE.filter(function(t){ return matches(t, Q); });
    if(!hits.length){
      return '<div class="empty">Nothing here matches “' + escapeHtml(Q) + '”.</div>';
    }
    return '<div class="grid">' + hits.map(tile).join('') + '</div>';
  }

  function paintCount(){
    var c = el('scount'); if(!c) return;
    var n = MINE.filter(function(t){ return matches(t, Q); }).length;
    c.textContent = Q ? n + ' of ' + MINE.length + ' shown' : MINE.length + ' tools';
  }

  function render(rank){
    MINE = TOOLS.filter(function(t){ return rank >= t.min; });

    if(!MINE.length){
      /* Somebody who typed the URL. Say who the page is for rather than
         drawing an empty grid that reads as broken. */
      paint('body', '<div class="card"><div class="card-b"><p class="lede">' +
        'The Management panel is for Management and Founders. Everything you can reach is in ' +
        'the menu on the left.</p></div></div>');
      return;
    }

    paint('body',
      '<div class="searchrow"><div class="sbox">' +
        '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>' +
        '<input type="text" id="q" placeholder="Search Management…" autocomplete="off" ' +
        'value="' + escapeHtml(Q) + '"></div>' +
        '<span class="scount" id="scount"></span></div>' +
      '<div id="gridhost">' + grid() + '</div>');

    paintCount();

    var q = el('q');
    if(q) q.addEventListener('input', function(){
      Q = this.value.trim().toLowerCase();
      /* Redraw the grid alone. Re-rendering the whole view would take the
         focus out of the box being typed in. */
      el('gridhost').innerHTML = grid();
      paintCount();
    });
  }

  /* Draw from the cached rank first so the tiles are there on the first
     paint, then confirm. renderNav() in assets/js/ucp.js does the same
     thing for the same reason. */
  (function(){
    var me = window.UCP && UCP.me;
    if(me) render(me.rank | 0);
  })();

  UCP.get('session.php').then(function(d){
    if(d && d.ok) render(d.rank | 0);
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
