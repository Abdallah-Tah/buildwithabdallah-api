<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Build With Abdallah Central API is a secure integration layer for messaging, billing, webhooks, and application events.">
    <meta name="theme-color" content="#07142d">
    <title>Build With Abdallah — Central API</title>
    <link rel="icon" href="{{ asset('images/bwa-logo.jpeg') }}" type="image/jpeg">
    <style>
        :root { color-scheme: dark; --ink:#edf5ff; --muted:#9fb2cd; --line:rgba(144,180,229,.17); --panel:rgba(12,30,61,.73); --blue:#3988ff; --cyan:#20d7d0; --navy:#061227; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; min-width:320px; color:var(--ink); background:var(--navy); font:16px/1.65 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; overflow-x:hidden; }
        body::before { content:""; position:fixed; inset:0; pointer-events:none; background:radial-gradient(circle at 14% 9%, rgba(37,113,255,.22), transparent 31rem), radial-gradient(circle at 86% 31%, rgba(24,209,197,.12), transparent 28rem), linear-gradient(130deg, #07152e 0%, #040d1d 58%, #07182a 100%); z-index:-2; }
        body::after { content:""; position:fixed; inset:0; pointer-events:none; opacity:.22; background-image:linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px); background-size:64px 64px; mask-image:linear-gradient(to bottom,black,transparent 82%); z-index:-1; }
        a { color:inherit; }
        .shell { width:min(1180px, calc(100% - 40px)); margin-inline:auto; }
        nav { height:82px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--line); }
        .brand { display:flex; align-items:center; gap:12px; text-decoration:none; font-weight:760; letter-spacing:-.02em; }
        .brand img { width:43px; height:43px; border-radius:13px; background:white; padding:5px; box-shadow:0 12px 30px rgba(12,102,255,.2); }
        .brand small { display:block; color:var(--muted); font-size:11px; font-weight:650; letter-spacing:.16em; text-transform:uppercase; }
        .nav-links { display:flex; align-items:center; gap:26px; color:#b8c7dc; font-size:14px; }
        .nav-links a { text-decoration:none; }
        .nav-links a:hover { color:white; }
        .status { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid rgba(52,211,153,.25); border-radius:999px; color:#baf6dc; background:rgba(21,128,94,.12); }
        .dot { width:8px; height:8px; border-radius:50%; background:#34d399; box-shadow:0 0 0 5px rgba(52,211,153,.1); }
        .brand-banner { margin-top:46px; overflow:hidden; border:1px solid rgba(144,180,229,.24); border-radius:24px; background:#fff; box-shadow:0 32px 80px rgba(0,0,0,.28); }
        .brand-banner img { display:block; width:100%; height:auto; aspect-ratio:3/1; object-fit:cover; }
        .hero { min-height:610px; padding:68px 0 76px; display:grid; grid-template-columns:1.08fr .92fr; gap:72px; align-items:center; }
        .eyebrow { display:inline-flex; gap:10px; align-items:center; color:#97eae5; font-size:13px; font-weight:750; letter-spacing:.13em; text-transform:uppercase; }
        .eyebrow::before { content:""; width:29px; height:1px; background:var(--cyan); }
        h1 { margin:22px 0; max-width:730px; font-size:clamp(48px,6.5vw,82px); line-height:1.01; letter-spacing:-.058em; }
        .gradient { color:transparent; background:linear-gradient(100deg,#fff 3%,#70a9ff 49%,#45e1d8); -webkit-background-clip:text; background-clip:text; }
        .lead { max-width:620px; color:#afbed2; font-size:19px; }
        .hero-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:34px; }
        .button { display:inline-flex; align-items:center; justify-content:center; gap:9px; min-height:48px; padding:0 18px; border:1px solid var(--line); border-radius:12px; color:#dce9fa; background:rgba(255,255,255,.035); text-decoration:none; font-weight:700; font-size:14px; transition:.2s ease; }
        .button.primary { color:#031126; border-color:transparent; background:linear-gradient(110deg,#66a7ff,#36ddd3); box-shadow:0 13px 34px rgba(37,134,255,.2); }
        .button:hover { transform:translateY(-2px); border-color:rgba(88,163,255,.5); }
        .terminal { position:relative; padding:19px; border:1px solid var(--line); border-radius:24px; background:linear-gradient(145deg,rgba(14,37,73,.92),rgba(5,17,38,.94)); box-shadow:0 40px 90px rgba(0,0,0,.35); }
        .terminal::before { content:""; position:absolute; inset:-1px; border-radius:inherit; padding:1px; background:linear-gradient(145deg,rgba(91,165,255,.5),transparent 45%,rgba(32,215,208,.24)); mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0); mask-composite:exclude; pointer-events:none; }
        .terminal-head { display:flex; align-items:center; justify-content:space-between; padding:3px 3px 17px; color:#7e93af; font:12px ui-monospace,SFMono-Regular,Menlo,monospace; }
        .lights { display:flex; gap:7px; }.lights i { width:9px; height:9px; border-radius:50%; background:#25405f; }.lights i:nth-child(2){background:#246a98}.lights i:nth-child(3){background:#24a49b}
        .flow { display:grid; gap:11px; }
        .node { display:flex; gap:14px; align-items:center; padding:15px; border:1px solid rgba(134,171,221,.14); border-radius:14px; background:rgba(255,255,255,.027); }
        .node strong { display:block; font-size:14px; }.node span { color:#879ab5; font-size:12px; }
        .node-icon { flex:0 0 37px; height:37px; display:grid; place-items:center; border-radius:10px; color:#8ec2ff; background:rgba(57,136,255,.12); font-weight:800; }
        .connector { height:22px; margin-left:33px; border-left:1px dashed #375a83; }
        .provider-row { display:grid; grid-template-columns:1fr 1fr; gap:11px; }
        .provider-row .node { min-width:0; }
        section { padding:94px 0; }
        .section-kicker { color:var(--cyan); font-size:12px; font-weight:800; letter-spacing:.15em; text-transform:uppercase; }
        h2 { margin:10px 0 14px; max-width:760px; font-size:clamp(34px,4vw,54px); line-height:1.08; letter-spacing:-.045em; }
        .section-copy { max-width:690px; color:var(--muted); font-size:18px; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:46px; }
        .card { min-height:218px; padding:25px; border:1px solid var(--line); border-radius:19px; background:var(--panel); box-shadow:inset 0 1px rgba(255,255,255,.025); }
        .card .num { color:#62a4ff; font:700 12px ui-monospace,SFMono-Regular,Menlo,monospace; }
        .card h3 { margin:35px 0 8px; font-size:19px; letter-spacing:-.02em; }
        .card p { margin:0; color:#98aac1; font-size:14px; }
        .architecture { display:grid; grid-template-columns:1fr 1fr; gap:58px; align-items:center; }
        .steps { display:grid; gap:13px; }
        .step { display:grid; grid-template-columns:42px 1fr; gap:16px; padding:20px; border-left:1px solid #265382; background:linear-gradient(90deg,rgba(32,103,185,.1),transparent); }
        .step b { width:34px; height:34px; display:grid; place-items:center; border-radius:50%; color:#8cc1ff; background:#102a52; font-size:12px; }
        .step strong { display:block; margin-bottom:4px; }.step p { margin:0;color:#91a5c0;font-size:14px; }
        .cta { margin:45px 0 100px; padding:44px; display:flex; align-items:center; justify-content:space-between; gap:30px; border:1px solid rgba(63,159,255,.3); border-radius:25px; background:linear-gradient(110deg,rgba(28,99,201,.23),rgba(13,171,159,.12)); }
        .cta h2 { margin:0 0 8px; font-size:32px; }.cta p { margin:0;color:#a6b8ce; }
        footer { padding:28px 0 36px; border-top:1px solid var(--line); color:#7f92ab; font-size:13px; }
        footer .shell { display:flex; justify-content:space-between; gap:20px; }
        @media (max-width:900px) { .hero,.architecture { grid-template-columns:1fr; }.hero{padding-top:54px;gap:50px}.grid{grid-template-columns:1fr 1fr}.nav-links a:not(.status){display:none}.cta{align-items:flex-start;flex-direction:column} }
        @media (max-width:600px) { .shell{width:min(100% - 26px,1180px)}nav{height:70px}.brand span{font-size:14px}.brand-banner{margin-top:26px;border-radius:16px}.hero{padding-top:42px}.grid{grid-template-columns:1fr}.provider-row{grid-template-columns:1fr}.terminal{padding:13px}.cta{padding:28px}footer .shell{flex-direction:column} }
        @media (prefers-reduced-motion:reduce) { html{scroll-behavior:auto}.button{transition:none} }
    </style>
</head>
<body>
    <header class="shell">
        <nav aria-label="Primary navigation">
            <a class="brand" href="/">
                <img src="{{ asset('images/bwa-logo.jpeg') }}" alt="Build With Abdallah logo">
                <span>Build With Abdallah<small>Central API</small></span>
            </a>
            <div class="nav-links">
                <a href="#capabilities">Capabilities</a>
                <a href="#architecture">Architecture</a>
                <a class="status" href="{{ route('health.ready') }}"><i class="dot"></i><span id="status-label">API online</span></a>
            </div>
        </nav>
    </header>

    <main>
        <div class="shell brand-banner">
            <img src="{{ asset('images/bwa-banner.jpeg') }}" alt="Build With Abdallah — Software, Automation, APIs, Solutions" width="1536" height="512">
        </div>
        <div class="shell hero">
            <div>
                <div class="eyebrow">Integration infrastructure</div>
                <h1>One secure layer for <span class="gradient">messaging and billing.</span></h1>
                <p class="lead">A central API that lets applications integrate once, then safely routes WhatsApp communication, Stripe billing, provider webhooks, and delivery events at scale.</p>
                <div class="hero-actions">
                    <a class="button primary" href="#architecture">Explore the architecture <span>→</span></a>
                    <a class="button" href="{{ route('health.ready') }}">View API status</a>
                </div>
            </div>
            <div class="terminal" aria-label="Platform request flow">
                <div class="terminal-head"><div class="lights"><i></i><i></i><i></i></div><span>api.buildwithabdallah.com</span></div>
                <div class="flow">
                    <div class="node"><div class="node-icon">01</div><div><strong>Connected applications</strong><span>Kirada · Djib Payroll · future products</span></div></div>
                    <div class="connector"></div>
                    <div class="node"><div class="node-icon">API</div><div><strong>Build With Abdallah</strong><span>Authentication · orchestration · audit trail</span></div></div>
                    <div class="connector"></div>
                    <div class="provider-row">
                        <div class="node"><div class="node-icon">WA</div><div><strong>WhatsApp</strong><span>Meta / Sent</span></div></div>
                        <div class="node"><div class="node-icon">$</div><div><strong>Billing</strong><span>Stripe Connect</span></div></div>
                    </div>
                </div>
            </div>
        </div>

        <section id="capabilities">
            <div class="shell">
                <div class="section-kicker">Platform capabilities</div>
                <h2>Infrastructure designed for real products.</h2>
                <p class="section-copy">The platform keeps provider complexity, credentials, retries, and audit history out of product applications while preserving clear ownership of every event.</p>
                <div class="grid">
                    <article class="card"><span class="num">01 / AUTH</span><h3>Signed application access</h3><p>Each product receives isolated credentials. Requests are timestamped, signed, and replay-protected before entering the platform.</p></article>
                    <article class="card"><span class="num">02 / WA</span><h3>WhatsApp orchestration</h3><p>A provider-neutral messaging contract routes traffic through Meta or Sent without forcing every application to rebuild its integration.</p></article>
                    <article class="card"><span class="num">03 / BILLING</span><h3>Centralized Stripe billing</h3><p>Customer mappings, checkout sessions, portal access, and Stripe webhook lifecycle events are coordinated centrally.</p></article>
                    <article class="card"><span class="num">04 / EVENTS</span><h3>Reliable event delivery</h3><p>Application callbacks are queued, signed, retried, and tracked with idempotency controls and operational visibility.</p></article>
                    <article class="card"><span class="num">05 / PRIVACY</span><h3>Protected data boundaries</h3><p>Sensitive message content and provider payloads remain encrypted and are excluded from the operations interface.</p></article>
                    <article class="card"><span class="num">06 / OPS</span><h3>Observable by design</h3><p>Queues, exceptions, slow requests, provider events, and failed deliveries are monitored from a secured operations console.</p></article>
                </div>
            </div>
        </section>

        <section id="architecture">
            <div class="shell architecture">
                <div>
                    <div class="section-kicker">How it works</div>
                    <h2>Products stay focused. The platform handles the plumbing.</h2>
                    <p class="section-copy">Adding a new application becomes a configuration exercise instead of another provider integration project.</p>
                </div>
                <div class="steps">
                    <div class="step"><b>1</b><div><strong>Authenticate once</strong><p>The connected application signs a versioned API request with its own secret.</p></div></div>
                    <div class="step"><b>2</b><div><strong>Route centrally</strong><p>The platform validates policy and chooses the configured messaging or billing provider.</p></div></div>
                    <div class="step"><b>3</b><div><strong>Deliver reliably</strong><p>Provider responses and webhooks become normalized, signed application events.</p></div></div>
                    <div class="step"><b>4</b><div><strong>Operate confidently</strong><p>Failures surface in one secured console with explicit, auditable retry controls.</p></div></div>
                </div>
            </div>
        </section>

        <div class="shell cta">
            <div><h2>Built as a foundation for a growing product portfolio.</h2><p>Secure integrations, fewer duplicated services, and one operational view.</p></div>
            <a class="button primary" href="mailto:buildwithabdallah@gmail.com">Discuss an integration <span>→</span></a>
        </div>
    </main>

    <footer><div class="shell"><span>© {{ date('Y') }} Build With Abdallah</span><span>Software · Automation · APIs · Solutions</span></div></footer>
    <script>
        fetch(@json(route('health.ready')), { headers: { Accept: 'application/json' } })
            .then(response => { if (!response.ok) throw new Error('unavailable'); return response.json(); })
            .then(() => document.getElementById('status-label').textContent = 'All systems ready')
            .catch(() => document.getElementById('status-label').textContent = 'Status unavailable');
    </script>
</body>
</html>
