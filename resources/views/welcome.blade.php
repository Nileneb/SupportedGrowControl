<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>GrowDash – Smart Grow Control System</title>
        <meta name="description" content="Modern IoT platform for automated grow control. Monitor sensors, control actuators, and manage your setup from anywhere.">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|space-grotesk:700" rel="stylesheet" />
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            
            :root {
                --bg-primary: #0a0a0f;
                --bg-secondary: #13131a;
                --bg-tertiary: #1a1a24;
                --text-primary: #f8fafc;
                --text-secondary: #94a3b8;
                --text-muted: #64748b;
                --brand: #3b82f6;
                --brand-light: #60a5fa;
                --accent: #10b981;
                --accent-orange: #f97316;
                --border: #1e293b;
                --glow-brand: rgba(59, 130, 246, 0.3);
                --glow-accent: rgba(16, 185, 129, 0.3);
            }
            
            body {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                background: var(--bg-primary);
                color: var(--text-primary);
                line-height: 1.6;
                overflow-x: hidden;
            }
            
            /* Typography */
            h1, h2, h3 { font-family: 'Space Grotesk', sans-serif; font-weight: 700; line-height: 1.2; }
            h1 { font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 1.5rem; }
            h2 { font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 1rem; }
            h3 { font-size: clamp(1.25rem, 2.5vw, 1.75rem); margin-bottom: 0.75rem; }
            
            .gradient-text {
                background: linear-gradient(135deg, var(--brand-light), var(--accent));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            /* Container */
            .container { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }
            
            /* Navigation */
            nav {
                position: sticky;
                top: 0;
                z-index: 50;
                backdrop-filter: blur(12px);
                background: rgba(10, 10, 15, 0.8);
                border-bottom: 1px solid var(--border);
            }
            
            nav .container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
            
            .logo {
                font-size: 1.5rem;
                font-weight: 700;
                font-family: 'Space Grotesk', sans-serif;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .logo-icon {
                width: 32px;
                height: 32px;
                background: linear-gradient(135deg, var(--brand), var(--accent));
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
            }
            
            .nav-links {
                display: flex;
                gap: 1rem;
                align-items: center;
            }
            
            /* Buttons */
            .btn {
                padding: 0.625rem 1.25rem;
                border-radius: 0.75rem;
                font-weight: 500;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
                border: 1px solid var(--border);
                color: var(--text-primary);
            }
            
            .btn:hover { transform: translateY(-2px); }
            
            .btn-primary {
                background: linear-gradient(135deg, var(--brand), var(--brand-light));
                border-color: transparent;
                box-shadow: 0 4px 20px var(--glow-brand);
            }
            
            .btn-primary:hover { box-shadow: 0 8px 30px var(--glow-brand); }
            
            /* Hero Section */
            .hero {
                padding: 6rem 0 4rem;
                text-align: center;
                position: relative;
            }
            
            .hero::before {
                content: '';
                position: absolute;
                top: -20%;
                left: 50%;
                transform: translateX(-50%);
                width: 800px;
                height: 800px;
                background: radial-gradient(circle, var(--glow-brand) 0%, transparent 70%);
                opacity: 0.15;
                pointer-events: none;
            }
            
            .hero-content { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; }
            
            .hero p {
                font-size: clamp(1.125rem, 2vw, 1.5rem);
                color: var(--text-secondary);
                max-width: 700px;
                margin: 0 auto 2.5rem;
            }
            
            .hero-cta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
            
            /* Feature Cards */
            .features {
                padding: 4rem 0;
                background: linear-gradient(to bottom, transparent, var(--bg-secondary) 50%, transparent);
            }
            
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 2rem;
                margin-top: 3rem;
            }
            
            .feature-card {
                background: var(--bg-tertiary);
                border: 1px solid var(--border);
                border-radius: 1rem;
                padding: 2rem;
                transition: all 0.3s;
                position: relative;
                overflow: hidden;
            }
            
            .feature-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, var(--brand), var(--accent));
                transform: scaleX(0);
                transition: transform 0.3s;
            }
            
            .feature-card:hover::before { transform: scaleX(1); }
            .feature-card:hover { transform: translateY(-8px); border-color: var(--brand); }
            
            .feature-icon {
                width: 56px;
                height: 56px;
                border-radius: 12px;
                background: linear-gradient(135deg, var(--brand), var(--accent));
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.75rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-card h3 { color: var(--text-primary); margin-bottom: 0.75rem; }
            .feature-card p { color: var(--text-secondary); font-size: 0.9375rem; }
            
            /* How It Works */
            .how-it-works {
                padding: 6rem 0;
                background: var(--bg-secondary);
            }
            
            .steps {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 3rem;
                margin-top: 4rem;
                position: relative;
            }
            
            .step {
                text-align: center;
                position: relative;
            }
            
            .step-number {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--brand), var(--accent));
                color: white;
                font-size: 1.75rem;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                box-shadow: 0 8px 24px var(--glow-brand);
            }
            
            .step h3 { font-size: 1.5rem; margin-bottom: 1rem; }
            .step p { color: var(--text-secondary); max-width: 280px; margin: 0 auto; }
            
            /* Visual Flow Diagram */
            .flow-diagram {
                margin: 4rem 0;
                padding: 3rem;
                background: var(--bg-tertiary);
                border-radius: 1.5rem;
                border: 1px solid var(--border);
            }
            
            .flow-items {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 2rem;
            }
            
            .flow-item {
                flex: 1;
                min-width: 200px;
                text-align: center;
                position: relative;
            }
            
            .flow-box {
                background: var(--bg-primary);
                border: 2px solid var(--brand);
                border-radius: 1rem;
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .flow-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
            .flow-label { font-weight: 600; font-size: 1.125rem; }
            .flow-desc { color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem; }
            
            .flow-arrow {
                position: absolute;
                right: -2.5rem;
                top: 50%;
                transform: translateY(-50%);
                font-size: 2rem;
                color: var(--brand);
            }
            
            /* Tech Stack */
            .tech-stack {
                padding: 6rem 0;
                text-align: center;
            }
            
            .tech-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 2rem;
                margin-top: 3rem;
                max-width: 900px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .tech-item {
                background: var(--bg-tertiary);
                border: 1px solid var(--border);
                border-radius: 1rem;
                padding: 1.5rem;
                transition: all 0.3s;
            }
            
            .tech-item:hover {
                transform: scale(1.05);
                border-color: var(--brand);
                box-shadow: 0 8px 24px var(--glow-brand);
            }
            
            .tech-logo { font-size: 2.5rem; margin-bottom: 0.75rem; }
            .tech-name { font-weight: 600; font-size: 0.9375rem; }
            
            /* CTA Section */
            .cta-section {
                padding: 6rem 0;
                background: linear-gradient(135deg, var(--brand), var(--accent));
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            
            .cta-section::before {
                content: '';
                position: absolute;
                width: 600px;
                height: 600px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                top: -300px;
                right: -200px;
            }
            
            .cta-content { position: relative; z-index: 1; }
            .cta-section h2 { color: white; }
            .cta-section p { color: rgba(255, 255, 255, 0.9); font-size: 1.25rem; margin-bottom: 2rem; }
            
            .btn-white {
                background: white;
                color: var(--brand);
                border: none;
                font-weight: 600;
            }
            
            .btn-white:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3); }
            
            /* Footer */
            footer {
                padding: 3rem 0;
                background: var(--bg-secondary);
                border-top: 1px solid var(--border);
                text-align: center;
                color: var(--text-muted);
            }
            
            /* Animations */
            @keyframes float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-20px); }
            }
            
            .float { animation: float 6s ease-in-out infinite; }
            
            /* Responsive */
            @media (max-width: 768px) {
                .nav-links { gap: 0.5rem; }
                .btn { padding: 0.5rem 1rem; font-size: 0.875rem; }
                .hero { padding: 3rem 0 2rem; }
                .features, .how-it-works, .tech-stack { padding: 3rem 0; }
                .flow-items { flex-direction: column; }
                .flow-arrow { display: none; }
                .steps { gap: 2rem; }
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav>
            <div class="container">
                <div class="logo">
                    <div class="logo-icon">🌱</div>
                    <span>GrowDash</span>
                </div>
                <div class="nav-links">
                    @if (Route::has('login'))
                        @auth
                            <a class="btn" href="{{ url('/dashboard') }}">Dashboard</a>
                        @else
                            <a class="btn" href="{{ route('login') }}">Login</a>
                            @if (Route::has('register'))
                                <a class="btn btn-primary" href="{{ route('register') }}">Get Started</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>
                        <span class="gradient-text">Smart Grow Control</span><br>
                        Made Simple
                    </h1>
                    <p>
                        Monitor sensors, control actuators, and automate your entire grow setup from anywhere. 
                        Real-time data, secure commands, and complete control at your fingertips.
                    </p>
                    <div class="hero-cta">
                        <a href="{{ route('register') }}" class="btn btn-primary">Start Free Trial</a>
                        <a href="#how-it-works" class="btn">See How It Works</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="features">
            <div class="container">
                <h2 class="gradient-text" style="text-align: center;">Everything You Need</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Real-Time Monitoring</h3>
                        <p>Track temperature, humidity, TDS, water levels, and more. Live updates via WebSockets keep you informed instantly.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎮</div>
                        <h3>Remote Control</h3>
                        <p>Control pumps, valves, lights, and fans from anywhere. Send commands securely and get instant feedback.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔌</div>
                        <h3>Easy Setup</h3>
                        <p>Plug in your device, scan the pairing code, and you're online in seconds. No complex configuration needed.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📈</div>
                        <h3>Historical Data</h3>
                        <p>Review trends, analyze patterns, and optimize your setup with comprehensive charts and logs.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h3>Secure & Private</h3>
                        <p>End-to-end encryption, token authentication, and multi-user policies keep your data safe.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3>Automation Ready</h3>
                        <p>Schedule commands, set triggers, and automate repetitive tasks. Let the system work for you.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="how-it-works">
            <div class="container">
                <h2 class="gradient-text" style="text-align: center;">How It Works</h2>
                
                <!-- Visual Flow -->
                <div class="flow-diagram">
                    <div class="flow-items">
                        <div class="flow-item">
                            <div class="flow-box">
                                <div class="flow-icon">🔌</div>
                                <div class="flow-label">Device</div>
                            </div>
                            <div class="flow-desc">Arduino/ESP with sensors</div>
                            <div class="flow-arrow">→</div>
                        </div>
                        <div class="flow-item">
                            <div class="flow-box">
                                <div class="flow-icon">🐍</div>
                                <div class="flow-label">Agent</div>
                            </div>
                            <div class="flow-desc">Python bridge</div>
                            <div class="flow-arrow">→</div>
                        </div>
                        <div class="flow-item">
                            <div class="flow-box">
                                <div class="flow-icon">☁️</div>
                                <div class="flow-label">Cloud</div>
                            </div>
                            <div class="flow-desc">Laravel backend</div>
                            <div class="flow-arrow">→</div>
                        </div>
                        <div class="flow-item">
                            <div class="flow-box">
                                <div class="flow-icon">📱</div>
                                <div class="flow-label">You</div>
                            </div>
                            <div class="flow-desc">Web dashboard</div>
                        </div>
                    </div>
                </div>

                <!-- Steps -->
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Connect Device</h3>
                        <p>Flash the Arduino code, connect your sensors, and power on your device.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Pair Online</h3>
                        <p>Enter the device ID and pairing code shown on the serial monitor in your dashboard.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Monitor & Control</h3>
                        <p>Watch live sensor data, send commands, and automate your grow environment.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tech Stack -->
        <section class="tech-stack">
            <div class="container">
                <h2 class="gradient-text">Built with Modern Tech</h2>
                <p style="color: var(--text-secondary); max-width: 700px; margin: 1rem auto 0;">Powered by industry-leading frameworks and tools for reliability, security, and performance.</p>
                
                <div class="tech-grid">
                    <div class="tech-item">
                        <div class="tech-logo">⚡</div>
                        <div class="tech-name">Laravel 12</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-logo">⚛️</div>
                        <div class="tech-name">Livewire 3</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-logo">🔄</div>
                        <div class="tech-name">Reverb</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-logo">🗄️</div>
                        <div class="tech-name">PostgreSQL</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-logo">🐍</div>
                        <div class="tech-name">Python</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-logo">🎨</div>
                        <div class="tech-name">Flux UI</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Ready to Get Started?</h2>
                    <p>Join growers worldwide using GrowDash for smarter automation.</p>
                    <a href="{{ route('register') }}" class="btn btn-white">Create Free Account</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <p>&copy; {{ date('Y') }} GrowDash. Built with ❤️ for growers.</p>
            </div>
        </footer>
    </body>
</html>
