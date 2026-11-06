<?php
/** Super Schoon – online offerte bekijken, aanvaarden, weigeren of herroepen. */
declare(strict_types=1);
date_default_timezone_set('Europe/Brussels');
define('SUPERSCHOON', true);
require __DIR__ . '/dashboard/offerte_lib.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$o = offerte_laad((string)($_GET['id'] ?? ''));
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$eur = fn($n) => offerte_eur((float)$n);
$d = fn($t) => date('d/m/Y', (int)$t);
$v = fn($f) => $f . '?v=' . substr(md5((string)@filemtime(__DIR__ . '/' . $f)), 0, 8);

if (!$o) {
    http_response_code(404);
    ?><!doctype html><html lang="nl-BE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Offerte niet gevonden | Super Schoon</title><link rel="stylesheet" href="<?= $v('assets/css/offerte.css') ?>"></head>
<body class="leeg"><main class="melding"><img src="/assets/img/logo-superschoon.svg" alt="Super Schoon" width="220" height="37"><h1>Deze offerte vinden we niet</h1>
<p>Controleer of je de volledige link uit je e-mail gebruikt. Lukt het niet, bel ons op <a href="tel:+32468430053">0468 43 00 53</a> of mail naar <a href="mailto:info@ssprojects.be">info@ssprojects.be</a>.</p>
<p><a class="knop groen" href="/prijsberekening.html">Nieuwe prijsberekening</a></p></main></body></html><?php
    exit;
}

$status = offerte_status($o);
$k = $o['klant'];
$voornaam = explode(' ', trim($k['naam']))[0];
$prijsPerM2 = $o['m2'] > 0 ? $o['reiniging'] / $o['m2'] : 0;
$av = @file_get_contents(__DIR__ . '/_bron/av.html') ?: '<p>Zie <a href="/algemene-voorwaarden.html">www.superschoon.be/algemene-voorwaarden.html</a>.</p>';
$av = preg_replace('#<p class="hint">.*?</p>#s', '', $av);
$a = $o['aanvaard'] ?? null;
$herroepTot = $a ? $a['t'] + OFFERTE_HERROEPING_DAGEN * 86400 : null;
$kop = '<div class="pkop"><span><b>Super Schoon</b> by S&amp;S Projects</span><span>Beekveldstraat 1A, 1980 Zemst · 0468 43 00 53 · info@ssprojects.be · BE 0735.883.273</span></div>';
$voet = fn($n) => '<div class="pvoet"><span>Offerte ' . $h($o['nummer']) . '</span><span>' . $n . ' / 5</span></div>';
stats_log('offerte_bekeken', ['p' => '/offerte.php', 'nr' => $o['nummer']]);
?><!doctype html>
<html lang="nl-BE">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title>Offerte <?= $h($o['nummer']) ?> | Super Schoon</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= $v('assets/css/offerte.css') ?>">
</head>
<body data-id="<?= $h($o['token']) ?>" data-status="<?= $h($status) ?>">

<header class="balk">
  <div class="balk-in">
    <span class="merkje" aria-hidden="true">SS</span>
    <div class="balk-titel"><b>Offerte <?= $h($o['nummer']) ?></b><span><?= $status === 'open' ? 'Geldig tot ' . $d($o['geldig_tot']) : ($status === 'aanvaard' ? 'Aanvaard op ' . $d($a['t']) : ucfirst($status)) ?></span></div>
    <div class="balk-acties">
      <button type="button" class="knop licht" id="b-pdf" title="Download of print als pdf"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Pdf</span></button>
      <button type="button" class="knop licht" data-open="d-vraag">Vraag stellen</button>
      <?php if ($status === 'open'): ?>
      <button type="button" class="knop rood" data-open="d-weigeren">Weigeren</button>
      <button type="button" class="knop groen" data-open="d-aanvaarden">Offerte aanvaarden</button>
      <?php elseif ($status === 'aanvaard' && offerte_herroepbaar($o)): ?>
      <button type="button" class="knop licht" data-open="d-herroepen">Herroepen</button>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($status !== 'open'): ?>
<div class="banner <?= $h($status) ?>" role="status"><div class="balk-in">
<?php if ($status === 'aanvaard'): ?>
  <b>Je hebt deze offerte aanvaard op <?= date('d/m/Y \o\m H:i', $a['t']) ?>.</b> We nemen contact met je op om een datum te plannen.
  <?php if (offerte_herroepbaar($o)): ?><span>Je kan nog kosteloos herroepen tot en met <?= $d($herroepTot) ?>.</span><?php endif; ?>
<?php elseif ($status === 'geweigerd'): ?>
  <b>Je hebt deze offerte geweigerd.</b> Bedankt om het ons te laten weten. Van gedachten veranderd? Bel 0468 43 00 53.
<?php elseif ($status === 'herroepen'): ?>
  <b>Je hebt deze overeenkomst herroepen op <?= $d($o['herroepen']['t']) ?>.</b> Er worden geen werken uitgevoerd.
<?php else: ?>
  <b>Deze offerte is verlopen op <?= $d($o['geldig_tot']) ?>.</b> <a href="/prijsberekening.html">Maak een nieuwe prijsberekening</a> of bel ons op 0468 43 00 53.
<?php endif; ?>
</div></div>
<?php endif; ?>

<main class="document" id="document">

  <!-- 1. voorblad -->
  <section class="pagina voorblad" aria-label="Voorblad">
    <div class="vb-boven">
      <p class="vb-label">Offerte</p>
      <img src="/assets/img/logo-superschoon-wit.svg" alt="Super Schoon – reiniging van buitenverhardingen" width="420" height="72" class="vb-logo">
    </div>
    <picture class="vb-foto">
      <source type="image/webp" srcset="/assets/img/oprit-half-gereinigd-800.webp">
      <img src="/assets/img/oprit-half-gereinigd-800.jpg" alt="" width="800" height="1067">
    </picture>
    <div class="vb-onder">
      <div><span>Voor</span><b><?= $h($k['naam']) ?></b><?= $h($k['adres']) ?></div>
      <div><span>Offerte</span><b><?= $h($o['nummer']) ?></b><?= $d($o['aangemaakt']) ?></div>
    </div>
  </section>

  <!-- 2. over ons -->
  <section class="pagina" aria-label="Over Super Schoon">
    <?= $kop ?>
    <div class="pinhoud">
      <h2 class="groot">Grondig proper, zonder schade</h2>
      <p class="lead">Super Schoon is de ecologische reinigingsdienst van tuinaannemer S&amp;S Projects uit Zemst. Wij leggen al meer dan 10 jaar terrassen, opritten en tuinpaden aan. Wie verhardingen zelf aanlegt, weet ook hoe je ze reinigt zonder ze te beschadigen.</p>
      <div class="usps">
        <?php foreach ([['ervaring', 'Meer dan 10 jaar ervaring', 'Wij leggen al meer dan 10 jaar verhardingen aan. We weten dus precies hoe we ze reinigen.'],
                        ['ecologisch', '100% ecologisch', 'Een gepatenteerd borstelsysteem en een beetje water. Geen chemicaliën, zeep, stoom of hogedruk.'],
                        ['alleverhardingen', 'Alle verhardingen', 'Natuursteen, klinkers, tegels, beton en zelfs houten en composiet terrassen.'],
                        ['geenschade', 'Geen schade', 'Je voegen blijven zitten, het oppervlak wordt niet ruw en de oorspronkelijke kleur komt terug.']] as [$i, $t, $x]): ?>
        <div class="usp"><?= @file_get_contents(__DIR__ . "/assets/img/icoon-$i.svg") ?><h3><?= $t ?></h3><p><?= $x ?></p></div>
        <?php endforeach; ?>
      </div>
      <h3>Zo verloopt het</h3>
      <ol class="stappen">
        <li><b>Je aanvaardt de offerte</b> hier online.</li>
        <li><b>We plannen samen een datum</b> en bevestigen de oppervlakte ter plaatse of aan de hand van foto's.</li>
        <li><b>We reinigen</b> met borstels en water, zonder chemie of hogedruk, en laten alles borstelschoon achter.</li>
        <li><b>Je ontvangt de factuur</b> na de uitvoering, te betalen binnen 14 dagen.</li>
      </ol>
    </div>
    <?= $voet(2) ?>
  </section>

  <!-- 3. offerte -->
  <section class="pagina" aria-label="Offerte">
    <?= $kop ?>
    <div class="pinhoud">
      <h2>Offerte <?= $h($o['nummer']) ?>: reiniging van je buitenverharding</h2>
      <dl class="gegevens">
        <div><dt>Datum</dt><dd><?= $d($o['aangemaakt']) ?></dd></div>
        <div><dt>Geldig tot</dt><dd><?= $d($o['geldig_tot']) ?></dd></div>
        <div><dt>Klant</dt><dd><?= $h($k['naam']) ?><br><?= $h($k['adres']) ?><br><?= $h($k['email']) ?><?= $k['tel'] ? '<br>' . $h($k['tel']) : '' ?></dd></div>
      </dl>
      <p>Beste <?= $h($voornaam) ?>,</p>
      <p>Bedankt voor je aanvraag. Hieronder vind je onze offerte voor de ecologische reiniging van je verharding. Heb je nog vragen, klik dan bovenaan op "Vraag stellen" of bel ons gerust.</p>

      <div class="tabel-wrap"><table class="lijnen">
        <thead><tr><th>Omschrijving</th><th class="r">Aantal</th><th class="r">Prijs</th><th class="r">Btw</th><th class="r">Totaal</th></tr></thead>
        <tbody>
          <tr><td><b>Ecologische reiniging buitenverharding</b>
            <ul><li>Verharding: <?= $h(implode(', ', $o['types'])) ?></li>
            <li>Reiniging met een gepatenteerd borstelsysteem en water, zonder chemicaliën, zeep, stoom of hogedruk</li>
            <li>Vrijgekomen vuil en mos samengeveegd, werkterrein borstelschoon achtergelaten</li>
            <?php if ($o['zones']): ?><li>Oppervlakte ingetekend op de luchtfoto (<?= (int)$o['zones'] ?> zone<?= $o['zones'] > 1 ? 's' : '' ?>)</li><?php endif; ?></ul></td>
            <td class="r"><?= number_format((float)$o['m2'], 1, ',', '.') ?> m²</td><td class="r"><?= $eur($prijsPerM2) ?>/m²</td><td class="r">21%</td><td class="r"><?= $eur($o['reiniging']) ?></td></tr>
          <tr><td><b>Verplaatsing</b><?= $k['gemeente'] ? '<ul><li>Naar ' . $h($k['gemeente']) . '</li></ul>' : '' ?></td>
            <td class="r">1</td><td class="r"><?= $o['verplaatsing'] === null ? 'op aanvraag' : ((float)$o['verplaatsing'] == 0 ? 'gratis' : $eur($o['verplaatsing'])) ?></td><td class="r">21%</td><td class="r"><?= $o['verplaatsing'] === null ? '–' : $eur($o['verplaatsing']) ?></td></tr>
          <?php if ($o['korting']): ?>
          <tr><td><b>Korting</b><ul><li>Code <?= $h($o['korting']['code']) ?>: <?= $h($o['korting']['label']) ?></li></ul></td><td class="r">1</td><td class="r">– <?= $eur($o['korting']['bedrag']) ?></td><td class="r">21%</td><td class="r">– <?= $eur($o['korting']['bedrag']) ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
      <table class="totalen">
        <tr><th>Totaal excl. btw</th><td><?= $eur($o['totaal_excl']) ?></td></tr>
        <tr><th>Btw 21%</th><td><?= $eur($o['btw']) ?></td></tr>
        <tr class="eind"><th>Totaal te betalen</th><td><?= $eur($o['totaal_incl']) ?></td></tr>
      </table>

      <h3>Voorwaarden van deze offerte</h3>
      <ul class="voorwaarden">
        <li><b>Oppervlakte.</b> De prijs is gebaseerd op de oppervlakte die je opgaf. Is de werkelijke oppervlakte meer dan 10% groter, dan melden we dat vóór we beginnen, met de aangepaste prijs volgens hetzelfde tarief. Je kiest dan zelf: verder aan de aangepaste prijs, alleen de opgegeven oppervlakte, of kosteloos annuleren. Is ze kleiner, dan verlagen we de prijs naar verhouding.</li>
        <li><b>Niet inbegrepen.</b> Het opnieuw invoegen of bijvullen van voegen, het verwijderen van hardnekkige vlekken zoals olie, roest, kauwgom of cement, het verplaatsen van zware objecten<?= preg_match('/\b(Brussel|Antwerpen)\b/u', $k['gemeente'] ?? '') ? ', en de LEZ-toeslag' : '' ?>. Dat bieden we graag apart aan.</li>
        <li><b>Wat je zelf voorziet.</b> Een buitenkraan met minstens 2,5 bar waterdruk en een Gardena-koppeling, een stopcontact (230 V) in de buurt, en een vrije oppervlakte zonder meubels, bloempotten of voertuigen.<?= OFFERTE_UURTARIEF ? ' Extra tijd door een niet-vrije werkzone rekenen we aan tegen ' . $eur(OFFERTE_UURTARIEF) . ' per uur excl. btw.' : '' ?></li>
        <li><b>Betaling.</b> Geen voorschot. Je ontvangt de factuur na de uitvoering, te betalen binnen 14 kalenderdagen.</li>
        <li><b>Planning.</b> Na je aanvaarding spreken we samen een datum af. Bij vorst of hevige regen verplaatsen we kosteloos naar een nieuwe datum.</li>
        <li><b>Herroepingsrecht.</b> Als consument kan je binnen 14 dagen na je aanvaarding kosteloos en zonder opgave van reden herroepen. Zie pagina 5.</li>
      </ul>
      <?php if ($o['opmerking']): ?><p class="opm"><b>Jouw opmerking:</b> <?= nl2br($h($o['opmerking'])) ?></p><?php endif; ?>

      <p class="akkoordzin">Beide partijen verklaren zich akkoord met de inhoud van deze offerte en de algemene voorwaarden.</p>
      <div class="handtekeningen">
        <div><span>Super Schoon – S&amp;S Projects BV</span><b>Sander Vangrunderbeek</b></div>
        <div class="klant-hand">
          <span>Voor akkoord – de klant</span>
          <?php if ($a): ?>
            <img src="<?= $h($a['handtekening']) ?>" alt="Handtekening van <?= $h($a['naam']) ?>" class="sig">
            <b><?= $h($a['naam']) ?></b><small>Digitaal aanvaard op <?= date('d/m/Y \o\m H:i', $a['t']) ?><?= $a['vroeg'] ? ' · uitvoering binnen de herroepingstermijn gevraagd' : '' ?></small>
          <?php elseif ($status === 'open'): ?>
            <button type="button" class="knop groen" data-open="d-aanvaarden">Offerte aanvaarden</button>
            <small>Nog niet aanvaard</small>
          <?php else: ?>
            <b><?= $h($k['naam']) ?></b><small><?= $h(ucfirst($status)) ?></small>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?= $voet(3) ?>
  </section>

  <!-- 4. algemene voorwaarden -->
  <section class="pagina av" aria-label="Algemene voorwaarden">
    <?= $kop ?>
    <div class="pinhoud"><h2>Algemene voorwaarden</h2><div class="avtekst"><?= $av ?></div></div>
    <?= $voet(4) ?>
  </section>

  <!-- 5. herroeping -->
  <section class="pagina" aria-label="Herroepingsrecht">
    <?= $kop ?>
    <div class="pinhoud">
      <h2>Herroepingsrecht</h2>
      <p>Ben je consument, dan heb je het recht om deze overeenkomst binnen 14 kalenderdagen na je aanvaarding zonder opgave van redenen te herroepen. Dat kan:</p>
      <ul>
        <li>met de knop "Herroepen" bovenaan deze offertepagina;</li>
        <li>per e-mail aan info@ssprojects.be;</li>
        <li>met het modelformulier hieronder, per e-mail of per post aan Super Schoon, Beekveldstraat 1A, 1980 Zemst.</li>
      </ul>
      <p>Je herroept op tijd als je je melding verstuurt vóór de termijn verstreken is. Heb je gevraagd om de reiniging binnen de herroepingstermijn uit te voeren en herroep je daarna nog, dan betaal je het deel van de werken dat al uitgevoerd is. Is de reiniging op jouw uitdrukkelijk verzoek volledig uitgevoerd, dan kan je niet meer herroepen.</p>
      <div class="modelformulier">
        <h3>Modelformulier voor herroeping</h3>
        <p><i>(Dit formulier alleen invullen en terugsturen als je de overeenkomst wil herroepen.)</i></p>
        <p>Aan Super Schoon – S&amp;S Projects BV, Beekveldstraat 1A, 1980 Zemst, info@ssprojects.be:</p>
        <p>Ik deel u hierbij mede dat ik onze overeenkomst betreffende de verrichting van de volgende dienst herroep: reiniging van buitenverharding, offerte <?= $h($o['nummer']) ?>.</p>
        <p class="lijntjes">Aanvaard op: <?= $a ? $d($a['t']) : '…………………' ?><br>Naam: …………………………………………<br>Adres: …………………………………………<br>Handtekening (alleen bij een papieren formulier): …………………<br>Datum: …………………</p>
      </div>
    </div>
    <?= $voet(5) ?>
  </section>
</main>

<!-- dialoogvensters -->
<dialog id="d-aanvaarden" class="dlg" aria-labelledby="t-aanv">
  <form method="dialog" class="dlg-in" id="f-aanvaarden" novalidate>
    <button class="sluit" value="sluit" aria-label="Sluiten" formnovalidate>×</button>
    <h2 id="t-aanv">Offerte <?= $h($o['nummer']) ?> aanvaarden</h2>
    <p class="samenvatting">Reiniging van <?= number_format((float)$o['m2'], 1, ',', '.') ?> m² · <b><?= $eur($o['totaal_incl']) ?> incl. btw</b></p>
    <label for="a-naam">Je naam</label>
    <input id="a-naam" autocomplete="name" value="<?= $h($k['naam']) ?>" required>
    <div class="sig-kop"><label for="a-sig">Je handtekening</label><button type="button" class="link" id="sig-wis">Wissen</button></div>
    <canvas id="a-sig" class="sigvak" aria-label="Teken hier je handtekening met je vinger of muis" role="img"></canvas>
    <label class="vink"><input type="checkbox" id="a-akkoord" required><span>Ik heb de offerte en de <a href="#document" data-naar=".av">algemene voorwaarden</a> gelezen en ga ermee akkoord.</span></label>
    <label class="vink"><input type="checkbox" id="a-vroeg"><span><b>Optioneel:</b> ik vraag uitdrukkelijk dat de reiniging binnen de herroepingstermijn van 14 dagen mag gebeuren. Ik weet dat ik bij herroeping het al uitgevoerde deel betaal, en dat ik mijn herroepingsrecht verlies zodra de reiniging volledig is uitgevoerd.</span></label>
    <p class="fout" id="a-fout" role="alert" hidden></p>
    <button type="submit" class="knop groen breed" id="a-knop" value="ok">Aanvaarden met betalingsverplichting</button>
    <p class="klein">Je ontvangt meteen een bevestiging per e-mail.</p>
  </form>
</dialog>

<dialog id="d-weigeren" class="dlg" aria-labelledby="t-weig">
  <form method="dialog" class="dlg-in" id="f-weigeren">
    <button class="sluit" value="sluit" aria-label="Sluiten" formnovalidate>×</button>
    <h2 id="t-weig">Offerte weigeren</h2>
    <p>Jammer! Mogen we vragen waarom? Zo kunnen we ons aanbod verbeteren.</p>
    <label for="w-reden">Reden</label>
    <select id="w-reden"><option value="">Kies een reden (optioneel)</option><option>Te duur</option><option>Andere aanbieder gekozen</option><option>Werk zelf uit</option><option>Niet meer nodig</option><option>Andere reden</option></select>
    <label for="w-toel">Toelichting (optioneel)</label>
    <textarea id="w-toel" rows="3"></textarea>
    <p class="fout" id="w-fout" role="alert" hidden></p>
    <button type="submit" class="knop rood breed" value="ok">Offerte weigeren</button>
  </form>
</dialog>

<dialog id="d-vraag" class="dlg" aria-labelledby="t-vraag">
  <form method="dialog" class="dlg-in" id="f-vraag">
    <button class="sluit" value="sluit" aria-label="Sluiten" formnovalidate>×</button>
    <h2 id="t-vraag">Vraag over je offerte</h2>
    <p>We antwoorden per e-mail op <?= $h($k['email']) ?>. Liever bellen? 0468 43 00 53.</p>
    <label for="v-tekst">Je vraag</label>
    <textarea id="v-tekst" rows="5" required></textarea>
    <p class="fout" id="v-fout" role="alert" hidden></p>
    <button type="submit" class="knop groen breed" value="ok">Versturen</button>
  </form>
</dialog>

<dialog id="d-herroepen" class="dlg" aria-labelledby="t-herr">
  <form method="dialog" class="dlg-in" id="f-herroepen">
    <button class="sluit" value="sluit" aria-label="Sluiten" formnovalidate>×</button>
    <h2 id="t-herr">Overeenkomst herroepen</h2>
    <p>Hiermee herroep je de overeenkomst voor offerte <?= $h($o['nummer']) ?>. Dat kan kosteloos tot en met <?= $herroepTot ? $d($herroepTot) : '' ?>.</p>
    <label for="h-naam">Je naam</label>
    <input id="h-naam" value="<?= $h($k['naam']) ?>" required>
    <p class="fout" id="h-fout" role="alert" hidden></p>
    <button type="submit" class="knop rood breed" value="ok">Ik herroep de overeenkomst</button>
  </form>
</dialog>

<div class="toast" id="toast" role="status" hidden></div>
<script src="<?= $v('assets/js/offerte.js') ?>"></script>
</body>
</html>
