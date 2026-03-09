<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolle = $_SESSION['rolle'] ?? '';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include_once __DIR__ . '/db.php';

// Lazy-Lock: falls Taskplaner ausfüllt/offline, beim ersten Aufruf (max. alle 30 Min) vergangene Wochen automatisch sperren
require_once __DIR__ . '/wochenbericht_lock_core.php';
$lockStampFile = dirname(__DIR__) . '/uploads/.wochenbericht_lock_last';
$lockInterval  = 1800; // Sekunden
$nowTs = time();
$lastRun = is_file($lockStampFile) ? (int)@file_get_contents($lockStampFile) : 0;
if ($nowTs - $lastRun >= $lockInterval) {
    try {
        runWochenberichtLock($conn, null, null);
        @file_put_contents($lockStampFile, (string)$nowTs);
    } catch (Throwable $e) {
        // stillschweigend ignorieren, UI nicht blockieren
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DAN Plattform</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="css/dan-base.css" rel="stylesheet">
<link href="css/dan-layout.css" rel="stylesheet">
<link href="css/icon-buttons.css" rel="stylesheet">

<script>
// Initiales Theme aus localStorage setzen, bevor die Seite sichtbar wird
(function() {
    try {
        var stored = localStorage.getItem('danTheme');
        if (stored === 'dark') {
            document.documentElement.classList.add('theme-dark');
            document.documentElement.dataset.theme = 'dark';
        } else {
            document.documentElement.classList.remove('theme-dark');
            document.documentElement.dataset.theme = 'light';
        }
    } catch (e) {
        // localStorage evtl. nicht verfügbar
    }
})();
</script>

<style>
/* Basis */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Inter', sans-serif;
    background:#f4f6f8;
    color:#333;
    line-height:1.6;
    padding-top:96px; /* Platz für fixe Navbar */
}

/* Navbar */
nav {
    position: fixed;
    top:0; left:0;
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0.75rem 2rem;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    z-index:1000;
    transition: box-shadow 0.3s ease, background 0.3s ease;
}

/* Logo */
.logo {
    display:flex;
    align-items:center;
    gap:0.5rem;
    pointer-events:none;
}
.logo img { height:48px; width:auto; border-radius:6px; transition: transform 0.3s ease; }
.logo img:hover { transform: scale(1.05); }
.logo span { font-weight:700; font-size:1.3rem; color:#0056b3; letter-spacing:0.5px; }

/* Nav Links */
.nav-links { 
    display:flex; 
    gap:1.8rem; 
    font-weight:600; 
    align-items:center;
}

/* Fix: Kein Bold-Flackern mehr */
.nav-links a {
    text-decoration:none;
    color:#333;
    font-weight:600; /* fixiertes Gewicht */
    padding:0.4rem 0.6rem;
    border-radius:6px;
    transition: background 0.25s ease, color 0.25s ease;
}

.nav-links a:hover {
    background: linear-gradient(90deg, #0056b3, #00aaff);
    color:#fff;
}

.nav-links .active {
    background:#0056b3; 
    color:#fff;
}

/* Mobile */
@media(max-width:768px){
    nav { flex-direction:column; align-items:flex-start; padding:1rem 2rem; }
    .nav-links { flex-direction:column; gap:1rem; margin-top:0.75rem; width:100%; }
}


/* Subheader — direkt unter Navbar */
.subheader {
    position: fixed;
    top: 0; /* wird gleich mit JS überschrieben */
    left: 0;
    width: 100%;
    
    padding: 0.75rem 2rem;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(10px);

    border-bottom: 1px solid #e3e8ef;
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);

    z-index: 900;
}

/* Container */
.subheader-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Breadcrumb */
.subheader .breadcrumb {
    margin: 0;
    padding: 0;
    display: flex;
    gap: 0.35rem;
    align-items: center;

    background: transparent;

    font-size: 0.95rem;
    font-weight: 500;
    color: #5a6a78;
}

/* breadcrumb links */
.subheader .breadcrumb a {
    text-decoration: none;
    color: #0073e6;
}
.subheader .breadcrumb a:hover {
    color: #0056b3;
    text-decoration: underline;
}

/* > Divider */
.breadcrumb .divider {
    font-size: 0.9rem;
    opacity: 0.45;
    margin: 0 0.2rem;
    display: inline-flex;
}

/* current page */
.breadcrumb .current {
    font-weight: 600;
    color: #333;
}


/* Content */
main { padding:2rem 2rem 2rem; max-width:1200px; margin:0 auto; display:flex; flex-direction:column; gap:1.5rem; }

.card {
    background:#fff;
    border-radius:12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    padding:1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Logout Button */
.logout-btn {
  display:flex;
  align-items:center;
  justify-content:flex-start;
  width:45px;
  height:45px;
  border:none;
  border-radius:50%;
  cursor:pointer;
  position:relative;
  overflow:hidden;
  transition:.3s;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
  background-color:#0056b3;
}

.logout-btn .sign {
  width:100%;
  transition:.3s;
  display:flex;
  align-items:center;
  justify-content:center;
}

.logout-btn .sign svg {
  width:18px;
}

.logout-btn .sign svg path {
  fill:white;
}

.logout-btn .text {
  position:absolute;
  right:0%;
  width:0%;
  opacity:0;
  color:white;
  font-size:1.1em;
  font-weight:600;
  transition:.3s;
}

.logout-btn:hover {
  width:130px;
  border-radius:40px;
  background:linear-gradient(90deg,#0056b3,#00aaff);
}

.logout-btn:hover .sign {
  width:30%;
  padding-left:12px;
}

.logout-btn:hover .text {
  opacity:1;
  width:70%;
  padding-right:10px;
}

.logout-btn:active {
  transform:translate(1px,1px);
}
</style>

<style>
:root.theme-dark body {
    background:#111827;
    color:#e5e7eb;
}

:root.theme-dark nav {
    background: rgba(17,24,39,0.9);
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
}

:root.theme-dark .nav-links a {
    color:#e5e7eb;
}

:root.theme-dark .nav-links a:hover {
    background: linear-gradient(90deg, #2563eb, #22d3ee);
    color:#0b1220;
}

:root.theme-dark .nav-links .active {
    background:#2563eb;
    color:#0b1220;
}

:root.theme-dark .card {
    background:#1f2937;
    color:#e5e7eb;
    box-shadow: 0 6px 18px rgba(0,0,0,0.35);
}

:root.theme-dark .subheader {
    background: rgba(17,24,39,0.9);
    border-color:#1f2937;
}

:root.theme-dark .breadcrumb a {
    color:#93c5fd;
}

:root.theme-dark .logout-btn {
    box-shadow: 0 4px 16px rgba(0,0,0,0.4);
}
</style>
</head>
<body>

<!-- Navbar -->
<nav id="navbar">
    <div class="logo">
        <img src="/DAN/assets/logo_w.png" alt="DAN Logo">
        <span>Digitaler Ausbildungs-Nachweis</span>
    </div>

    <div class="nav-links">

        <!-- Immer -->
        <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>">Startseite</a>

        <!-- Nur Azubi -->
        <?php if($rolle === 'azubi'): ?>
            <a href="aufgaben.php" class="<?= $current_page=='aufgaben.php'?'active':'' ?>">Aufgaben</a>
            <a href="wochenbericht.php" class="<?= $current_page=='wochenbericht.php'?'active':'' ?>">Ausbildungsnachweis</a>
        <?php endif; ?>

        <!-- Nur Ausbilder -->
        <?php if($rolle === 'ausbilder'): ?>
            <a href="abteilungen.php" class="<?= $current_page=='abteilungen.php'?'active':'' ?>">Abteilungen</a>
            <a href="klassen_verwalten.php" class="<?= in_array($current_page, ['klassen_verwalten.php','klassen.php']) ? 'active' : '' ?>">Klassen</a>
            <a href="wochenbericht_ausbilder.php" class="<?= $current_page=='wochenbericht_ausbilder.php'?'active':'' ?>">Wochenberichte</a>
        <?php endif; ?>

        <!-- Immer -->
        <a href="account.php" class="<?= $current_page=='account.php'?'active':'' ?>">Mein Profil</a>

        <!-- Logout Button -->
        <form action="logout.php" method="post" style="margin:0;">
            <button class="logout-btn" type="submit">
                <span class="sign">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 17L10 14L14 14L14 10L10 10L10 7L5 12L10 17Z"/>
                        <path d="M19 3H11V5H19V19H11V21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3Z"/>
                    </svg>
                </span>
                <span class="text">Logout</span>
            </button>
        </form>

    </div>
</nav>

<?php $breadcrumbs_html = $page_breadcrumbs ?? ''; ?>
<?php if (!empty($breadcrumbs_html)): ?>
<div class="subheader">
    <div class="subheader-inner">
        <?= $breadcrumbs_html ?>
    </div>
</div>
<?php endif; ?>

<main>
    <div class="card">
        <?= $page_content ?>
    </div>
</main>

<script>
const nav = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    nav.style.boxShadow = window.scrollY > 10
        ? '0 10px 25px rgba(0,0,0,0.12)'
        : '0 8px 20px rgba(0,0,0,0.08)';
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const navbar = document.getElementById("navbar");
    const subheader = document.querySelector(".subheader");

    if (navbar && subheader) {
        const navbarHeight = navbar.offsetHeight;
        subheader.style.top = navbarHeight + "px";
    }
});
</script>

<?php if (!empty($page_scripts ?? '')): ?>
<?= $page_scripts ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>





