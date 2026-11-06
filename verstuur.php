<?php
/**
 * Super Schoon – verwerking van de prijsaanvraag en het contactformulier.
 * Werkt op een OVH-webhosting met PHP 8. Stuurt een e-mail naar Super Schoon
 * en een bevestiging naar de klant.
 *
 * Aanpassen indien nodig:
 *   ONTVANGER  = waar de aanvragen naartoe gaan
 *   AFZENDER   = afzender van de mails; moet een adres op superschoon.be zijn
 */
declare(strict_types=1);
date_default_timezone_set('Europe/Brussels');
define('SUPERSCHOON', true);
require __DIR__ . '/kortingscodes.php';
require __DIR__ . '/dashboard/lib.php';
require __DIR__ . '/dashboard/offerte_lib.php';

const ONTVANGER = 'info@ssprojects.be';
const AFZENDER  = 'noreply@superschoon.be';
const NAAM      = 'Super Schoon';
const MAX_PER_UUR = 6;          // max. aanvragen per IP-adres per uur

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function antwoord(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    antwoord(405, ['ok' => false, 'fout' => 'Alleen POST is toegelaten.']);
}

$ruw = file_get_contents('php://input', false, null, 0, 15000000);
$in = json_decode((string)$ruw, true);
if (!is_array($in)) {
    antwoord(400, ['ok' => false, 'fout' => 'Ongeldige aanvraag.']);
}

/* ---- spamfilter ---- */
// honeypot: dit verborgen veld vullen alleen robots in
if (!empty($in['website'])) {
    antwoord(200, ['ok' => true]);
}
// te snel ingevuld (minder dan 3 seconden na het laden van de pagina)
$t = (int)($in['t'] ?? 0);
if ($t > 0 && (microtime(true) * 1000 - $t) < 3000) {
    antwoord(200, ['ok' => true]);
}
// eenvoudige limiet per IP-adres (het IP-adres zelf wordt niet bewaard, alleen een hash)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'onbekend';
$bestand = sys_get_temp_dir() . '/superschoon_' . hash('sha256', $ip . date('YmdH')) . '.cnt';
$aantal = is_file($bestand) ? (int)file_get_contents($bestand) : 0;
if ($aantal >= MAX_PER_UUR) {
    antwoord(429, ['ok' => false, 'fout' => 'Te veel aanvragen. Probeer het later opnieuw.']);
}
@file_put_contents($bestand, (string)($aantal + 1));

/* ---- hulpfuncties ---- */
function tekst($v, int $max = 500): string {
    $v = is_scalar($v) ? (string)$v : '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[^\P{C}\n]/u', '', $v) ?? '';   // geen stuurtekens
    return mb_substr(trim($v), 0, $max);
}
function regel($v, int $max = 200): string {
    return str_replace("\n", ' ', tekst($v, $max));
}
function eur(float $n): string {
    return '€ ' . number_format($n, 2, ',', '.');
}
function prijsReiniging(float $m2): ?int {
    if ($m2 < 1) return null;
    if ($m2 <= 50)  $p = 190 + 210 * ($m2 - 1) / 49;
    elseif ($m2 <= 100) $p = 400 + 7 * ($m2 - 50);
    else $p = 750 + 6 * ($m2 - 100);
    return (int)round($p);
}
function verstuur(string $aan, string $onderwerp, string $body, ?string $antwoordAan = null, array $bijlagen = []): bool {
    $headers = [
        'From: ' . mb_encode_mimeheader(NAAM, 'UTF-8') . ' <' . AFZENDER . '>',
        'MIME-Version: 1.0',
        'X-Mailer: SuperSchoon-website',
    ];
    if ($antwoordAan) $headers[] = 'Reply-To: ' . $antwoordAan;
    if (!$bijlagen) {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $inhoud = $body;
    } else {
        $grens = 'ss_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $grens . '"';
        $inhoud = "--$grens\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n$body\r\n";
        foreach ($bijlagen as [$naam, $data]) {
            $inhoud .= "--$grens\r\nContent-Type: image/jpeg; name=\"$naam\"\r\nContent-Transfer-Encoding: base64\r\n"
                     . "Content-Disposition: attachment; filename=\"$naam\"\r\n\r\n" . chunk_split(base64_encode($data)) . "\r\n";
        }
        $inhoud .= "--$grens--\r\n";
    }
    return mail($aan, mb_encode_mimeheader($onderwerp, 'UTF-8'), $inhoud, implode("\r\n", $headers), '-f' . AFZENDER);
}

/* ---- gemeenschappelijke velden ---- */
$type  = ($in['type'] ?? '') === 'offerte' ? 'offerte' : 'contact';
$naam  = regel($in['naam'] ?? '', 120);
$email = filter_var(regel($in['email'] ?? '', 160), FILTER_VALIDATE_EMAIL) ?: '';
$tel   = regel($in['tel'] ?? '', 40);
$nieuwsbrief = !empty($in['nieuwsbrief']) ? 'ja' : 'nee';

if (mb_strlen($naam) < 2 || $email === '') {
    antwoord(422, ['ok' => false, 'fout' => 'Naam of e-mailadres ontbreekt.']);
}

$tijd = date('d/m/Y H:i');

if ($type === 'contact') {
    $bericht = tekst($in['bericht'] ?? '', 4000);
    if (mb_strlen($bericht) < 5) antwoord(422, ['ok' => false, 'fout' => 'Bericht ontbreekt.']);

    $body = "Nieuw bericht via superschoon.be ($tijd)\n\n"
          . "Naam:     $naam\nE-mail:   $email\nTelefoon: " . ($tel ?: '-') . "\nNieuwsbrief: $nieuwsbrief\n\n"
          . "Bericht:\n$bericht\n";
    $ok = verstuur(ONTVANGER, "Nieuw bericht van $naam", $body, $email);

    $bev = "Beste $naam,\n\nBedankt voor je bericht. We antwoorden zo snel mogelijk.\n\n"
         . "Je bericht:\n$bericht\n\n"
         . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\nwww.superschoon.be\n";
    if ($ok) verstuur($email, 'We hebben je bericht ontvangen', $bev, ONTVANGER);
    if ($ok) stats_log('contact', ['p' => '/contact.html', 'nb' => $nieuwsbrief === 'ja']);
    antwoord($ok ? 200 : 500, ['ok' => $ok]);
}

/* ---- offerteaanvraag ---- */
$adres = regel($in['adres'] ?? '', 250);
$m2    = (float)($in['m2'] ?? 0);
$types = array_slice(array_map(fn($x) => regel($x, 80), is_array($in['types'] ?? null) ? $in['types'] : []), 0, 8);
$opm   = tekst($in['opmerking'] ?? '', 2000);
$rein  = prijsReiniging($m2);
if ($rein === null || $adres === '' || !$types) {
    antwoord(422, ['ok' => false, 'fout' => 'Oppervlakte, adres of type verharding ontbreekt.']);
}
$rit   = is_numeric($in['verplaatsing'] ?? null) ? (float)$in['verplaatsing'] : null;
$korting = zoekKorting(regel($in['kortingscode'] ?? '', 30));
$kb    = kortingBedrag($korting, $rein + ($rit ?? 0));
$excl  = $rein + ($rit ?? 0) - $kb;
$incl  = round($excl * 1.21, 2);

// foto's: max. 6, alleen geldige JPEG-afbeeldingen van max. 3 MB; ze worden enkel gemaild, niet bewaard
$bijlagen = [];
foreach (array_slice(is_array($in['fotos'] ?? null) ? $in['fotos'] : [], 0, 6) as $i => $f) {
    $data = base64_decode((string)($f['data'] ?? ''), true);
    if ($data === false || strlen($data) > 3000000) continue;
    $info = @getimagesizefromstring($data);
    if (!$info || $info[2] !== IMAGETYPE_JPEG) continue;
    $bijlagen[] = [sprintf('foto-%d.jpg', $i + 1), $data];
}

$zonesTxt = '';
$zones = is_array($in['zones'] ?? null) ? array_slice($in['zones'], 0, 20) : [];
foreach ($zones as $i => $z) {
    $pts = is_array($z['punten'] ?? null) ? array_slice($z['punten'], 0, 100) : [];
    $pts = array_values(array_filter($pts, fn($p) => is_array($p) && count($p) === 2 && is_numeric($p[0]) && is_numeric($p[1])));
    if (!$pts) continue;
    $opp = is_numeric($z['m2'] ?? null) ? (float)$z['m2'] : 0;
    $lat = (float)$pts[0][0]; $lng = (float)$pts[0][1];
    $zonesTxt .= sprintf("  Zone %d: %s m² – https://www.google.com/maps?q=%.6f,%.6f\n    hoekpunten: %s\n",
        $i + 1, number_format($opp, 1, ',', '.'), $lat, $lng,
        implode(' | ', array_map(fn($p) => sprintf('%.6f,%.6f', $p[0], $p[1]), $pts)));
}

// ---- online offerte aanmaken ----
$offerte = [
    'token' => bin2hex(random_bytes(16)), 'nummer' => offerte_volgnummer(), 'aangemaakt' => time(),
    'geldig_tot' => strtotime('+' . OFFERTE_GELDIG_MAANDEN . ' month'), 'status' => 'open',
    'klant' => ['naam' => $naam, 'email' => $email, 'tel' => $tel, 'adres' => $adres, 'gemeente' => regel($in['gemeente'] ?? '', 80)],
    'types' => $types, 'm2' => round($m2, 1), 'zones' => count($zones), 'fotos' => count($bijlagen), 'opmerking' => $opm,
    'reiniging' => (float)$rein, 'verplaatsing' => $rit,
    'korting' => $korting ? ['code' => $korting['code'], 'label' => $korting['label'], 'bedrag' => $kb] : null,
    'totaal_excl' => round($excl, 2), 'btw' => round($incl - $excl, 2), 'totaal_incl' => $incl,
    'nieuwsbrief' => $nieuwsbrief === 'ja', 'historiek' => [['t' => time(), 'actie' => 'aangemaakt']],
];
$offerteOk = offerte_bewaar($offerte);
$offerteUrl = $offerteOk ? offerte_url($offerte) : '';

$body = "Nieuwe offerteaanvraag via superschoon.be ($tijd)\n"
      . ($offerteOk ? "Online offerte {$offerte['nummer']}: $offerteUrl\n" : '') . "\n"
      . "KLANT\n"
      . "Naam:      $naam\nE-mail:    $email\nTelefoon:  $tel\nAdres:     $adres\nNieuwsbrief: $nieuwsbrief\n\n"
      . "VERHARDING\n"
      . "Type:      " . implode(', ', $types) . "\n"
      . "Oppervlakte: " . number_format($m2, 1, ',', '.') . " m²\n"
      . ($zonesTxt ? "Ingetekend op de kaart:\n$zonesTxt" : "Oppervlakte zelf ingevuld (niet ingetekend).\n")
      . "\nPRIJS (zoals getoond op de website)\n"
      . "Reiniging:    " . eur($rein) . " excl. btw\n"
      . "Verplaatsing: " . ($rit === null ? 'op aanvraag (gemeente niet gevonden)' : eur($rit) . ' excl. btw') . "\n"
      . ($korting ? "Korting:      – " . eur($kb) . " (code {$korting['code']}: {$korting['label']})\n" : '')
      . "Totaal:       " . eur($excl) . " excl. btw – " . eur($incl) . " incl. 21% btw\n\n"
      . "Foto's:       " . (count($bijlagen) ?: 'geen') . ($bijlagen ? ' (in bijlage)' : '') . "\n\n"
      . "Opmerking:\n" . ($opm ?: '-') . "\n";
$ok = verstuur(ONTVANGER, ($offerteOk ? "Offerte {$offerte['nummer']}: " : 'Offerteaanvraag: ') . "$naam – " . number_format($m2, 0, ',', '.') . ' m²', $body, $email, $bijlagen);

$bev = "Beste $naam,\n\nBedankt voor je aanvraag bij Super Schoon.\n\n"
     . ($offerteOk ? "Je offerte {$offerte['nummer']} staat klaar. Je kan ze bekijken, downloaden en meteen online aanvaarden via deze persoonlijke link:\n$offerteUrl\n\nDe offerte is geldig tot " . date('d/m/Y', $offerte['geldig_tot']) . ". Na je aanvaarding nemen we contact op om een datum te plannen.\n\n"
                   : "We nemen zo snel mogelijk contact met je op om een datum te plannen.\n\n")
     . "Je aanvraag:\n"
     . "- Verharding: " . implode(', ', $types) . "\n"
     . "- Oppervlakte: " . number_format($m2, 1, ',', '.') . " m²\n"
     . "- Adres: $adres\n"
     . ($korting ? "- Kortingscode: {$korting['code']} ({$korting['label']})\n" : '')
     . "- Indicatieve prijs: " . eur($incl) . " incl. btw" . ($rit === null ? ' + verplaatsing' : '') . "\n\n"
     . "Dit is een indicatieve prijs. De definitieve prijs bevestigen we na een korte controle ter plaatse of aan de hand van foto's.\n"
     . "Onze algemene voorwaarden: https://www.superschoon.be/algemene-voorwaarden.html\n\n"
     . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\nwww.superschoon.be\n";
if ($ok) verstuur($email, 'Je aanvraag bij Super Schoon', $bev, ONTVANGER);
// statistiek: geen naam, e-mail, telefoon of adres, alleen gemeente en kerncijfers
if ($ok) stats_log('offerte', ['p' => '/prijsberekening.html', 'g' => regel($in['gemeente'] ?? '', 60), 'm2' => round($m2, 1),
    'b' => round($excl, 2), 'code' => $korting['code'] ?? '', 'nb' => $nieuwsbrief === 'ja', 'f' => count($bijlagen),
    'kaart' => $zonesTxt !== '', 'ty' => $types]);

antwoord($ok ? 200 : 500, ['ok' => $ok] + ($ok && $offerteOk ? ['url' => '/offerte.php?id=' . $offerte['token']] : []));
