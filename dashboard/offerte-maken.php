<?php
/** Super Schoon – offerte handmatig opmaken (bv. na een plaatsbezoek), met Teamleader-koppeling en tekenen ter plaatse. */
declare(strict_types=1);
define('SUPERSCHOON', true);
require __DIR__ . '/auth.php';
require __DIR__ . '/offerte_lib.php';
require __DIR__ . '/tl_lib.php';

const OM_ONTVANGER = 'info@ssprojects.be';
const OM_AFZENDER  = 'noreply@superschoon.be';

/* ---------- acties (JSON) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $uit = function (int $code, array $d): void { http_response_code($code); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; };
    if (!dash_ingelogd()) $uit(401, ['ok' => false, 'fout' => 'Je bent niet meer aangemeld. Herlaad de pagina.']);
    $_SESSION['tijd'] = time();
    if (!hash_equals(dash_csrf(), (string)($_SERVER['HTTP_X_CSRF'] ?? ''))) $uit(403, ['ok' => false, 'fout' => 'Sessie verlopen. Herlaad de pagina.']);
    $in = json_decode((string)file_get_contents('php://input', false, null, 0, 200000), true);
    if (!is_array($in)) $uit(400, ['ok' => false, 'fout' => 'Ongeldige aanvraag.']);
    $regel = fn($v, int $max) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)$v) ?? ''), 0, $max);
    $tekst = fn($v, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$v) ?? ''), 0, $max);
    $actie = (string)($in['actie'] ?? '');

    if ($actie === 'zoek' || $actie === 'contact') {
        if (!tl_gekoppeld()) $uit(409, ['ok' => false, 'fout' => 'Teamleader is niet gekoppeld (zie Offertes & Teamleader).']);
        try {
            if ($actie === 'zoek') {
                $q = $regel($in['q'] ?? '', 80);
                if (mb_strlen($q) < 2) $uit(422, ['ok' => false, 'fout' => 'Typ minstens 2 letters.']);
                $uit(200, ['ok' => true, 'lijst' => tl_zoek_contacten($q)]);
            }
            $id = $regel($in['id'] ?? '', 60);
            $uit(200, ['ok' => true, 'contact' => tl_contact($id)]);
        } catch (Throwable $t) { $uit(502, ['ok' => false, 'fout' => 'Teamleader: ' . $t->getMessage()]); }
    }

    if ($actie === 'maak') {
        $k = is_array($in['klant'] ?? null) ? $in['klant'] : [];
        $klant = ['naam' => $regel($k['naam'] ?? '', 120), 'email' => '', 'tel' => $regel($k['tel'] ?? '', 40),
                  'adres' => $regel($k['adres'] ?? '', 250), 'gemeente' => $regel($k['gemeente'] ?? '', 80)];
        $mail = $regel($k['email'] ?? '', 160);
        if ($mail !== '') { $klant['email'] = filter_var($mail, FILTER_VALIDATE_EMAIL) ?: ''; if ($klant['email'] === '') $uit(422, ['ok' => false, 'fout' => 'Het e-mailadres klopt niet.']); }
        $wijze = ($in['wijze'] ?? '') === 'tekenen' ? 'tekenen' : 'mailen';
        if (mb_strlen($klant['naam']) < 2) $uit(422, ['ok' => false, 'fout' => 'Vul de naam van de klant in.']);
        if ($klant['adres'] === '') $uit(422, ['ok' => false, 'fout' => 'Vul het adres van de werf in.']);
        if ($wijze === 'mailen' && $klant['email'] === '') $uit(422, ['ok' => false, 'fout' => 'Om de offerte te mailen is een e-mailadres nodig.']);

        $m2 = max(0, min(100000, round((float)($in['m2'] ?? 0), 1)));
        $types = array_slice(array_values(array_filter(array_map(fn($x) => $regel($x, 80), is_array($in['types'] ?? null) ? $in['types'] : []))), 0, 8);
        $rein = $m2 >= 1 ? (is_numeric($in['reiniging'] ?? null) ? round((float)$in['reiniging'], 2) : offerte_prijs_reiniging($m2)) : 0.0;
        if ($m2 > 0 && $m2 < 1) $uit(422, ['ok' => false, 'fout' => 'De oppervlakte moet minstens 1 m² zijn (of 0 als er geen reiniging is).']);
        if ($m2 >= 1 && !$types) $uit(422, ['ok' => false, 'fout' => 'Kies minstens één type verharding.']);
        $rit = is_numeric($in['verplaatsing'] ?? null) ? round(max(0, (float)$in['verplaatsing']), 2) : null;

        $extra = [];
        foreach (array_slice(is_array($in['extra'] ?? null) ? $in['extra'] : [], 0, 30) as $l) {
            $oms = $regel($l['omschrijving'] ?? '', 150);
            if ($oms === '') continue;
            $aantal = (float)str_replace(',', '.', (string)($l['aantal'] ?? 1));
            $prijs = (float)str_replace(',', '.', (string)($l['prijs'] ?? 0));
            if ($aantal <= 0) $uit(422, ['ok' => false, 'fout' => "Het aantal bij \"$oms\" moet groter dan 0 zijn."]);
            $extra[] = ['omschrijving' => $oms, 'toelichting' => $tekst($l['toelichting'] ?? '', 500), 'aantal' => round($aantal, 2),
                        'eenheid' => $regel($l['eenheid'] ?? '', 20), 'prijs' => round($prijs, 2)];
        }
        if ($m2 < 1 && !$extra) $uit(422, ['ok' => false, 'fout' => 'De offerte is leeg: vul een oppervlakte in of voeg een lijn toe.']);

        $o = [
            'token' => bin2hex(random_bytes(16)), 'nummer' => offerte_volgnummer(), 'aangemaakt' => time(),
            'geldig_tot' => strtotime('+' . OFFERTE_GELDIG_MAANDEN . ' month'), 'status' => 'open', 'bron' => 'dashboard',
            'klant' => $klant, 'types' => $types, 'm2' => $m2, 'zones' => 0, 'fotos' => 0,
            'opmerking' => $tekst($in['opmerking'] ?? '', 2000),
            'reiniging' => $rein, 'verplaatsing' => $rit, 'korting' => null, 'extra' => $extra,
            'nieuwsbrief' => false, 'historiek' => [['t' => time(), 'actie' => 'aangemaakt in dashboard (' . $wijze . ')']],
        ];
        $tlId = $regel($in['tl_contact_id'] ?? '', 60);
        if ($tlId !== '') $o['tl'] = ['contact_id' => $tlId];
        offerte_bereken($o);
        if ($o['totaal_excl'] <= 0) $uit(422, ['ok' => false, 'fout' => 'Het totaal moet groter dan € 0 zijn.']);
        if (!offerte_bewaar($o)) $uit(500, ['ok' => false, 'fout' => 'Opslaan is mislukt.']);
        tl_sync_nieuw($o);
        offerte_bewaar($o);
        $url = offerte_url($o);

        $gemaild = false;
        if ($wijze === 'mailen') {
            $vn = explode(' ', $klant['naam'])[0];
            $body = "Beste $vn,\n\nBedankt voor je interesse in Super Schoon" . ((float)$m2 > 0 ? ' en voor het plaatsbezoek' : '') . ".\n\n"
                  . "Zoals besproken staat je offerte {$o['nummer']} klaar. Je kan ze bekijken, downloaden en meteen online aanvaarden via deze persoonlijke link:\n$url\n\n"
                  . "- " . ucfirst(offerte_kort($o)) . "\n- Totaal: " . offerte_eur((float)$o['totaal_incl']) . " incl. btw\n\n"
                  . "De offerte is geldig tot " . date('d/m/Y', $o['geldig_tot']) . ". Bij aanvaarding betaal je nog niets: na je aanvaarding spreken we samen een datum af.\n\n"
                  . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\nwww.superschoon.be\n";
            $h = ['From: Super Schoon <' . OM_AFZENDER . '>', 'Reply-To: ' . OM_ONTVANGER, 'Bcc: ' . OM_ONTVANGER, 'MIME-Version: 1.0',
                  'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: 8bit'];
            $gemaild = mail($klant['email'], mb_encode_mimeheader("Je offerte {$o['nummer']} van Super Schoon", 'UTF-8'), $body, implode("\r\n", $h), '-f' . OM_AFZENDER);
            $o['historiek'][] = ['t' => time(), 'actie' => $gemaild ? 'gemaild naar klant' : 'mailen mislukt'];
            offerte_bewaar($o);
        }
        stats_log('offerte_manueel', ['p' => '/dashboard/offerte-maken.php', 'nr' => $o['nummer'], 'b' => $o['totaal_excl'], 'g' => $klant['gemeente']]);
        $uit(200, ['ok' => true, 'nummer' => $o['nummer'], 'url' => '/offerte.php?id=' . $o['token'], 'gemaild' => $gemaild,
                   'tl' => !empty($o['tl']['ok']) ? 'ok' : (!empty($o['tl']['fout']) ? 'fout: ' . $o['tl']['fout'] : 'niet')]);
    }
    $uit(400, ['ok' => false, 'fout' => 'Onbekende actie.']);
}

/* ---------- pagina ---------- */
if (!dash_ingelogd()) { header('Location: ./'); exit; }
$_SESSION['tijd'] = time();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$v = fn($f) => $f . '?v=' . substr(md5((string)@filemtime(__DIR__ . '/' . $f)), 0, 8);
$zones = array_map(fn($z) => $z['kost'], offerte_zones());
$tlOk = tl_gekoppeld();
$types = ['Natuursteen', 'Keramische tegel', 'Betonklinker', 'Kleiklinker', 'XL-tegel', 'Houten terras', 'Composiet terras', 'Gepolierde beton', 'Asfalt', 'Andere'];
?><!doctype html>
<html lang="nl-BE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Nieuwe offerte | Dashboard Super Schoon</title><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="<?= $v('dashboard.css') ?>"></head>
<body>
<header class="kop"><div class="kop-in">
  <a class="merk" href="./"><img src="/assets/img/logo-superschoon-wit.svg" alt="Super Schoon" width="180" height="31"></a>
  <span class="kop-titel">Nieuwe offerte</span>
  <nav class="kop-links" style="margin-left:auto"><a href="./">Terug naar dashboard</a><a href="./?uit=1">Afmelden</a></nav>
</div></header>
<main class="wrap om" id="om" data-csrf="<?= $h(dash_csrf()) ?>" data-uurtarief="<?= OFFERTE_UURTARIEF ?>">

  <section class="paneel">
    <h2>1. Klant</h2>
    <?php if ($tlOk): ?>
    <div class="om-zoek">
      <label for="tl-q">Zoeken in Teamleader</label>
      <div class="om-rij"><input id="tl-q" placeholder="Naam, e-mail of telefoon" autocomplete="off"><button type="button" class="knop licht" id="tl-zoek">Zoeken</button></div>
      <ul class="om-resultaten" id="tl-res" hidden></ul>
      <p class="zacht" id="tl-gekozen" hidden></p>
    </div>
    <?php else: ?>
    <p class="zacht">Teamleader is niet gekoppeld: vul de klantgegevens zelf in.</p>
    <?php endif; ?>
    <div class="om-grid">
      <label>Naam *<input id="k-naam" autocomplete="off" required></label>
      <label>E-mail<input id="k-email" type="email" autocomplete="off"></label>
      <label>Telefoon<input id="k-tel" type="tel" autocomplete="off"></label>
      <label class="breed">Adres van de werf * <input id="k-adres" placeholder="Straat en nummer, postcode gemeente" autocomplete="off"></label>
      <label>Gemeente (voor de verplaatsing)<input id="k-gemeente" list="gemeenten" autocomplete="off"></label>
    </div>
    <datalist id="gemeenten"><?php foreach ($zones as $n => $kost): ?><option value="<?= $h($n) ?>"><?php endforeach; ?></datalist>
  </section>

  <section class="paneel">
    <h2>2. Reiniging</h2>
    <p class="zacht">Laat de oppervlakte op 0 als de offerte geen reiniging bevat.</p>
    <div class="om-grid">
      <label>Oppervlakte (m²)<input id="r-m2" type="number" min="0" step="0.1" inputmode="decimal" value="0"></label>
      <label>Prijs reiniging excl. btw<input id="r-prijs" type="number" step="0.01" inputmode="decimal" readonly></label>
      <label class="vink"><input type="checkbox" id="r-aanpassen"> Prijs zelf aanpassen</label>
      <label>Verplaatsing excl. btw<input id="r-rit" type="number" min="0" step="0.01" inputmode="decimal" placeholder="geen"></label>
    </div>
    <p class="zacht" id="r-rit-info"></p>
    <fieldset class="om-types"><legend>Type verharding</legend>
      <?php foreach ($types as $t): ?><label class="vink"><input type="checkbox" name="type" value="<?= $h($t) ?>"> <?= $h($t) ?></label><?php endforeach; ?>
    </fieldset>
  </section>

  <section class="paneel">
    <h2>3. Extra lijnen</h2>
    <p class="zacht">Bijvoorbeeld voegen opnieuw inzanden: een lijn arbeid en een lijn voegzand. Prijzen excl. btw (21%).</p>
    <div class="tabel-scroll"><table class="om-lijnen">
      <thead><tr><th>Omschrijving</th><th>Toelichting (optioneel)</th><th class="r">Aantal</th><th>Eenheid</th><th class="r">Prijs/eenheid</th><th class="r">Totaal</th><th></th></tr></thead>
      <tbody id="lijnen"></tbody>
    </table></div>
    <datalist id="eenheden"><option value="uur"><option value="m²"><option value="m"><option value="stuk"><option value="zak"><option value="forfait"></datalist>
    <div class="knoppen" style="margin-top:10px">
      <button type="button" class="knop licht" id="l-nieuw">+ Lijn toevoegen</button>
      <button type="button" class="knop licht" data-snel="arbeid">+ Arbeid (uur à € <?= number_format(OFFERTE_UURTARIEF, 0, ',', '.') ?>)</button>
      <button type="button" class="knop licht" data-snel="voegzand">+ Voegzand</button>
    </div>
  </section>

  <section class="paneel">
    <h2>4. Opmerking voor de klant <span class="zacht">(optioneel, staat op de offerte)</span></h2>
    <textarea id="opmerking" rows="3" placeholder="Bv. afspraken van het plaatsbezoek"></textarea>
  </section>

  <section class="paneel om-totaal">
    <h2>5. Totaal</h2>
    <table class="om-tot">
      <tr><th>Totaal excl. btw</th><td id="t-excl">€ 0,00</td></tr>
      <tr><th>Btw 21%</th><td id="t-btw">€ 0,00</td></tr>
      <tr class="eind"><th>Totaal incl. btw</th><td id="t-incl">€ 0,00</td></tr>
      <tr><th class="zacht">Voorschot 25% (zodra de datum vastligt)</th><td class="zacht" id="t-vs">€ 0,00</td></tr>
    </table>
    <p class="fout" id="om-fout" role="alert" hidden></p>
    <div class="knoppen">
      <button type="button" class="knop" data-wijze="mailen">Aanmaken en naar de klant mailen</button>
      <button type="button" class="knop licht" data-wijze="tekenen">Aanmaken en nu laten tekenen</button>
    </div>
    <p class="zacht">Beide maken de offerte ook aan in Teamleader (contact, deal en offerte), net zoals bij een aanvraag via de website. "Nu laten tekenen" opent de offerte op dit scherm met het handtekenvak: de klant leest en tekent, en krijgt daarna de bevestiging per mail.</p>
    <div class="om-klaar" id="om-klaar" hidden></div>
  </section>
</main>
<script>window.OM_ZONES = <?= json_encode($zones, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= $v('offerte-maken.js') ?>"></script>
</body></html>
