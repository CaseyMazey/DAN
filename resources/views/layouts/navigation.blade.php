<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');
    /* Global body bg so dark mode covers the full viewport on every page */
    :root { --p-bg:#F4F7FB; }
    html[data-theme="dark"] { --p-bg:#0D1117; }
    body { background: var(--p-bg) !important; }

    /* ─── CSS Variables ─── */
    :root {
        --nav-h: 60px;
        --nav-bg: rgba(255,255,255,0.9);
        --nav-border: #E8EDF4;
        --nav-text: #5A6478;
        --nav-text-hover: #111827;
        --nav-active-bg: #EEF3FF;
        --nav-active-text: #2563EB;
        --nav-hover-bg: #F4F7FB;
        --nav-surface: #FFFFFF;
        --nav-dd-shadow: 0 4px 20px rgba(15,23,42,0.12);
        --nav-muted: #94A3B8;
        --nav-danger: #EF4444;
        --nav-danger-bg: #FEF2F2;
        --nav-radius: 8px;
        --nav-font: 'DM Sans', system-ui, sans-serif;
    }

    html[data-theme="dark"] {
        --nav-bg: rgba(13,17,23,0.9);
        --nav-border: #21262D;
        --nav-text: #8B949E;
        --nav-text-hover: #E6EDF3;
        --nav-active-bg: #0D2340;
        --nav-active-text: #58A6FF;
        --nav-hover-bg: #161B22;
        --nav-surface: #1C2128;
        --nav-dd-shadow: 0 4px 20px rgba(0,0,0,0.5);
        --nav-muted: #484F58;
        --nav-danger-bg: #2D0A0A;
    }

    /* ─── Navbar shell ─── */
    #dan-nav {
        position: sticky;
        top: 0;
        z-index: 500;
        height: var(--nav-h);
        background: var(--nav-bg);
        border-bottom: 1px solid var(--nav-border);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        font-family: var(--nav-font);
        transition: border-color .2s, background .2s;
    }

    #dan-nav-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 20px;
        height: 100%;
        display: flex;
        align-items: center;
    }

    /* ─── Logo ─── */
    .dn-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        flex-shrink: 0;
        margin-right: 8px;
    }
    .dn-logo-mark {
        width: 30px;
        height: 30px;
        background: #2563EB;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 1px 4px rgba(37,99,235,.35);
        transition: transform .15s, box-shadow .15s;
    }
    .dn-logo:hover .dn-logo-mark {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(37,99,235,.4);
    }
    html[data-theme="dark"] .dn-logo-mark { background: #1D4ED8; }
    .dn-logo-text { display: flex; flex-direction: column; line-height: 1; }
    .dn-logo-title { font-size: 14px; font-weight: 700; color: #111827; letter-spacing: -.3px; }
    html[data-theme="dark"] .dn-logo-title { color: #E6EDF3; }
    .dn-logo-sub { font-size: 9.5px; font-weight: 400; text-transform: uppercase; letter-spacing: .6px; color: var(--nav-muted); margin-top: 2px; }

    /* ─── Nav links ─── */
    .dn-links { display: flex; align-items: center; gap: 1px; flex: 1; padding-left: 16px; }

    .dn-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 11px;
        border-radius: var(--nav-radius);
        font-size: 13.5px;
        font-weight: 400;
        color: var(--nav-text);
        text-decoration: none;
        transition: background .12s, color .12s;
        white-space: nowrap;
        background: none;
        border: none;
        cursor: pointer;
        font-family: var(--nav-font);
        line-height: 1;
    }
    .dn-link:hover { background: var(--nav-hover-bg); color: var(--nav-text-hover); }
    .dn-link.active { background: var(--nav-active-bg); color: var(--nav-active-text); font-weight: 500; }

    /* ─── Right cluster ─── */
    .dn-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

    .dn-icon-btn {
        width: 32px; height: 32px;
        border-radius: 7px;
        border: 1px solid var(--nav-border);
        background: transparent;
        color: var(--nav-text);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: background .12s, color .12s;
        flex-shrink: 0;
    }
    .dn-icon-btn:hover { background: var(--nav-hover-bg); color: var(--nav-text-hover); }

    /* ─── User button ─── */
    .dn-user-btn {
        display: flex; align-items: center; gap: 7px;
        padding: 4px 10px 4px 4px;
        border-radius: 99px;
        border: 1px solid var(--nav-border);
        background: transparent;
        cursor: pointer;
        transition: background .12s, border-color .12s;
        font-family: var(--nav-font);
    }
    .dn-user-btn:hover { background: var(--nav-hover-bg); border-color: #CBD5E1; }
    html[data-theme="dark"] .dn-user-btn:hover { border-color: #444C56; }

    .dn-avatar {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: #2563EB;
        color: #fff;
        font-size: 10.5px; font-weight: 600;
        display: flex; align-items: center; justify-content: center;
        letter-spacing: .3px; flex-shrink: 0;
    }
    html[data-theme="dark"] .dn-avatar { background: #1D4ED8; }

    .dn-username { font-weight: 500; color: var(--nav-text-hover); max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
    html[data-theme="dark"] .dn-username { color: #E6EDF3; }
    .dn-chevron { color: var(--nav-muted); transition: transform .18s; flex-shrink: 0; }
    .dn-chevron.flipped { transform: rotate(180deg); }

    /* ─── Dropdown ─── */
    .dn-dd-wrap { position: relative; }
    .dn-dd {
        position: absolute;
        top: calc(100% + 7px); right: 0;
        min-width: 175px;
        background: var(--nav-surface);
        border: 1px solid var(--nav-border);
        border-radius: 11px;
        box-shadow: var(--nav-dd-shadow);
        padding: 4px;
        opacity: 0; transform: translateY(-5px); pointer-events: none;
        transition: opacity .14s, transform .14s;
        z-index: 600;
    }
    .dn-dd.dn-left { right: auto; left: 0; }
    .dn-dd.dn-open { opacity: 1; transform: translateY(0); pointer-events: all; }

    .dn-dd-item {
        display: flex; align-items: center; gap: 8px;
        padding: 7px 10px;
        border-radius: 7px;
        font-size: 13px;
        color: var(--nav-text);
        text-decoration: none;
        transition: background .1s, color .1s;
        border: none; background: none;
        width: 100%; text-align: left; cursor: pointer;
        font-family: var(--nav-font);
    }
    .dn-dd-item:hover { background: var(--nav-hover-bg); color: var(--nav-text-hover); }
    .dn-dd-item.danger { color: var(--nav-danger); }
    .dn-dd-item.danger:hover { background: var(--nav-danger-bg); }
    .dn-dd-sep { height: 1px; background: var(--nav-border); margin: 4px 0; }

    /* ─── Hamburger ─── */
    .dn-hamburger {
        display: none;
        width: 33px; height: 33px;
        border-radius: 7px;
        border: 1px solid var(--nav-border);
        background: transparent; color: var(--nav-text);
        align-items: center; justify-content: center;
        cursor: pointer; transition: background .12s;
    }
    .dn-hamburger:hover { background: var(--nav-hover-bg); }

    /* ─── Mobile menu ─── */
    #dn-mobile {
        display: none;
        background: var(--nav-surface);
        border-top: 1px solid var(--nav-border);
        padding: 8px 14px 14px;
        font-family: var(--nav-font);
    }
    #dn-mobile.dn-open { display: flex; flex-direction: column; gap: 2px; }
    .dn-mobile-header { padding: 8px 8px 10px; display: flex; align-items: center; gap: 9px; }
    .dn-mobile-name { font-size: 13.5px; font-weight: 600; color: var(--nav-text-hover); }
    .dn-mobile-email { font-size: 11.5px; color: var(--nav-muted); margin-top: 1px; }
    .dn-mobile-sep { height: 1px; background: var(--nav-border); margin: 6px 0; }

    @media (max-width: 720px) {
        .dn-links { display: none; }
        .dn-user-btn { display: none; }
        .dn-icon-btn { display: none; }
        .dn-hamburger { display: flex; }
    }
</style>

<nav id="dan-nav">
    <div id="dan-nav-inner">

        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" class="dn-logo">
            <div class="dn-logo-mark">
                <svg width="17" height="17" viewBox="0 0 20 20" fill="none">
                    <rect x="3.5" y="2" width="11" height="14" rx="1.5" stroke="white" stroke-width="1.4" fill="none"/>
                    <line x1="6" y1="6.5" x2="13" y2="6.5" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                    <line x1="6" y1="9.5" x2="13" y2="9.5" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                    <line x1="6" y1="12.5" x2="10" y2="12.5" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                    <circle cx="15" cy="15" r="3.5" fill="#60A5FA"/>
                    <path d="M13.5 15l1 1 2-2" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="dn-logo-text">
                <span class="dn-logo-title">DAN</span>
                <span class="dn-logo-sub">Ausbildungsnachweis</span>
            </div>
        </a>

        {{-- Desktop links --}}
        <div class="dn-links">
            <a href="{{ route('dashboard') }}" class="dn-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>

            @auth
                @if(auth()->user()->rolle === 'azubi')
                    <a href="{{ route('wochenbericht.index') }}" class="dn-link {{ request()->routeIs('wochenbericht.*') ? 'active' : '' }}">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>
                        Wochenberichte
                    </a>
                    <a href="{{ route('aufgaben.index') }}" class="dn-link {{ request()->routeIs('aufgaben.*') ? 'active' : '' }}">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Aufgaben
                    </a>
                    <a href="{{ route('kalender.index') }}" class="dn-link {{ request()->routeIs('kalender.*') ? 'active' : '' }}">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Kalender
                    </a>
                @endif

                @if(auth()->user()->rolle === 'ausbilder')
                    <a href="{{ route('wochenbericht.index') }}" class="dn-link {{ request()->routeIs('wochenbericht.*') ? 'active' : '' }}">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Wochenberichte
                    </a>
                @endif

                @if(auth()->user()->rolle === 'admin')
                    <div class="dn-dd-wrap">
                        <button class="dn-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                                onclick="dnToggle('adminDd', this)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            Verwaltung
                            <svg class="dn-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="dn-dd dn-left" id="adminDd">
                            <a href="{{ route('admin.nutzer') }}" class="dn-dd-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Nutzerverwaltung
                            </a>
                            <a href="{{ route('admin.klassen') }}" class="dn-dd-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                Klassen
                            </a>
                            <a href="{{ route('admin.einladungen') }}" class="dn-dd-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                Einladungen
                            </a>
                        </div>
                    </div>
                @endif
            @endauth
        </div>

        {{-- Right --}}
        <div class="dn-right">
            {{-- Dark mode --}}
            <button class="dn-icon-btn" onclick="dnToggleDark()" title="Dark Mode umschalten" aria-label="Dark Mode">
                <svg id="dn-sun" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg id="dn-moon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>

            {{-- User dropdown --}}
            <div class="dn-dd-wrap">
                <button class="dn-user-btn" onclick="dnToggle('userDd', this)" aria-label="Benutzermenü">
                    <div class="dn-avatar">
                        {{ strtoupper(substr(Auth::user()->vorname ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->nachname ?? '', 0, 1)) }}
                    </div>
                    <span class="dn-username">{{ Auth::user()->full_name }}</span>
                    <svg class="dn-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="dn-dd" id="userDd">
                    <a href="{{ route('profile.edit') }}" class="dn-dd-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Mein Profil
                    </a>
                    <div class="dn-dd-sep"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dn-dd-item danger">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>

            {{-- Hamburger --}}
            <button class="dn-hamburger" onclick="dnToggleMobile()" aria-label="Menü öffnen">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="dn-mobile">
        <div class="dn-mobile-header">
            <div class="dn-avatar" style="width:34px;height:34px;font-size:13px;flex-shrink:0">
                {{ strtoupper(substr(Auth::user()->vorname ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->nachname ?? '', 0, 1)) }}
            </div>
            <div>
                <div class="dn-mobile-name">{{ Auth::user()->full_name }}</div>
                <div class="dn-mobile-email">{{ Auth::user()->email }}</div>
            </div>
        </div>
        <div class="dn-mobile-sep"></div>
        <a href="{{ route('dashboard') }}" class="dn-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        @auth
            @if(auth()->user()->rolle === 'azubi')
                <a href="{{ route('wochenbericht.index') }}" class="dn-link {{ request()->routeIs('wochenbericht.*') ? 'active' : '' }}">Wochenberichte</a>
                <a href="{{ route('aufgaben.index') }}" class="dn-link {{ request()->routeIs('aufgaben.*') ? 'active' : '' }}">Aufgaben</a>
                <a href="{{ route('kalender.index') }}" class="dn-link {{ request()->routeIs('kalender.*') ? 'active' : '' }}">Kalender</a>
            @endif
            @if(auth()->user()->rolle === 'ausbilder')
                <a href="{{ route('wochenbericht.index') }}" class="dn-link {{ request()->routeIs('wochenbericht.*') ? 'active' : '' }}">Wochenberichte</a>
            @endif
            @if(auth()->user()->rolle === 'admin')
                <a href="{{ route('admin.nutzer') }}" class="dn-link">Nutzerverwaltung</a>
                <a href="{{ route('admin.klassen') }}" class="dn-link">Klassen</a>
                <a href="{{ route('admin.einladungen') }}" class="dn-link">Einladungen</a>
            @endif
        @endauth
        <div class="dn-mobile-sep"></div>
        <a href="{{ route('profile.edit') }}" class="dn-link">Mein Profil</a>
        <button onclick="dnToggleDark()" class="dn-link" style="width:100%;text-align:left">Dark Mode</button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dn-link" style="width:100%;text-align:left;color:var(--nav-danger)">Abmelden</button>
        </form>
    </div>
</nav>

<script>
(function () {
    function applyDark(on) {
        document.documentElement.setAttribute('data-theme', on ? 'dark' : '');
        const sun  = document.getElementById('dn-sun');
        const moon = document.getElementById('dn-moon');
        if (sun)  sun.style.display  = on ? 'block' : 'none';
        if (moon) moon.style.display = on ? 'none'  : 'block';
    }

    // Apply from localStorage immediately (prevents flash on page load)
    (function() {
        const stored = localStorage.getItem('dan_dark');
        @auth
        const serverPref = {{ auth()->user()->dark_mode ? 'true' : 'false' }};
        // If no localStorage entry yet, seed from server preference
        if (stored === null) {
            localStorage.setItem('dan_dark', serverPref ? '1' : '0');
            applyDark(serverPref);
        } else {
            applyDark(stored === '1');
        }
        @else
        if (stored === '1') applyDark(true);
        @endauth
    })();

    window.dnToggleDark = function () {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const next = !isDark;
        applyDark(next);
        localStorage.setItem('dan_dark', next ? '1' : '0');
        const cb = document.getElementById('dark_mode_toggle');
        if (cb) cb.checked = next;
        // Persist to server (fire & forget)
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('dark_mode', next ? '1' : '0');
            fd.append('_token', token.content);
            fetch('{{ route("profile.darkmode") }}', { method: 'POST', body: fd }).catch(() => {});
        }
    };

    window.dnToggle = function (id, trigger) {
        const el = document.getElementById(id);
        const isOpen = el.classList.contains('dn-open');
        document.querySelectorAll('.dn-dd').forEach(d => d.classList.remove('dn-open'));
        document.querySelectorAll('.dn-chevron').forEach(c => c.classList.remove('flipped'));
        if (!isOpen) {
            el.classList.add('dn-open');
            const chevron = trigger?.querySelector('.dn-chevron');
            if (chevron) chevron.classList.add('flipped');
        }
    };

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dn-dd-wrap') && !e.target.closest('.dn-dd-wrap')) return;
        if (e.target.closest('.dn-dd')) return;
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dn-dd-wrap')) {
            document.querySelectorAll('.dn-dd').forEach(d => d.classList.remove('dn-open'));
            document.querySelectorAll('.dn-chevron').forEach(c => c.classList.remove('flipped'));
        }
    });

    window.dnToggleMobile = function () {
        document.getElementById('dn-mobile').classList.toggle('dn-open');
    };
})();
</script>
