<!DOCTYPE html>
<html lang="de" data-theme="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DAN — Digitaler Ausbildungsnachweis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #F4F7FB;
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --text: #1A2233;
            --muted: #64748B;
            --blue: #2563EB;
            --blue-bg: #EEF3FF;
            --blue-dark: #1D4ED8;
            --font: 'DM Sans', system-ui, sans-serif;
        }

        html, body {
            height: 100%;
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
        }

        /* ─── Layout ─── */
        .welcome-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── Top bar ─── */
        .welcome-nav {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            height: 60px;
            display: flex;
            align-items: center;
        }
        .welcome-nav-inner {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 24px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .wn-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .wn-logo-mark {
            width: 30px; height: 30px;
            background: var(--blue);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
        }
        .wn-logo-title { font-size: 15px; font-weight: 700; color: var(--text); letter-spacing: -.3px; }
        .wn-logo-sub   { font-size: 9.5px; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); margin-top: 1px; }

        .wn-links { display: flex; align-items: center; gap: 8px; }
        .wn-link-ghost {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            transition: background .12s, color .12s;
        }
        .wn-link-ghost:hover { background: #F0F4FA; color: var(--text); }
        .wn-link-btn {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            background: var(--blue);
            color: #fff;
            text-decoration: none;
            transition: opacity .12s;
        }
        .wn-link-btn:hover { opacity: .88; }

        /* ─── Hero ─── */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 24px 60px;
        }
        .hero-inner {
            max-width: 640px;
            width: 100%;
            text-align: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: var(--blue-bg);
            border: 1px solid #BFDBFE;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 500;
            color: var(--blue);
            margin-bottom: 28px;
        }
        .hero-title {
            font-size: clamp(32px, 5vw, 52px);
            font-weight: 700;
            letter-spacing: -1.5px;
            line-height: 1.1;
            color: var(--text);
            margin-bottom: 18px;
        }
        .hero-title span { color: var(--blue); }
        .hero-desc {
            font-size: 17px;
            line-height: 1.65;
            color: var(--muted);
            max-width: 480px;
            margin: 0 auto 36px;
        }
        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cta-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            background: var(--blue);
            color: #fff;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s, transform .15s;
            box-shadow: 0 4px 16px rgba(37,99,235,.3);
        }
        .cta-primary:hover { opacity: .9; transform: translateY(-1px); }
        .cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 24px;
            border: 1.5px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            background: var(--surface);
            transition: border-color .15s;
        }
        .cta-secondary:hover { border-color: #94A3B8; }

        /* ─── Features ─── */
        .features {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 24px 80px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        @media (max-width: 640px) { .features { grid-template-columns: 1fr; } }

        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .feature-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 4px;
        }
        .feature-title { font-size: 14.5px; font-weight: 600; color: var(--text); }
        .feature-desc  { font-size: 13px; color: var(--muted); line-height: 1.6; }

        /* ─── Footer ─── */
        .welcome-footer {
            text-align: center;
            padding: 20px 24px 28px;
            font-size: 12.5px;
            color: var(--muted);
            border-top: 1px solid var(--border);
        }
    </style>
</head>
<body>
<div class="welcome-wrap">

    {{-- Navbar --}}
    <nav class="welcome-nav">
        <div class="welcome-nav-inner">
            <a href="/" class="wn-logo">
                <div class="wn-logo-mark">
                    <svg width="17" height="17" viewBox="0 0 20 20" fill="none">
                        <rect x="3.5" y="2" width="11" height="14" rx="1.5" stroke="white" stroke-width="1.4" fill="none"/>
                        <line x1="6" y1="6.5" x2="13" y2="6.5" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                        <line x1="6" y1="9.5" x2="13" y2="9.5" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                        <line x1="6" y1="12.5" x2="10" y2="12.5" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                        <circle cx="15" cy="15" r="3.5" fill="#60A5FA"/>
                        <path d="M13.5 15l1 1 2-2" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <div class="wn-logo-title">DAN</div>
                    <div class="wn-logo-sub">Ausbildungsnachweis</div>
                </div>
            </a>
            <div class="wn-links">
                @auth
                    <a href="{{ url('/dashboard') }}" class="wn-link-btn">
                        Zum Dashboard →
                    </a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="wn-link-ghost">Anmelden</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="wn-link-btn">Registrieren</a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Digitale Ausbildungsdokumentation
            </div>
            <h1 class="hero-title">
                Dein Ausbildungsnachweis.<br>
                <span>Digital. Einfach. Vollständig.</span>
            </h1>
            <p class="hero-desc">
                DAN macht die Ausbildungsdokumentation einfacher — Wochenberichte, Aufgaben, Noten und mehr an einem Ort.
            </p>
            <div class="hero-cta">
                @auth
                    <a href="{{ url('/dashboard') }}" class="cta-primary">
                        Dashboard öffnen
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="cta-primary">
                            Jetzt anmelden
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="cta-secondary">
                            Account erstellen
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- Features --}}
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon" style="background:#EEF3FF">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>
            </div>
            <div class="feature-title">Wochenberichte</div>
            <div class="feature-desc">Berichte erstellen, signieren und als PDF exportieren. Mit automatischem Abschicken freitags.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#DCFCE7">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="feature-title">Aufgaben-Kanban</div>
            <div class="feature-desc">Aufgaben planen, priorisieren und den Ausbildern zeigen was du gerade machst.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#FEF3C7">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="feature-title">Kalender & Noten</div>
            <div class="feature-desc">Schultermine, Tests und Klausuren tracken — inklusive Notendurchschnitt pro Fach.</div>
        </div>
    </div>

    <footer class="welcome-footer">
        DAN — Digitaler Ausbildungsnachweis &middot; {{ date('Y') }}
    </footer>

</div>
</body>
</html>
