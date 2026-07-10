  <style data-turbo-eval="false">
    :root {
      color-scheme: light;
      --canvas: #f3f3f2;
      --surface: #ffffff;
      --surface-raised: #ffffff;
      --ink: #191919;
      --muted: #666666;
      --faint: #8b8b8b;
      --line: #dedede;
      --line-strong: #bdbdbd;
      --accent: #242424;
      --accent-hover: #000000;
      --accent-soft: #eeeeed;
      --on-accent: #ffffff;
      --folder: #3e3e3e;
      --danger: #b33b31;
      --danger-soft: #fff0ed;
      --shadow: 0 1px 2px rgba(0, 0, 0, .035);
      --shadow-small: 0 2px 8px rgba(0, 0, 0, .06);
      --radius: 5px;
      --radius-small: 3px;
      font-family: "Avenir Next", Avenir, "Segoe UI", sans-serif;
    }

    [data-theme="dark"] {
      color-scheme: dark;
      --canvas: #101010;
      --surface: #181818;
      --surface-raised: #202020;
      --ink: #f1f1f1;
      --muted: #aaaaaa;
      --faint: #777777;
      --line: #303030;
      --line-strong: #484848;
      --accent: #eeeeee;
      --accent-hover: #ffffff;
      --accent-soft: #292929;
      --on-accent: #111111;
      --folder: #c5c5c5;
      --danger: #ff8b81;
      --danger-soft: #3d2222;
      --shadow: 0 1px 2px rgba(0, 0, 0, .18);
      --shadow-small: 0 3px 10px rgba(0, 0, 0, .22);
    }

    *, *::before, *::after { box-sizing: border-box; }
    html { min-height: 100%; background: var(--canvas); }
    body {
      min-height: 100vh;
      margin: 0;
      overflow-y: scroll;
      color: var(--ink);
      background: var(--canvas);
      font-size: 14px;
      line-height: 1.45;
      letter-spacing: .005em;
    }
    button, input, select { font: inherit; }
    button, a { -webkit-tap-highlight-color: transparent; }
    a { color: inherit; text-decoration: none; }
    a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
      outline: 2px solid var(--ink);
      outline-offset: 2px;
    }
    svg { vertical-align: middle; }

    /* Small, purpose-built layout primitives used by the server templates. */
    .d-flex { display: flex; }
    .flex-column { flex-direction: column; }
    .min-vh-100 { min-height: 100vh; }
    .d-none { display: none !important; }
    .d-block { display: block !important; }
    .position-sticky { position: sticky; }
    .position-fixed { position: fixed; }
    .bottom-0 { bottom: 0; }
    .end-0 { right: 0; }
    .m-auto { margin: auto; }
    .mt-auto { margin-top: auto; }
    .mt-1 { margin-top: .25rem; }
    .mt-2 { margin-top: .5rem; }
    .mb-0 { margin-bottom: 0; }
    .mb-2 { margin-bottom: .5rem; }
    .mb-3 { margin-bottom: 1rem; }
    .ms-1 { margin-left: .25rem; }
    .ms-auto { margin-left: auto; }
    .me-auto { margin-right: auto; }
    .p-2 { padding: .4rem; }
    .p-3 { padding: .8rem; }
    .px-1 { padding-inline: .25rem; }
    .px-3 { padding-inline: .8rem; }
    .py-2 { padding-block: .4rem; }
    .py-3 { padding-block: .8rem; }
    .pt-2 { padding-top: .5rem; }
    .pt-3 { padding-top: .8rem; }
    .pb-3 { padding-bottom: .8rem; }
    .pe-0 { padding-right: 0; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .text-muted, .text-body-secondary, .text-body-tertiary { color: var(--muted) !important; }
    .text-danger { color: var(--danger) !important; }
    .text-primary, .link-primary { color: var(--accent) !important; }
    .fw-semibold { font-weight: 650; }
    .small { font-size: .84rem; }
    .fs-5 { font-size: 1.15rem; }
    .rounded, .rounded-1, .rounded-3 { border-radius: var(--radius-small); }
    .border-top { border-top: 1px solid var(--line); }
    .w-100 { width: 100%; }
    .img-fluid { display: block; max-width: 100%; height: auto; margin-inline: auto; }
    .justify-content-center { justify-content: center; }

    .container { width: min(1120px, calc(100% - 24px)); margin-inline: auto; }
    .row { display: flex; align-items: center; min-width: 0; }
    .col { flex: 1 1 0; min-width: 0; }
    .col-auto { flex: 0 0 auto; }
    .col-2 { flex: 0 0 18%; }

    .card {
      color: var(--ink);
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }
    .card-body { padding: 1.1rem; }
    .card-footer { padding: .65rem 1.1rem; border-top: 1px solid var(--line); color: var(--muted); }
    .card-footer a:hover { color: var(--accent); }
    .alert-heading { margin: 0 0 .45rem; font-size: 1.2rem; font-weight: 650; line-height: 1.25; }
    .alert { padding: .5rem .65rem; border-radius: var(--radius-small); }
    .alert-danger { color: var(--danger); background: var(--danger-soft); border: 1px solid color-mix(in srgb, var(--danger) 26%, transparent); }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .35rem;
      min-height: 36px;
      padding: .45rem .7rem;
      color: var(--ink);
      background: var(--surface-raised);
      border: 1px solid var(--line-strong);
      border-radius: var(--radius-small);
      cursor: pointer;
      transition: transform .18s ease, border-color .18s ease, background .18s ease, color .18s ease, box-shadow .18s ease;
    }
    .btn:hover { border-color: var(--ink); background: var(--accent-soft); }
    .btn:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-primary { color: var(--on-accent); background: var(--accent); border-color: var(--accent); font-weight: 650; }
    .btn-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
    .btn-secondary { background: var(--surface-raised); }
    .btn-sm { width: 34px; height: 34px; min-height: 34px; padding: 0; border-color: transparent; background: transparent; color: var(--muted); }
    .btn-sm:hover { color: var(--ink); background: var(--accent-soft); box-shadow: none; }
    .btn-close {
      width: 32px; height: 32px; padding: 0; flex: 0 0 auto;
      border: 0; border-radius: var(--radius-small); background: transparent; color: var(--muted); cursor: pointer;
    }
    .btn-close::before { content: "×"; display: block; font-size: 23px; font-weight: 300; line-height: 30px; }
    .btn-close:hover { color: var(--ink); background: var(--accent-soft); }

    .form-control, .form-select {
      width: 100%; min-height: 38px; padding: .48rem .65rem;
      color: var(--ink); background: var(--surface-raised); border: 1px solid var(--line-strong); border-radius: var(--radius-small);
      transition: border-color .18s ease, box-shadow .18s ease;
    }
    .form-control:focus, .form-select:focus { border-color: var(--ink); box-shadow: 0 0 0 2px color-mix(in srgb, var(--ink) 10%, transparent); outline: 0; }
    .input-group { display: flex; width: 100%; }
    .input-group > :first-child { border-radius: var(--radius-small) 0 0 var(--radius-small); }
    .input-group > :last-child { border-radius: 0 var(--radius-small) var(--radius-small) 0; margin-left: -1px; }
    .form-check-input { width: 17px; height: 17px; accent-color: var(--accent); }

    .icon { width: 21px !important; height: 21px !important; text-align: center; flex: 0 0 auto; }
    .icon::before { font-size: 18px !important; width: 18px !important; }
    .dir-icon-placeholder { color: var(--folder); }
    .file-icon-placeholder { color: var(--muted); }

    #path {
      display: flex; align-items: center; gap: .2rem; min-height: 34px; overflow-x: auto;
      font-size: .95rem; font-weight: 600; line-height: 1.25; white-space: nowrap; scrollbar-width: none;
    }
    #path::-webkit-scrollbar { display: none; }
    #path > a { padding: .25rem 0; color: var(--muted); }
    #path > a:hover { color: var(--accent); }
    #path > a:last-child { color: var(--ink); }

    .position-sticky.card {
      top: 8px !important; z-index: 5 !important;
      border-radius: var(--radius) var(--radius) 0 0 !important;
      box-shadow: var(--shadow);
    }
    .workspace-header, .workspace-list { width: 100%; }
    #search-container { border-top: 1px solid var(--line); padding: .55rem 0; }
    #sort { min-height: 36px; border-top: 1px solid var(--line); color: var(--faint) !important; font-size: .68rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
    #sort > a { transition: color .18s ease; }
    #sort > a:hover { color: var(--accent); }
    #sort > a > svg { width: 14px !important; height: 14px !important; margin-left: .3rem; opacity: 0; transition: opacity .18s ease; }
    #sort > a:hover > svg { opacity: 1; }

    #filetree, #resultstree {
      overflow: hidden; border-top: 0 !important; border-radius: 0 0 var(--radius) var(--radius) !important;
      box-shadow: var(--shadow); animation: tree-in .22s ease-out both;
    }
    @keyframes tree-in { from { opacity: 0; } to { opacity: 1; } }
    #filetree > a, #resultstree > a { position: relative; min-height: 48px; padding-inline: .8rem; border-bottom: 1px solid var(--line); transition: background .12s ease; }
    #filetree > a:last-child, #resultstree > a:last-child { border-bottom: 0; }
    #filetree > a:hover, #resultstree > a:hover { background: var(--accent-soft); }
    #filetree > a::before, #resultstree > a::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: var(--ink); transform: scaleY(0); transition: transform .12s ease; }
    #filetree > a:hover::before, #resultstree > a:hover::before { transform: scaleY(1); }
    #filetree > .db-file > .col-auto:not(.multiselect) + .col { margin-left: .55rem; }
    #filetree > .db-file > .multiselect + .col-auto { margin-left: .55rem; }
    #resultstree > .db-file > .row { width: 100%; gap: .55rem; }
    .db-file > .col:not(.col-auto) { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .db-file .text-body-secondary { margin-left: .45rem; font-size: .8rem; }
    a[data-file-selected="1"] { background: var(--accent-soft) !important; }
    .badge { display: inline-flex; margin-left: .3rem; padding: .12rem .34rem; border-radius: 2px; background: var(--line); color: var(--ink); font-size: .65rem; font-weight: 700; }

    .pagination { display: flex; gap: 1px; margin: .7rem 0; padding: 0; overflow: hidden; list-style: none; border: 1px solid var(--line); border-radius: var(--radius); background: var(--line); box-shadow: var(--shadow); }
    .page-link { display: grid; min-width: 32px; height: 32px; place-items: center; padding: 0 .5rem; border-radius: 0; color: var(--muted); background: var(--surface); transition: background .12s ease, color .12s ease; }
    .page-link:hover { color: var(--accent); background: var(--accent-soft); }
    .page-link.active { color: var(--on-accent); background: var(--accent); }
    .page-link.disabled { pointer-events: none; opacity: .32; }

    #readme { padding: clamp(1rem, 2.5vw, 2rem) !important; }
    #readme { overflow-wrap: anywhere; }
    #readme > :first-child { margin-top: 0; }
    #readme > :last-child { margin-bottom: 0; }
    #readme h1, #readme h2, #readme h3, #readme h4 {
      margin: 1.5em 0 .55em; color: var(--ink); font-family: inherit; line-height: 1.25; letter-spacing: -.015em;
    }
    #readme h1 { padding-bottom: .4rem; border-bottom: 1px solid var(--line); font-size: clamp(1.55rem, 3vw, 2.1rem); }
    #readme h2 { font-size: 1.35rem; }
    #readme h3 { font-size: 1.12rem; }
    #readme p, #readme ul, #readme ol { max-width: 76ch; }
    #readme a { color: var(--accent); text-decoration: underline; text-decoration-color: color-mix(in srgb, var(--accent) 35%, transparent); text-underline-offset: .18em; }
    #readme img { max-width: 100%; border-radius: var(--radius-small); }
    #readme blockquote { margin-inline: 0; padding: .15rem 1rem; color: var(--muted); border-left: 3px solid var(--accent); }
    #readme table { display: block; max-width: 100%; overflow-x: auto; border-collapse: collapse; }
    #readme th, #readme td { padding: .42rem .55rem; border: 1px solid var(--line); }
    #readme code { padding: .12rem .3rem; border-radius: 2px; color: var(--ink); background: var(--canvas); font-family: "SFMono-Regular", Consolas, monospace; font-size: .88em; }
    #readme pre code { padding: 0; background: transparent; }
    #footer { color: var(--faint); font-size: .78rem; letter-spacing: .03em; }
    #footer a:hover { color: var(--accent); }

    .modal { display: none; position: fixed; inset: 0; z-index: 1000; padding: 18px; overflow-y: auto; background: rgba(0, 0, 0, .72); opacity: 0; transition: opacity .15s ease; }
    .modal.show { display: block; opacity: 1; }
    .modal-dialog { display: flex; width: min(900px, 100%); min-height: calc(100% - 36px); margin: 18px auto; align-items: center; }
    .modal-content { width: 100%; max-height: calc(100vh - 54px); overflow: hidden; color: var(--ink); background: var(--surface); border: 1px solid var(--line-strong); border-radius: var(--radius); box-shadow: 0 16px 50px rgba(0, 0, 0, .35); animation: modal-in .18s ease-out both; }
    @keyframes modal-in { from { transform: translateY(6px); opacity: 0; } to { transform: none; opacity: 1; } }
    .modal-header, .modal-footer { display: flex; align-items: center; gap: .45rem; padding: .7rem .85rem; border-bottom: 1px solid var(--line); }
    .modal-header { justify-content: space-between; }
    .modal-title { margin: 0; font-family: inherit; font-weight: 650; }
    .modal-body { padding: .85rem; overflow: auto; max-height: calc(100vh - 180px); }
    .modal-footer { justify-content: flex-end; border-top: 1px solid var(--line); border-bottom: 0; }
    .modal-open { overflow: hidden; }
    #file-popup-meta { display: grid; grid-template-columns: minmax(110px, 1fr) 2fr; gap: .45rem 1rem; }
    #file-popup-meta dt, #file-popup-meta dd { margin: 0; padding: .25rem 0; }
    iframe, video { border: 1px solid var(--line); background: var(--canvas); }
    pre { padding: .75rem; overflow: auto; color: var(--ink); background: var(--canvas); border: 1px solid var(--line); border-radius: var(--radius-small); font: .82rem/1.5 "SFMono-Regular", Consolas, monospace; }

    .toast-container { position: fixed; right: 14px; bottom: 14px; z-index: 1100; width: min(360px, calc(100% - 28px)); pointer-events: none; }
    .toast { display: none; overflow: hidden; color: var(--ink); background: var(--surface-raised); border: 1px solid var(--line-strong); border-radius: var(--radius); box-shadow: var(--shadow-small); pointer-events: auto; }
    .toast.show { display: block; animation: toast-in .18s ease-out both; }
    @keyframes toast-in { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: none; } }
    .toast-header { display: flex; align-items: center; gap: .4rem; padding: .55rem .65rem; border-bottom: 1px solid var(--line); }
    .toast-body { padding: .65rem; color: var(--muted); }

    @media (min-width: 769px) {
      .d-md-inline, .d-md-inline-block { display: inline-block !important; }
      .d-md-block { display: block !important; }
    }
    @media (max-width: 768px) {
      .d-md-inline, .d-md-inline-block, .d-md-block { display: none !important; }
      .container { width: min(100% - 12px, 1120px); }
      .col-2 { flex-basis: 0; width: 0; padding: 0; }
      .position-sticky.card { top: 6px !important; }
      .db-file { min-height: 46px !important; }
      .modal { padding: 0; }
      .modal-dialog { min-height: 100%; margin: 0; align-items: flex-end; }
      .modal-content { max-height: 94vh; border-radius: var(--radius) var(--radius) 0 0; }
      .modal-body { max-height: calc(94vh - 190px); }
      .modal-footer { flex-wrap: wrap; }
      .modal-footer .btn { flex: 1 1 auto; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; transition-duration: .01ms !important; }
    }
    $[ifeq env:TRANSITION true]$
    html[data-turbo-visit-direction="forward"]::view-transition-old(sidebar):only-child { animation-duration: .1s; }
    $[end]$
  </style>
