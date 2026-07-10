import React, {useState} from 'react';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';

const dockerCommand =
  'docker run --rm -p 8080:80 -v $(pwd):/var/www/html/public:ro -v rdb:/var/lib/redis/ adrianschubek/dir-browser';

const features = [
  {
    number: '01',
    title: 'Read-only by design',
    text: 'Mount your directory read-only. Add password protection when the files are not for everyone.',
    icon: 'lock',
  },
  {
    number: '02',
    title: 'Nginx at full speed',
    text: 'Files are served directly by Nginx for low memory use and excellent performance at any scale.',
    icon: 'bolt',
  },
  {
    number: '03',
    title: 'More than a list',
    text: 'Search, sorting, README rendering, hashes, labels, download counters and a clean JSON API.',
    icon: 'layers',
  },
];

function Arrow({external = false}) {
  return external ? (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M8 16 16.5 7.5M10 7h7v7" />
    </svg>
  ) : (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M5 12h13M13 7l5 5-5 5" />
    </svg>
  );
}

function FeatureIcon({name}) {
  if (name === 'lock') {
    return (
      <svg viewBox="0 0 32 32" aria-hidden="true">
        <rect x="6.5" y="13" width="19" height="14" rx="2" />
        <path d="M11 13V9.5a5 5 0 0 1 10 0V13M16 18v4" />
      </svg>
    );
  }
  if (name === 'bolt') {
    return (
      <svg viewBox="0 0 32 32" aria-hidden="true">
        <path d="m18 3-9 15h7l-2 11 9-16h-7l2-10Z" />
      </svg>
    );
  }
  return (
    <svg viewBox="0 0 32 32" aria-hidden="true">
      <path d="m16 4 11 6-11 6L5 10l11-6Z" />
      <path d="m5 16 11 6 11-6M5 22l11 6 11-6" />
    </svg>
  );
}

function CopyCommand() {
  const [copied, setCopied] = useState(false);

  async function copy() {
    await navigator.clipboard.writeText(dockerCommand);
    setCopied(true);
    window.setTimeout(() => setCopied(false), 1800);
  }

  return (
    <div className="db-command">
      <div className="db-command__topline">
        <span><i /> Terminal</span>
        <button type="button" onClick={copy} aria-label="Copy Docker command">
          {copied ? 'Copied' : 'Copy'}
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="8" y="8" width="11" height="11" rx="1" />
            <path d="M16 8V5H5v11h3" />
          </svg>
        </button>
      </div>
      <code><span>$</span> {dockerCommand}</code>
    </div>
  );
}

function Hero() {
  return (
    <header className="db-hero">
      <div className="db-orbit db-orbit--one" />
      <div className="db-orbit db-orbit--two" />
      <div className="db-shell db-hero__inner">
        <div className="db-eyebrow"><span>v4.0</span> Your files, beautifully indexed</div>
        <h1>Turn any folder into a <em>fast, polished</em> file browser.</h1>
        <p className="db-hero__lead">
          A secure, read-only directory listing with search, previews, metadata and a JSON API—packaged in one tiny Docker image.
        </p>
        <div className="db-actions">
          <Link className="db-button db-button--primary" to="/v4/intro">
            Get started <Arrow />
          </Link>
          <Link className="db-button db-button--ghost" to="https://dir-demo.adriansoftware.de">
            Explore live demo <Arrow external />
          </Link>
        </div>
        <div className="db-proof">
          <span><b>~10MB</b> memory</span>
          <span><b>1 image</b> to deploy</span>
          <span><b>0 writes</b> to your files</span>
        </div>

        <div className="db-browser-wrap">
          <div className="db-browser-glow" />
          <a href="https://dir-demo.adriansoftware.de" target="_blank" rel="noreferrer" className="db-browser">
            <div className="db-browser__bar">
              <div className="db-dots"><i /><i /><i /></div>
              <div className="db-address"><span>↳</span> /shared/releases</div>
              <span className="db-live">● LIVE</span>
            </div>
            <img src="/img/main2.png" alt="Directory Browser showing a clean, searchable file listing" />
          </a>
          <div className="db-float db-float--left"><span>✓</span> Read-only mount</div>
          <div className="db-float db-float--right"><span>↗</span><div><b>NGINX</b><small>direct file serving</small></div></div>
        </div>
      </div>
    </header>
  );
}

function QuickStart() {
  return (
    <section className="db-quick">
      <div className="db-shell db-quick__grid">
        <div>
          <div className="db-kicker">Deploy in sixty seconds</div>
          <h2>One command.<br />Your files are live.</h2>
        </div>
        <div>
          <p>Point the container at any local folder and open <strong>localhost:8080</strong>. No database setup, no build step, no config file required.</p>
          <CopyCommand />
          <Link className="db-text-link" to="/v4/getting-started/installation">Read the quick-start guide <Arrow /></Link>
        </div>
      </div>
    </section>
  );
}

function Features() {
  return (
    <section className="db-features">
      <div className="db-shell">
        <div className="db-section-head">
          <div>
            <div className="db-kicker">Built for the real web</div>
            <h2>Simple on the surface.<br />Serious underneath.</h2>
          </div>
          <p>Everything you need to share a directory without turning it into another infrastructure project.</p>
        </div>
        <div className="db-feature-grid">
          {features.map((feature) => (
            <article className="db-feature" key={feature.number}>
              <div className="db-feature__top"><span>{feature.number}</span><FeatureIcon name={feature.icon} /></div>
              <h3>{feature.title}</h3>
              <p>{feature.text}</p>
            </article>
          ))}
        </div>
        <div className="db-capabilities" aria-label="More capabilities">
          {['Batch downloads', 'Clean URLs', 'Dark mode', 'File hashes', 'Custom themes', 'ARM64 ready', 'Hidden paths', 'JSON API'].map((item) => (
            <span key={item}><i>+</i>{item}</span>
          ))}
        </div>
      </div>
    </section>
  );
}

function Showcase() {
  return (
    <section className="db-showcase">
      <div className="db-shell db-showcase__grid">
        <div className="db-showcase__copy">
          <div className="db-kicker">Made to fit</div>
          <h2>Your directory.<br />Your way.</h2>
          <p>Choose a theme, switch to dark mode, add descriptions and labels, or bring your own CSS and JavaScript. Directory Browser stays out of the way while your content takes the stage.</p>
          <div className="db-mini-list">
            <span><b>10+</b> included themes</span>
            <span><b>100%</b> responsive</span>
          </div>
          <Link className="db-text-link" to="/v4/configuration/themes">Browse configuration <Arrow /></Link>
        </div>
        <div className="db-showcase__visual">
          <div className="db-dark-card">
            <div className="db-dark-card__bar"><span>Dark mode</span><i>ON</i></div>
            <img src="/img/main1.png" alt="Directory Browser in dark mode" loading="lazy" />
          </div>
          <div className="db-palette" aria-hidden="true"><i /><i /><i /><i /><i /></div>
        </div>
      </div>
    </section>
  );
}

function FinalCta() {
  return (
    <section className="db-final">
      <div className="db-shell db-final__inner">
        <div className="db-final__mark">/</div>
        <div>
          <div className="db-kicker">Ready when you are</div>
          <h2>Give your files<br />a better front door.</h2>
        </div>
        <div className="db-final__action">
          <p>Open source, Docker-native and ready to run.</p>
          <Link className="db-button db-button--light" to="/v4/intro">Start browsing <Arrow /></Link>
          <a className="db-github" href="https://github.com/adrianschubek/dir-browser">View source on GitHub ↗</a>
        </div>
      </div>
    </section>
  );
}

const pageStyles = `
  @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap');
  :root { --db-ink:#121512; --db-paper:#f3f1e8; --db-green:#b7f34b; --db-line:rgba(18,21,18,.16); }
  .db-page { background:var(--db-paper); color:var(--db-ink); overflow:hidden; font-family:'Manrope',sans-serif; }
  .db-page * { box-sizing:border-box; }
  .db-page h1,.db-page h2,.db-page h3,.db-page p { margin-top:0; }
  .db-shell { width:min(1180px,calc(100% - 48px)); margin:0 auto; }
  .db-hero { position:relative; padding:104px 0 0; background:var(--db-paper); }
  .db-hero__inner { position:relative; z-index:1; text-align:center; }
  .db-eyebrow,.db-kicker { font:500 12px/1.2 'DM Mono',monospace; letter-spacing:.12em; text-transform:uppercase; }
  .db-eyebrow { display:inline-flex; align-items:center; gap:12px; margin-bottom:28px; }
  .db-eyebrow span { padding:5px 9px; border:1px solid var(--db-ink); border-radius:30px; background:var(--db-green); }
  .db-hero h1 { max-width:1020px; margin:0 auto 26px; font-size:clamp(52px,7vw,96px); line-height:.95; letter-spacing:-.07em; font-weight:700; }
  .db-hero h1 em { position:relative; font-style:normal; white-space:nowrap; }
  .db-hero h1 em:after { content:''; position:absolute; left:0; right:0; bottom:1px; height:10px; z-index:-1; background:var(--db-green); transform:rotate(-1deg); }
  .db-hero__lead { max-width:700px; margin:0 auto 34px; color:#4d524d; font-size:18px; line-height:1.65; }
  .db-actions { display:flex; justify-content:center; gap:12px; flex-wrap:wrap; }
  .db-button { min-height:54px; padding:0 22px; display:inline-flex; align-items:center; justify-content:center; gap:20px; border:1px solid var(--db-ink); color:var(--db-ink); font-size:14px; font-weight:700; text-decoration:none!important; transition:transform .2s ease,box-shadow .2s ease,background .2s ease; }
  .db-button svg,.db-text-link svg { width:18px; fill:none; stroke:currentColor; stroke-width:1.8; }
  .db-button:hover { color:var(--db-ink); transform:translateY(-3px); box-shadow:4px 4px 0 var(--db-ink); }
  .db-button--primary { background:var(--db-green); }
  .db-button--ghost { background:rgba(255,255,255,.45); }
  .db-proof { display:flex; justify-content:center; gap:0; margin:34px 0 64px; color:#686c68; font:400 12px 'DM Mono',monospace; }
  .db-proof span { padding:0 20px; border-right:1px solid var(--db-line); }
  .db-proof span:last-child { border:0; }
  .db-proof b { color:var(--db-ink); }
  .db-browser-wrap { position:relative; width:min(100%,1010px); margin:0 auto -90px; }
  .db-browser-glow { position:absolute; inset:-60px 8% 15%; background:var(--db-green); opacity:.48; filter:blur(80px); border-radius:50%; }
  .db-browser { position:relative; display:block; overflow:hidden; border:1px solid var(--db-ink); background:white; box-shadow:14px 16px 0 rgba(18,21,18,.13); color:inherit; text-decoration:none!important; transform:perspective(1400px) rotateX(1.5deg); transition:transform .4s ease; }
  .db-browser:hover { transform:perspective(1400px) rotateX(0) translateY(-5px); }
  .db-browser__bar { height:48px; padding:0 16px; display:grid; grid-template-columns:1fr auto 1fr; align-items:center; border-bottom:1px solid #d7d7d2; background:#eeeee9; color:#222; }
  .db-dots { display:flex; gap:6px; }.db-dots i { width:8px;height:8px;border:1px solid #777;border-radius:50%; }
  .db-address { min-width:270px; padding:7px 22px; border:1px solid #d1d1cd; border-radius:4px; background:#fff; font:400 11px 'DM Mono',monospace; }
  .db-address span { color:#7d827d; margin-right:8px; }
  .db-live { justify-self:end; font:500 9px 'DM Mono',monospace; color:#4c7040; letter-spacing:.08em; }
  .db-browser img { width:100%; display:block; }
  .db-float { position:absolute; z-index:2; display:flex; align-items:center; gap:10px; padding:11px 14px; border:1px solid var(--db-ink); background:var(--db-paper); box-shadow:4px 4px 0 var(--db-ink); font:500 11px 'DM Mono',monospace; }
  .db-float--left { left:-45px; bottom:20%; transform:rotate(-3deg); }.db-float--left span { color:#4a7900; }
  .db-float--right { right:-44px; top:30%; text-align:left; transform:rotate(3deg); }.db-float--right>span { font-size:20px; }.db-float small { display:block;color:#757975;font-size:8px;margin-top:2px; }
  .db-orbit { position:absolute; border:1px solid rgba(18,21,18,.08); border-radius:50%; pointer-events:none; }
  .db-orbit--one { width:500px;height:500px;left:-260px;top:70px; }.db-orbit--two { width:720px;height:720px;right:-430px;top:-180px; }
  .db-quick { padding:190px 0 100px; background:#171a17; color:#f5f3e9; }
  .db-quick__grid { display:grid; grid-template-columns:.85fr 1.15fr; gap:100px; align-items:start; }
  .db-kicker { margin-bottom:22px; color:#6c716c; }.db-quick .db-kicker,.db-final .db-kicker { color:var(--db-green); }
  .db-page h2 { font-size:clamp(38px,5vw,68px); line-height:1; letter-spacing:-.055em; font-weight:600; }
  .db-quick p { max-width:610px; color:#b7bcb7; line-height:1.7; }
  .db-quick p strong { color:#fff; }
  .db-command { margin:30px 0 24px; border:1px solid #3c413c; background:#0c0e0c; }
  .db-command__topline { padding:10px 13px; display:flex; justify-content:space-between; border-bottom:1px solid #2b2f2b; font:400 10px 'DM Mono',monospace; color:#878d87; text-transform:uppercase;letter-spacing:.08em; }
  .db-command__topline span { display:flex;align-items:center;gap:8px; }.db-command__topline i { width:7px;height:7px;border-radius:50%;background:var(--db-green);box-shadow:0 0 8px var(--db-green); }
  .db-command button { padding:0; display:flex;align-items:center;gap:7px;border:0;background:none;color:#aeb3ae;font:inherit;cursor:pointer; }.db-command button:hover { color:white; }.db-command button svg { width:13px;fill:none;stroke:currentColor;stroke-width:1.5; }
  .db-command code { display:block; padding:22px; overflow:auto; color:#e7e9e7; background:none; font:400 12px/1.7 'DM Mono',monospace; white-space:nowrap; }.db-command code span { color:var(--db-green); }
  .db-text-link { display:inline-flex;align-items:center;gap:12px;color:inherit!important;font-size:13px;font-weight:700;text-decoration:none!important;border-bottom:1px solid currentColor;padding-bottom:4px; }.db-text-link:hover svg { transform:translateX(4px); }.db-text-link svg { transition:transform .2s; }
  .db-features { padding:120px 0; background:var(--db-paper); }
  .db-section-head { display:grid;grid-template-columns:1fr .55fr;gap:80px;align-items:end;margin-bottom:68px; }.db-section-head h2 { margin-bottom:0; }.db-section-head>p { margin-bottom:8px;color:#5d625d;line-height:1.7; }
  .db-feature-grid { display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--db-ink);border-bottom:1px solid var(--db-ink); }
  .db-feature { min-height:350px;padding:30px;border-right:1px solid var(--db-ink);transition:background .25s,transform .25s; }.db-feature:last-child { border:0; }.db-feature:hover { background:var(--db-green);transform:translateY(-6px); }
  .db-feature__top { display:flex;justify-content:space-between;align-items:start;margin-bottom:80px;font:500 11px 'DM Mono',monospace; }.db-feature__top svg { width:36px;height:36px;fill:none;stroke:currentColor;stroke-width:1.4; }
  .db-feature h3 { margin-bottom:14px;font-size:23px;letter-spacing:-.035em; }.db-feature p { margin:0;color:#575c57;font-size:14px;line-height:1.7; }.db-feature:hover p { color:#2f3825; }
  .db-capabilities { display:grid;grid-template-columns:repeat(4,1fr);margin-top:34px;gap:0 20px; }.db-capabilities span { padding:14px 0;border-bottom:1px solid var(--db-line);font:500 12px 'DM Mono',monospace; }.db-capabilities i { margin-right:12px;color:#669900;font-style:normal;font-size:16px; }
  .db-showcase { padding:120px 0;background:#dfe3d7;border-top:1px solid var(--db-ink); }
  .db-showcase__grid { display:grid;grid-template-columns:.65fr 1.35fr;gap:100px;align-items:center; }.db-showcase__copy>p { color:#596058;line-height:1.75; }.db-mini-list { display:flex;gap:32px;margin:32px 0; }.db-mini-list span { font:400 11px 'DM Mono',monospace;color:#666c65; }.db-mini-list b { display:block;color:var(--db-ink);font:700 24px 'Manrope',sans-serif;margin-bottom:2px; }
  .db-showcase__visual { position:relative; }.db-dark-card { border:1px solid #0a0d0a;background:#111511;box-shadow:16px 16px 0 rgba(18,21,18,.18);transform:rotate(1.5deg);overflow:hidden; }.db-dark-card__bar { height:43px;padding:0 15px;display:flex;align-items:center;justify-content:space-between;color:#ccd1cc;border-bottom:1px solid #343934;font:400 10px 'DM Mono',monospace; }.db-dark-card__bar i { color:var(--db-green);font-style:normal; }.db-dark-card img { width:100%;display:block; }.db-palette { position:absolute;left:-35px;bottom:-25px;padding:11px;display:flex;gap:6px;border:1px solid var(--db-ink);background:var(--db-paper);box-shadow:4px 4px 0 var(--db-ink); }.db-palette i { width:17px;height:17px;border-radius:50%;background:#b7f34b; }.db-palette i:nth-child(2){background:#ff8b6b}.db-palette i:nth-child(3){background:#ead85b}.db-palette i:nth-child(4){background:#8cb9f8}.db-palette i:nth-child(5){background:#262a26}
  .db-final { padding:110px 0;background:var(--db-green);color:var(--db-ink); }.db-final__inner { display:grid;grid-template-columns:100px 1fr .55fr;gap:50px;align-items:center; }.db-final__mark { width:76px;height:76px;display:grid;place-items:center;border:2px solid var(--db-ink);border-radius:50%;font:500 42px 'DM Mono',monospace; }.db-final h2 { margin:0; }.db-final__action p { font-size:14px; }.db-button--light { background:var(--db-paper);width:100%; }.db-github { display:block;text-align:center;margin-top:17px;color:var(--db-ink)!important;font:500 11px 'DM Mono',monospace;text-decoration:none!important; }
  html[data-theme='dark'] .db-page { --db-paper:#edece4;--db-ink:#121512;color:var(--db-ink); }
  @keyframes db-rise { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
  .db-eyebrow,.db-hero h1,.db-hero__lead,.db-actions,.db-proof,.db-browser-wrap { animation:db-rise .7s both cubic-bezier(.2,.8,.2,1); }.db-hero h1{animation-delay:.08s}.db-hero__lead{animation-delay:.16s}.db-actions{animation-delay:.24s}.db-proof{animation-delay:.3s}.db-browser-wrap{animation-delay:.38s}
  @media (prefers-reduced-motion:reduce) { .db-page * { animation:none!important;transition:none!important; } }
  @media (max-width:900px) { .db-hero{padding-top:74px}.db-quick__grid,.db-section-head,.db-showcase__grid{grid-template-columns:1fr;gap:40px}.db-feature-grid{grid-template-columns:1fr}.db-feature{min-height:0;border-right:0;border-bottom:1px solid var(--db-ink)}.db-feature:last-child{border-bottom:0}.db-feature__top{margin-bottom:45px}.db-capabilities{grid-template-columns:repeat(2,1fr)}.db-final__inner{grid-template-columns:70px 1fr}.db-final__action{grid-column:2}.db-float{display:none}.db-showcase__copy{max-width:650px}.db-showcase__visual{width:95%;margin-left:auto} }
  @media (max-width:600px) { .db-shell{width:min(100% - 28px,1180px)}.db-hero{padding-top:56px}.db-hero h1{font-size:47px}.db-hero h1 em{white-space:normal}.db-hero h1 em:after{display:none}.db-hero__lead{font-size:16px}.db-actions{flex-direction:column}.db-button{width:100%}.db-proof{display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:left}.db-proof span{padding:0;border:0}.db-proof span:last-child{grid-column:span 2}.db-browser-wrap{margin-bottom:-50px}.db-browser__bar{grid-template-columns:auto 1fr}.db-address{min-width:0;margin-left:12px;overflow:hidden;white-space:nowrap}.db-live{display:none}.db-quick{padding:120px 0 75px}.db-features,.db-showcase{padding:80px 0}.db-capabilities{grid-template-columns:1fr 1fr}.db-showcase__grid{gap:55px}.db-palette{left:-8px}.db-final{padding:75px 0}.db-final__inner{grid-template-columns:1fr;gap:24px}.db-final__mark{width:56px;height:56px;font-size:28px}.db-final__action{grid-column:auto}.db-page h2{font-size:42px} }
`;

export default function Home() {
  const {siteConfig} = useDocusaurusContext();

  return (
    <Layout title={siteConfig.title} description="A fast, secure and beautiful web directory browser in one Docker image">
      <style>{pageStyles}</style>
      <main className="db-page">
        <Hero />
        <QuickStart />
        <Features />
        <Showcase />
        <FinalCta />
      </main>
    </Layout>
  );
}
