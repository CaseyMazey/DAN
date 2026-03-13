<x-app-layout>

<x-slot name="header">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="font-weight:600; font-size:1.1rem; margin:0; color:inherit;">Mein Profil</h2>
    </div>
</x-slot>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
        /* ─── Profile-page scoped variables ─── */
        :root {
            --p-blue:     #2563EB;
            --p-blue-h:   #1D4ED8;
            --p-blue-50:  #EBF3FF;
            --p-blue-100: #C2D9FF;
            --p-blue-400: #3B82F6;
            --p-surface:  #ffffff;
            --p-surface-2:#F0F4FA;
            --p-bg:       #F7F9FC;
            --p-text:     #1A2233;
            --p-muted:    #64748B;
            --p-border:   #E2E8F0;
            --p-shadow:   0 1px 3px rgba(15,23,42,.06), 0 4px 16px rgba(15,23,42,.06);
            --p-r:        10px;
            --p-rsm:      6px;
        }
        html[data-theme="dark"] {
            --p-blue:     #58A6FF;
            --p-blue-h:   #79B8FF;
            --p-blue-50:  #0D2340;
            --p-blue-100: #1A3A6B;
            --p-blue-400: #58A6FF;
            --p-surface:  #161B22;
            --p-surface-2:#21262D;
            --p-bg:       #0D1117;
            --p-text:     #E6EDF3;
            --p-muted:    #8B949E;
            --p-border:   #30363D;
            --p-shadow:   0 1px 4px rgba(0,0,0,.3), 0 4px 16px rgba(0,0,0,.3);
        }

        /* ─── Page layout ─── */
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--p-bg);
        }

        .profil-page {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 0;
            max-width: 1100px;
            margin: 0 auto;
            min-height: calc(100vh - 65px);
        }

        /* ─── Left subnav ─── */
        .profil-subnav {
            background: var(--p-surface);
            border-right: 1px solid var(--p-border);
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            position: sticky;
            top: 65px;
            max-height: calc(100vh - 65px);
            overflow-y: auto;
            align-self: start;
        }
        .profil-subnav-section {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--p-muted);
            padding: 10px 8px 4px;
            opacity: .75;
        }
        .profil-subnav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: var(--p-rsm);
            font-size: 13.5px;
            color: var(--p-muted);
            text-decoration: none;
            transition: all .15s;
            position: relative;
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            width: 100%;
        }
        .profil-subnav-item:hover {
            background: var(--p-surface-2);
            color: var(--p-text);
        }
        .profil-subnav-item.active {
            background: var(--p-blue-50);
            color: var(--p-blue);
            font-weight: 500;
        }
        .profil-subnav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 4px; bottom: 4px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--p-blue);
        }

        /* ─── Content area ─── */
        .profil-content {
            padding: 28px 32px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 760px;
        }

        /* ─── Page heading ─── */
        .profil-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 4px;
        }
        .profil-heading-avatar {
            width: 50px; height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--p-blue) 0%, #60A5FA 100%);
            color: #fff;
            font-size: 17px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            letter-spacing: .5px;
        }
        .profil-heading h1 {
            font-size: 20px;
            font-weight: 600;
            color: var(--p-text);
            margin: 0;
        }
        .profil-heading p {
            font-size: 13px;
            color: var(--p-muted);
            margin: 2px 0 0;
        }
        .profil-heading p span {
            color: var(--p-blue);
            font-weight: 500;
        }

        /* ─── Section cards ─── */
        .profil-card {
            background: var(--p-surface);
            border: 1px solid var(--p-border);
            border-radius: var(--p-r);
            box-shadow: var(--p-shadow);
            overflow: hidden;
            scroll-margin-top: 80px;
        }
        .profil-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--p-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .profil-card-icon {
            width: 32px; height: 32px;
            border-radius: 7px;
            background: var(--p-blue-50);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .profil-card-icon svg { display: block; }
        .profil-card-header-text h2 {
            font-size: 14px;
            font-weight: 600;
            color: var(--p-text);
            margin: 0;
        }
        .profil-card-header-text p {
            font-size: 12px;
            color: var(--p-muted);
            margin: 2px 0 0;
        }
        .profil-card-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ─── Form fields ─── */
        .profil-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .profil-field label {
            font-size: 12px;
            font-weight: 500;
            color: var(--p-muted);
            letter-spacing: .2px;
        }
        .profil-field input,
        .profil-field select {
            padding: 9px 12px;
            border: 1px solid var(--p-border);
            border-radius: var(--p-rsm);
            background: var(--p-surface);
            color: var(--p-text);
            font-size: 13.5px;
            font-family: 'DM Sans', inherit;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            width: 100%;
        }
        .profil-field input:focus,
        .profil-field select:focus {
            border-color: var(--p-blue-400);
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }
        .profil-field input[readonly] {
            background: var(--p-surface-2);
            color: var(--p-muted);
            cursor: default;
        }
        .profil-field input[type="date"] {
            color-scheme: light;
        }
        html[data-theme="dark"] .profil-field input[type="date"] {
            color-scheme: dark;
        }
        .profil-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* ─── Buttons ─── */
        .profil-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--p-rsm);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            border: 1px solid transparent;
            font-family: 'DM Sans', inherit;
            text-decoration: none;
        }
        .profil-btn-primary {
            background: var(--p-blue);
            color: #fff;
            border-color: var(--p-blue-h);
        }
        .profil-btn-primary:hover { background: var(--p-blue-h); color: #fff; }
        .profil-btn-outline {
            background: transparent;
            color: var(--p-text);
            border-color: var(--p-border);
        }
        .profil-btn-outline:hover { background: var(--p-surface-2); }
        .profil-btn-sm { padding: 5px 12px; font-size: 12px; }
        .profil-footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 4px;
        }

        /* ─── Password strength ─── */
        .profil-pw-strength {
            display: flex;
            gap: 4px;
            margin-top: 4px;
        }
        .profil-pw-bar {
            flex: 1; height: 3px;
            border-radius: 99px;
            background: var(--p-border);
            transition: background .2s;
        }
        .profil-pw-bar.ok   { background: #22C55E; }
        .profil-pw-bar.warn { background: #F59E0B; }

        /* ─── Toggle switch ─── */
        .profil-toggle-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .profil-toggle-wrap {
            position: relative;
            width: 40px; height: 22px;
            flex-shrink: 0;
        }
        .profil-toggle-wrap input {
            opacity: 0; width: 0; height: 0;
            position: absolute;
        }
        .profil-toggle-slider {
            position: absolute; inset: 0;
            background: var(--p-border);
            border-radius: 99px;
            cursor: pointer;
            transition: background .2s;
        }
        .profil-toggle-slider::after {
            content: '';
            position: absolute;
            top: 3px; left: 3px;
            width: 16px; height: 16px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
        }
        .profil-toggle-wrap input:checked + .profil-toggle-slider {
            background: var(--p-blue);
        }
        .profil-toggle-wrap input:checked + .profil-toggle-slider::after {
            transform: translateX(18px);
        }
        .profil-toggle-label { font-size: 13.5px; color: var(--p-text); }
        .profil-toggle-sub   { font-size: 12px; color: var(--p-muted); margin-top: 1px; }

        /* ─── Status alerts ─── */
        .profil-alert {
            padding: 10px 14px;
            border-radius: var(--p-rsm);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 3px solid;
        }
        .profil-alert-success {
            background: #F0FDF4;
            color: #166534;
            border-left-color: #22C55E;
        }
        .profil-alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border-left-color: #EF4444;
        }

        /* ─── Divider ─── */
        .profil-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--p-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .profil-divider::before,
        .profil-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--p-border);
        }

        /* ─── Signature ─── */
        .profil-sig-current {
            border: 1px solid var(--p-border);
            border-radius: var(--p-rsm);
            background: var(--p-surface-2);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 90px;
        }
        .profil-sig-current img {
            max-height: 70px;
            max-width: 100%;
        }
        .profil-sig-no-sig {
            color: var(--p-muted);
            font-size: 13px;
        }
        .profil-sig-tabs {
            display: flex;
            border: 1px solid var(--p-border);
            border-radius: var(--p-rsm);
            overflow: hidden;
            width: fit-content;
        }
        .profil-sig-tab {
            padding: 6px 16px;
            font-size: 12.5px;
            font-weight: 500;
            background: transparent;
            color: var(--p-muted);
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', inherit;
            transition: all .15s;
        }
        .profil-sig-tab + .profil-sig-tab {
            border-left: 1px solid var(--p-border);
        }
        .profil-sig-tab.active {
            background: var(--p-blue-50);
            color: var(--p-blue);
        }
        .profil-sig-canvas-wrap {
            border: 1.5px dashed var(--p-border);
            border-radius: var(--p-rsm);
            background: var(--p-surface-2);
            position: relative;
            overflow: hidden;
            cursor: crosshair;
        }
        .profil-sig-canvas-wrap canvas {
            display: block;
            width: 100%;
            height: 140px;
            touch-action: none;
        }
        .profil-sig-hint {
            position: absolute; inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12.5px;
            color: var(--p-muted);
            pointer-events: none;
            transition: opacity .2s;
        }
        .profil-sig-upload-area {
            border: 1.5px dashed var(--p-border);
            border-radius: var(--p-rsm);
            background: var(--p-surface-2);
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .15s;
        }
        .profil-sig-upload-area:hover {
            border-color: var(--p-blue-400);
        }
        .profil-sig-upload-icon {
            width: 38px; height: 38px;
            border-radius: 8px;
            background: var(--p-blue-50);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profil-sig-upload-text {
            font-size: 13px;
            color: var(--p-muted);
        }
        .profil-sig-upload-text strong {
            color: var(--p-blue);
            font-weight: 500;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .profil-page {
                grid-template-columns: 1fr;
            }
            .profil-subnav {
                position: static;
                height: auto;
                flex-direction: row;
                flex-wrap: wrap;
                border-right: none;
                border-bottom: 1px solid var(--p-border);
            }
            .profil-subnav-section { display: none; }
            .profil-content { padding: 20px 16px; }
            .profil-field-row { grid-template-columns: 1fr; }
        }
    </style>

<div class="profil-page">

    {{-- ── LEFT SUBNAV ── --}}
    <nav class="profil-subnav" aria-label="Profil-Navigation">
        <div class="profil-subnav-section">Profil</div>

        <a href="#sec-personal" class="profil-subnav-item active" data-section="sec-personal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Persönliche Daten
        </a>
        <a href="#sec-pw" class="profil-subnav-item" data-section="sec-pw">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Passwort
        </a>
        <a href="#sec-settings" class="profil-subnav-item" data-section="sec-settings">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Einstellungen
        </a>
        <a href="#sec-sig" class="profil-subnav-item" data-section="sec-sig">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 17l4-8 4 4 4-6 4 10"/></svg>
            Meine Signatur
        </a>
    </nav>

    {{-- ── MAIN CONTENT ── --}}
    <main class="profil-content">

        {{-- Page heading --}}
        <div class="profil-heading">
            <div class="profil-heading-avatar" aria-hidden="true">
                {{ strtoupper(substr(auth()->user()->vorname, 0, 1)) }}{{ strtoupper(substr(auth()->user()->nachname, 0, 1)) }}
            </div>
            <div>
                <h1>{{ auth()->user()->full_name }}</h1>
                <p>{{ auth()->user()->abteilung ?? 'Keine Abteilung' }} · <span>{{ ucfirst(auth()->user()->rolle) }}</span></p>
            </div>
        </div>

        {{-- ── SECTION: PERSÖNLICHE DATEN ── --}}
        <section class="profil-card" id="sec-personal">
            <div class="profil-card-header">
                <div class="profil-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="profil-card-header-text">
                    <h2>Persönliche Daten</h2>
                    <p>Name, E-Mail und Ausbildungszeitraum</p>
                </div>
            </div>
            <div class="profil-card-body">

                {{-- Session feedback --}}
                @if (session('status') === 'profile-updated')
                    <div class="profil-alert profil-alert-success" role="alert">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Profil erfolgreich gespeichert.
                    </div>
                @endif

                @if ($errors->any())
                    <div class="profil-alert profil-alert-error" role="alert">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="profil-field-row" style="margin-bottom:16px">
                        <div class="profil-field">
                            <label for="vorname">Vorname</label>
                            <input type="text" id="vorname" name="vorname"
                                   value="{{ old('vorname', auth()->user()->vorname) }}"
                                   required autocomplete="given-name"/>
                            @error('vorname')
                                <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="profil-field">
                            <label for="nachname">Nachname</label>
                            <input type="text" id="nachname" name="nachname"
                                   value="{{ old('nachname', auth()->user()->nachname) }}"
                                   required autocomplete="family-name"/>
                            @error('nachname')
                                <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="profil-field" style="margin-bottom:16px">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email', auth()->user()->email) }}"
                               required autocomplete="email"/>
                        @error('email')
                            <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                        @enderror
                    </div>

                    @if (auth()->user()->rolle === 'azubi')
                        <div class="profil-field-row" style="margin-bottom:16px">
                            <div class="profil-field">
                                <label for="ausbildungsbeginn">Ausbildungsbeginn</label>
                                <input type="date" id="ausbildungsbeginn" name="ausbildungsbeginn"
                                       value="{{ old('ausbildungsbeginn', auth()->user()->ausbildungsbeginn?->format('Y-m-d')) }}"/>
                                @error('ausbildungsbeginn')
                                    <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="profil-field">
                                <label for="ausbildungsende">Ausbildungsende</label>
                                <input type="date" id="ausbildungsende" name="ausbildungsende"
                                       value="{{ old('ausbildungsende', auth()->user()->ausbildungsende?->format('Y-m-d')) }}"/>
                                @error('ausbildungsende')
                                    <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <div class="profil-field-row" style="margin-bottom:4px">
                        <div class="profil-field">
                            <label>Rolle</label>
                            <input type="text" value="{{ ucfirst(auth()->user()->rolle) }}" readonly/>
                        </div>
                        <div class="profil-field">
                            <label for="abteilung">Abteilung</label>
                            <input type="text" id="abteilung" name="abteilung"
                                   value="{{ old('abteilung', auth()->user()->abteilung) }}"
                                   placeholder="z.B. IT Anwendungsentwicklung"/>
                        </div>
                    </div>

                    <div class="profil-footer-actions">
                        <button type="submit" class="profil-btn profil-btn-primary">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            Speichern
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- ── SECTION: PASSWORT ── --}}
        <section class="profil-card" id="sec-pw">
            <div class="profil-card-header">
                <div class="profil-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div class="profil-card-header-text">
                    <h2>Passwort ändern</h2>
                    <p>Mindestens 8 Zeichen empfohlen</p>
                </div>
            </div>
            <div class="profil-card-body">

                @if (session('status') === 'password-updated')
                    <div class="profil-alert profil-alert-success" role="alert">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Passwort erfolgreich geändert.
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="profil-field" style="margin-bottom:16px">
                        <label for="current_password">Aktuelles Passwort</label>
                        <input type="password" id="current_password" name="current_password"
                               autocomplete="current-password" placeholder="••••••••"/>
                        @error('current_password', 'updatePassword')
                            <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="profil-field" style="margin-bottom:16px">
                        <label for="password">Neues Passwort</label>
                        <input type="password" id="password" name="password"
                               autocomplete="new-password" placeholder="••••••••"
                               oninput="danPwStrength(this.value)"/>
                        <div class="profil-pw-strength">
                            <div class="profil-pw-bar" id="pb1"></div>
                            <div class="profil-pw-bar" id="pb2"></div>
                            <div class="profil-pw-bar" id="pb3"></div>
                            <div class="profil-pw-bar" id="pb4"></div>
                        </div>
                        @error('password', 'updatePassword')
                            <span style="font-size:11px;color:#EF4444">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="profil-field" style="margin-bottom:4px">
                        <label for="password_confirmation">Passwort bestätigen</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               autocomplete="new-password" placeholder="••••••••"/>
                    </div>

                    <div class="profil-footer-actions">
                        <button type="submit" class="profil-btn profil-btn-primary">
                            Passwort ändern
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- ── SECTION: EINSTELLUNGEN ── --}}
        <section class="profil-card" id="sec-settings">
            <div class="profil-card-header">
                <div class="profil-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </div>
                <div class="profil-card-header-text">
                    <h2>Einstellungen</h2>
                    <p>Erscheinungsbild & Präferenzen</p>
                </div>
            </div>
            <div class="profil-card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')
                    {{-- Pass through existing values so profile.update doesn't wipe them --}}
                    <input type="hidden" name="vorname"           value="{{ auth()->user()->vorname }}">
                    <input type="hidden" name="nachname"          value="{{ auth()->user()->nachname }}">
                    <input type="hidden" name="email"             value="{{ auth()->user()->email }}">
                    <input type="hidden" name="abteilung"         value="{{ auth()->user()->abteilung }}">
                    <input type="hidden" name="ausbildungsbeginn" value="{{ auth()->user()->ausbildungsbeginn?->format('Y-m-d') }}">
                    <input type="hidden" name="ausbildungsende"   value="{{ auth()->user()->ausbildungsende?->format('Y-m-d') }}">
                    {{-- dark_mode is only sent when checkbox is checked; backend uses boolean() which defaults to false --}}

                    <div class="profil-toggle-row">
                        <label class="profil-toggle-wrap">
                            <input type="checkbox" name="dark_mode" id="dark_mode_toggle"
                                   {{ auth()->user()->dark_mode ? 'checked' : '' }}
                                   onchange="danToggleDarkMode(this.checked)"/>
                            <div class="profil-toggle-slider"></div>
                        </label>
                        <div>
                            <div class="profil-toggle-label">Dark Mode</div>
                            <div class="profil-toggle-sub">Dunkles Farbschema aktivieren</div>
                        </div>
                    </div>

                    <div class="profil-footer-actions" style="padding-top:0">
                        <button type="submit" class="profil-btn profil-btn-primary">
                            Einstellungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- ── SECTION: SIGNATUR ── --}}
        <section class="profil-card" id="sec-sig">
            <div class="profil-card-header">
                <div class="profil-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2"><path d="M3 17l4-8 4 4 4-6 4 10"/></svg>
                </div>
                <div class="profil-card-header-text">
                    <h2>Meine Signatur</h2>
                    <p>Wird auf Wochenberichten angezeigt</p>
                </div>
            </div>
            <div class="profil-card-body">

                {{-- Current signature preview --}}
                <div>
                    <div style="font-size:12px;font-weight:500;color:var(--p-muted);margin-bottom:8px">Aktuelle Signatur</div>
                    <div class="profil-sig-current">
                        @if (auth()->user()->signatur_pfad)
                            <img src="{{ Storage::url(auth()->user()->signatur_pfad) }}"
                                 alt="Deine Signatur"/>
                        @else
                            <span class="profil-sig-no-sig">Noch keine Signatur hinterlegt</span>
                        @endif
                    </div>
                </div>

                <div class="profil-divider">Signatur aktualisieren</div>

                {{-- Tab switcher --}}
                <div class="profil-sig-tabs" role="tablist">
                    <button class="profil-sig-tab active" role="tab" id="tab-draw-btn"
                            onclick="danSwitchSigTab('draw', this)">
                        Zeichnen
                    </button>
                    <button class="profil-sig-tab" role="tab" id="tab-upload-btn"
                            onclick="danSwitchSigTab('upload', this)">
                        Bild hochladen
                    </button>
                </div>

                {{-- Draw tab --}}
                <div id="tab-draw" role="tabpanel">
                    <form method="POST" action="{{ route('profile.signatur') }}" id="sig-canvas-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="signatur_canvas" id="sig-canvas-data"/>

                        <div class="profil-sig-canvas-wrap">
                            <canvas id="sigCanvas"></canvas>
                            <div class="profil-sig-hint" id="sigHint">Hier mit der Maus oder dem Finger unterschreiben</div>
                        </div>

                        <div style="display:flex;gap:8px;margin-top:10px">
                            <button type="button" class="profil-btn profil-btn-outline profil-btn-sm"
                                    onclick="danClearCanvas()">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                Leeren
                            </button>
                            <button type="button" class="profil-btn profil-btn-primary profil-btn-sm"
                                    onclick="danSubmitCanvas()">
                                Signatur übernehmen
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Upload tab --}}
                <div id="tab-upload" role="tabpanel" style="display:none">
                    <form method="POST" action="{{ route('profile.signatur') }}"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="profil-sig-upload-area"
                             onclick="document.getElementById('sigFile').click()"
                             id="sigDropZone">
                            <div class="profil-sig-upload-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </div>
                            <div class="profil-sig-upload-text">
                                <strong>Klicken zum Hochladen</strong> oder Datei ablegen
                            </div>
                            <div class="profil-sig-upload-text" style="margin-top:4px;font-size:11px">
                                PNG, JPG — max. 2 MB
                            </div>
                            <div id="sigFileName" style="margin-top:8px;font-size:12px;color:var(--p-blue);display:none"></div>
                        </div>

                        <input type="file" id="sigFile" name="signatur_bild"
                               accept="image/png,image/jpeg,image/jpg"
                               style="display:none"
                               onchange="danSigFileChosen(this)"/>

                        <div style="margin-top:10px;display:flex;justify-content:flex-end">
                            <button type="submit" class="profil-btn profil-btn-primary profil-btn-sm">
                                Signatur speichern
                            </button>
                        </div>
                    </form>
                </div>

                @if (session('status') === 'profile-updated' && request()->is('profile'))
                    <div class="profil-alert profil-alert-success" role="alert">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Signatur gespeichert.
                    </div>
                @endif

            </div>
        </section>

    </main>
</div>

<script>
    /* ── Dark mode toggle (instant, form submit syncs to DB) ── */
    function danToggleDarkMode(on) {
        document.documentElement.setAttribute('data-theme', on ? 'dark' : '');
    }
    // Apply on page load from user preference
    document.addEventListener('DOMContentLoaded', function() {
        @if (auth()->user()->dark_mode)
            document.documentElement.setAttribute('data-theme', 'dark');
        @endif
    });

    /* ── Password strength indicator ── */
    function danPwStrength(v) {
        const bars = ['pb1','pb2','pb3','pb4'].map(id => document.getElementById(id));
        const score = Math.min(4, Math.floor(v.length / 3));
        bars.forEach((b, i) => {
            if (!b) return;
            b.className = 'profil-pw-bar';
            if (i < score) b.classList.add(score >= 3 ? 'ok' : 'warn');
        });
    }

    /* ── Signature tab switcher ── */
    function danSwitchSigTab(tab, btn) {
        document.querySelectorAll('.profil-sig-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-draw').style.display   = tab === 'draw'   ? '' : 'none';
        document.getElementById('tab-upload').style.display = tab === 'upload' ? '' : 'none';
    }

    /* ── Signature canvas ── */
    const sigCanvas = document.getElementById('sigCanvas');
    let sigCtx = null, sigDrawing = false;

    function danInitCanvas() {
        if (!sigCanvas) return;
        const rect = sigCanvas.getBoundingClientRect();
        sigCanvas.width  = Math.round(rect.width  * window.devicePixelRatio);
        sigCanvas.height = Math.round(rect.height * window.devicePixelRatio);
        sigCtx = sigCanvas.getContext('2d');
        sigCtx.scale(window.devicePixelRatio, window.devicePixelRatio);
        sigCtx.strokeStyle = document.documentElement.getAttribute('data-theme') === 'dark'
            ? '#E6EDF3' : '#1A2233';
        sigCtx.lineWidth   = 2;
        sigCtx.lineCap     = 'round';
        sigCtx.lineJoin    = 'round';
    }

    function danGetSigPos(e) {
        const rect = sigCanvas.getBoundingClientRect();
        const src  = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    if (sigCanvas) {
        sigCanvas.addEventListener('mousedown', e => {
            sigDrawing = true;
            document.getElementById('sigHint').style.opacity = '0';
            const p = danGetSigPos(e);
            sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y);
        });
        sigCanvas.addEventListener('mousemove', e => {
            if (!sigDrawing) return;
            const p = danGetSigPos(e);
            sigCtx.lineTo(p.x, p.y); sigCtx.stroke();
        });
        sigCanvas.addEventListener('mouseup',    () => sigDrawing = false);
        sigCanvas.addEventListener('mouseleave', () => sigDrawing = false);

        // Touch support
        sigCanvas.addEventListener('touchstart', e => {
            e.preventDefault(); sigDrawing = true;
            document.getElementById('sigHint').style.opacity = '0';
            const p = danGetSigPos(e);
            sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y);
        }, { passive: false });
        sigCanvas.addEventListener('touchmove', e => {
            e.preventDefault();
            if (!sigDrawing) return;
            const p = danGetSigPos(e);
            sigCtx.lineTo(p.x, p.y); sigCtx.stroke();
        }, { passive: false });
        sigCanvas.addEventListener('touchend', () => sigDrawing = false);

        window.addEventListener('load', danInitCanvas);
        window.addEventListener('resize', danInitCanvas);
    }

    function danClearCanvas() {
        if (!sigCtx || !sigCanvas) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        const hint = document.getElementById('sigHint');
        if (hint) hint.style.opacity = '1';
    }

    function danSubmitCanvas() {
        if (!sigCanvas) return;
        const dataURL = sigCanvas.toDataURL('image/png');
        document.getElementById('sig-canvas-data').value = dataURL;
        document.getElementById('sig-canvas-form').submit();
    }

    /* ── Upload file name display ── */
    function danSigFileChosen(input) {
        const label = document.getElementById('sigFileName');
        if (!label) return;
        if (input.files && input.files[0]) {
            label.textContent = input.files[0].name;
            label.style.display = 'block';
        }
    }

    /* ── Subnav active state on scroll ── */
    const danSections = ['sec-personal','sec-pw','sec-settings','sec-sig'];
    const danNavItems = document.querySelectorAll('.profil-subnav-item[data-section]');

    function danUpdateNav() {
        let current = danSections[0];
        danSections.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.getBoundingClientRect().top < 120) current = id;
        });
        danNavItems.forEach(item => {
            item.classList.toggle('active', item.dataset.section === current);
        });
    }

    window.addEventListener('scroll', danUpdateNav, { passive: true });

    /* Smooth scroll for subnav links */
    danNavItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const target = document.getElementById(item.dataset.section);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
</script>

</x-app-layout>
