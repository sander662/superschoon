<?php
/** Super Schoon – intern dashboard. */
declare(strict_types=1);
define('SUPERSCHOON', true);
require __DIR__ . '/auth.php';

$fout = '';
if (isset($_GET['uit'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ./'); exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['wachtwoord'])) {
    if (!dash_mag_proberen()) $fout = 'Te veel pogingen. Probeer het binnen 15 minuten opnieuw.';
    elseif (dash_aanmelden((string)$_POST['wachtwoord'])) { header('Location: ./'); exit; }
    else $fout = 'Dat wachtwoord klopt niet.';
}
$v = fn($f) => $f . '?v=' . substr(md5((string)@filemtime(__DIR__ . '/' . $f)), 0, 8);
?><!doctype html>
<html lang="nl-BE">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Dashboard | Super Schoon</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= $v('dashboard.css') ?>">
</head>
<body>
<?php if (!dash_ingelogd()): ?>
<main class="login">
  <form method="post" class="login-kaart" autocomplete="on">
    <img src="/assets/img/logo-superschoon.svg" alt="Super Schoon" width="220" height="37">
    <h1>Dashboard</h1>
    <p class="zacht">Alleen voor intern gebruik.</p>
    <label for="wachtwoord">Wachtwoord</label>
    <input type="password" id="wachtwoord" name="wachtwoord" autocomplete="current-password" required autofocus>
    <?php if ($fout): ?><p class="fout" role="alert"><?= htmlspecialchars($fout) ?></p><?php endif; ?>
    <button type="submit" class="knop">Aanmelden</button>
  </form>
</main>
<?php else: dash_intern_cookie(); ?>
<header class="kop">
  <div class="kop-in">
    <a class="merk" href="/" target="_blank" rel="noopener"><img src="/assets/img/logo-superschoon-wit.svg" alt="Super Schoon" width="180" height="31"></a>
    <span class="kop-titel">Dashboard</span>
    <span class="live" id="live" title="Bezoekers in de laatste 5 minuten"><i></i><b>–</b> nu op de site</span>
    <nav class="kop-links">
      <a href="#offertes-paneel">Offertes</a>
      <a href="offerte-maken.php">Nieuwe offerte</a>
      <a href="teamleader.php">Offertes &amp; Teamleader</a>
      <a href="#vindbaarheid">Vindbaarheid</a>
      <a href="#instellingen">Instellingen</a>
      <a href="?uit=1">Afmelden</a>
    </nav>
  </div>
</header>
<main class="wrap" data-csrf="<?= htmlspecialchars(dash_csrf()) ?>" id="app">
  <div class="filters" role="group" aria-label="Periode">
    <div class="seg" id="periodes">
      <button type="button" data-d="1">Vandaag</button>
      <button type="button" data-d="7">7 dagen</button>
      <button type="button" data-d="30" aria-pressed="true">30 dagen</button>
      <button type="button" data-d="90">90 dagen</button>
      <button type="button" data-d="365">12 maanden</button>
    </div>
    <div class="datums">
      <label>Van <input type="date" id="van"></label>
      <label>Tot <input type="date" id="tot"></label>
    </div>
    <p class="zacht" id="periodetekst"></p>
  </div>

  <section class="kpis" id="kpis" aria-label="Kerncijfers"></section>

  <section class="paneel breed">
    <div class="paneel-kop"><h2>Bezoekers per dag</h2><p class="zacht">Beweeg over de grafiek voor de cijfers per dag.</p></div>
    <div class="grafiek" id="g-bezoekers"></div>
    <details class="tabelview"><summary>Toon als tabel</summary><div id="t-bezoekers"></div></details>
  </section>

  <section class="paneel breed">
    <div class="paneel-kop"><h2>Aanvragen per dag</h2><p class="zacht">Offerteaanvragen en contactberichten samen.</p></div>
    <div class="grafiek klein" id="g-aanvragen"></div>
  </section>

  <div class="rooster">
    <section class="paneel"><h2>Waar komen bezoekers vandaan?</h2><div id="bronnen"></div><h3>Verwijzende websites</h3><div id="verwijzers"></div></section>
    <section class="paneel"><h2>Toestel</h2><div id="toestellen"></div><h3>Instappagina's</h3><div id="instap"></div></section>
    <section class="paneel"><h2>Meest bekeken pagina's</h2><div id="paginas"></div></section>
    <section class="paneel"><h2>Gemeentepagina's</h2><p class="zacht">Unieke bezoekers per gemeentepagina.</p><div id="gemeentes"></div></section>
    <section class="paneel"><h2>Kliks</h2><div id="kliksoorten"></div><h3>Meest aangeklikt</h3><div id="kliks"></div></section>
    <section class="paneel"><h2>Prijsberekening</h2><p class="zacht">Van bezoek tot aanvraag (aantal bezoeken).</p><div id="trechter"></div><div id="calcfeiten"></div></section>
    <section class="paneel"><h2>Aanvragen per bron</h2><div id="perbron"></div><h3>Per gemeente</h3><div id="pergemeente"></div></section>
    <section class="paneel"><h2>Kortingscodes</h2><div id="kcodes"></div></section>
  </div>

  <section class="paneel breed">
    <div class="paneel-kop"><h2>Laatste offerteaanvragen</h2><p class="zacht">Zonder persoonsgegevens. De volledige aanvraag staat in je mailbox.</p></div>
    <div class="tabel-wrap" id="recent"></div>
  </section>

  <section class="paneel breed" id="offertes-paneel">
    <div class="paneel-kop"><h2>Online offertes</h2><p class="zacht">Offertes via de prijsberekening en offertes die je zelf opmaakte, met hun status. <a class="knop" href="offerte-maken.php" style="margin-left:8px">+ Nieuwe offerte</a></p></div>
    <div id="offertes-feiten"></div>
    <div class="tabel-wrap" id="offertes"></div>
  </section>

  <section class="paneel breed" id="vindbaarheid">
    <div class="paneel-kop"><h2>Vindbaarheid</h2><p class="zacht">De SEO-score wordt bij elke publicatie van de website opnieuw berekend.</p></div>
    <div class="seo-top">
      <div class="seo-score" id="seoscore"></div>
      <div id="seochecks"></div>
    </div>
    <h3>Pagina's met verbeterpunten</h3>
    <div class="tabel-wrap" id="seopaginas"></div>

    <h3>Snelheid en kwaliteit (Google PageSpeed)</h3>
    <div class="psi-bediening">
      <select id="psi-url" aria-label="Pagina">
        <option value="https://www.superschoon.be/">Home</option>
        <option value="https://www.superschoon.be/prijsberekening.html">Prijsberekening</option>
        <option value="https://www.superschoon.be/werkwijze.html">Werkwijze</option>
        <option value="https://www.superschoon.be/terrasreiniging-zemst.html">Gemeentepagina (Zemst)</option>
      </select>
      <select id="psi-toestel" aria-label="Toestel"><option value="mobile">Gsm</option><option value="desktop">Computer</option></select>
      <button type="button" class="knop" id="psi-knop">Nu meten</button>
      <span class="zacht" id="psi-status"></span>
    </div>
    <div class="psi-scores" id="psi-scores"></div>
    <div class="tabel-wrap" id="psi-historiek"></div>

    <h3>Zoekresultaten in Google en Bing</h3>
    <p>Hoe vaak je in Google verschijnt, op welke zoekwoorden en op welke positie, zie je in Search Console.</p>
    <div class="knoppen">
      <a class="knop licht" href="https://search.google.com/search-console?resource_id=https%3A%2F%2Fwww.superschoon.be%2F" target="_blank" rel="noopener">Google Search Console</a>
      <a class="knop licht" href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a>
      <a class="knop licht" href="https://www.google.com/search?q=site%3Asuperschoon.be" target="_blank" rel="noopener">Pagina's in Google</a>
    </div>
  </section>

  <section class="paneel breed" id="instellingen">
    <h2>Instellingen</h2>
    <p>Deze browser wordt niet meegeteld in de statistieken zolang je hier af en toe aanmeldt.</p>
    <form id="ww-form" class="ww">
      <h3>Wachtwoord wijzigen</h3>
      <label>Huidig wachtwoord <input type="password" id="ww-oud" autocomplete="current-password" required></label>
      <label>Nieuw wachtwoord (min. 12 tekens) <input type="password" id="ww-nieuw" autocomplete="new-password" minlength="12" required></label>
      <button type="submit" class="knop">Opslaan</button>
      <p class="zacht" id="ww-msg" role="status"></p>
    </form>
    <p class="zacht" id="sinds"></p>
  </section>
</main>
<div class="tip" id="tip" role="tooltip" hidden></div>
<script src="<?= $v('dashboard.js') ?>"></script>
<?php endif; ?>
</body>
</html>
