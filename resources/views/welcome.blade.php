<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>GrowDash – Modular Grow Control</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <style>
            :root { --bg:#0b0b0b; --fg:#f3f3f0; --muted:#a1a09a; --brand:#38bdf8; --accent:#10b981; }
            *{box-sizing:border-box}html,body{height:100%}
            body{margin:0;background:var(--bg);color:var(--fg);font-family:Instrument Sans,ui-sans-serif,system-ui}
            a{color:var(--brand);text-decoration:none}
            .container{max-width:1100px;margin:0 auto;padding:24px}
            .nav{display:flex;justify-content:space-between;align-items:center}
            .nav a.btn{border:1px solid #2b2b2b;padding:8px 14px;border-radius:8px;color:var(--fg)}
            .hero{display:grid;grid-template-columns:1.2fr 0.8fr;gap:32px;align-items:center;padding:60px 0}
            .hero h1{font-size:40px;line-height:1.1;margin:0 0 12px}
            .hero p{color:var(--muted);font-size:18px;margin:0 0 20px}
            .cta{display:flex;gap:12px;margin-top:8px}
            .cta a{padding:10px 14px;border-radius:10px;border:1px solid #2b2b2b}
            .cta .primary{background:var(--brand);color:#0b0b0b;border-color:transparent}
            .panel{border:1px solid #222;border-radius:14px;padding:18px;background:#101010}
            .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
            .kpi{display:flex;align-items:center;gap:12px}
            .kpi .dot{width:10px;height:10px;border-radius:999px;background:var(--accent)}
            .list{display:grid;gap:10px;margin-top:12px}
            .list .row{display:flex;justify-content:space-between;color:var(--muted)}
            footer{padding:28px 0;color:#888;text-align:center}
            @media (max-width:900px){.hero{grid-template-columns:1fr} .nav{gap:12px}}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="nav">
                <div>
                    <strong>GrowDash</strong>
                </div>
                <div class="cta">
                    @if (Route::has('login'))
                        @auth
                            <a class="btn" href="{{ url('/dashboard') }}">Open Dashboard</a>
                        @else
                            <a class="btn" href="{{ route('login') }}">Log in</a>
                            @if (Route::has('register'))
                                <a class="btn" href="{{ route('register') }}">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>

            <section class="hero">
                <div>
                    <h1>Modular IoT control for growers</h1>
                    <p>Pair your device in seconds, stream telemetry in real-time, queue safe commands, and audit every event. Built on Laravel 12, Livewire 3, and Reverb.</p>
                    <div class="cta">
                        <a class="primary" href="{{ url('/dashboard') }}">Launch Dashboard</a>
                        <a href="{{ route('login') }}">Device Pairing</a>
                        <a href="https://grow.linn.games" target="_blank" rel="noopener">Docs</a>
                    </div>
                </div>
                <div class="panel">
                    <div class="grid">
                        <div class="kpi"><span class="dot"></span><div><strong>Realtime</strong><br><span class="muted">WebSockets events</span></div></div>
                        <div class="kpi"><span class="dot"></span><div><strong>Secure</strong><br><span class="muted">Token + policy</span></div></div>
                        <div class="kpi"><span class="dot"></span><div><strong>Telemetry</strong><br><span class="muted">Time-series DB</span></div></div>
                        <div class="kpi"><span class="dot"></span><div><strong>Commands</strong><br><span class="muted">Queued + auditable</span></div></div>
                    </div>
                    <div class="list">
                        <div class="row"><span>Pairing flow</span><span>Bootstrap ID + code</span></div>
                        <div class="row"><span>Device auth</span><span>Header tokens</span></div>
                        <div class="row"><span>Capabilities</span><span>Dynamic JSON</span></div>
                        <div class="row"><span>Logs & status</span><span>Full history</span></div>
                    </div>
                </div>
            </section>

            <section class="grid">
                <div class="panel">
                    <h3>How it works</h3>
                    <p class="muted">Agents bootstrap, users pair, devices stream telemetry and poll commands. Policies enforce multi-tenant isolation; all writes authenticated by tokens.</p>
                </div>
                <div class="panel">
                    <h3>Tech stack</h3>
                    <p class="muted">Laravel 12, Fortify, Livewire 3, Flux UI, Reverb, Pest tests. SQLite default for dev; ready for Postgres in production.</p>
                </div>
            </section>

            <footer>
                © {{ date('Y') }} GrowDash. All rights reserved.
            </footer>
        </div>
    </body>
</html>
