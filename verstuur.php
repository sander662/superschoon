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

$ruw = file_get_contents('php://input', false, null, 0, 200000);
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
function verstuur(string $aan, string $onderwerp, string $body, ?string $antwoordAan = null): bool {
    $headers = [
        'From: ' . mb_encode_mimeheader(NAAM, 'UTF-8') . ' <' . AFZENDER . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: SuperSchoon-website',
    ];
    if ($antwoordAan) $headers[] = 'Reply-To: ' . $antwoordAan;
    return mail($aan, mb_encode_mimeheader($onderwerp, 'UTF-8'), $body, implode("\r\n", $headers), '-f' . AFZENDER);
}

/* ---- gemeenschappelijke velden ---- */
$type  = ($in['type'] ?? '') === 'offerte' ? 'offerte' : 'contact';
$naam  = regel($in['naam'] ?? '', 120);
$email = filter_var(regel($in['email'] ?? '', 160), FILTER_VALIDATE_EMAIL) ?: '';
$tel   = regel($in['tel'] ?? '', 40);

if (mb_strlen($naam) < 2 || $email === '') {
    antwoord(422, ['ok' => false, 'fout' => 'Naam of e-mailadres ontbreekt.']);
}

$tijd = date('d/m/Y H:i');

if ($type === 'contact') {
    $bericht = tekst($in['bericht'] ?? '', 4000);
    if (mb_strlen($bericht) < 5) antwoord(422, ['ok' => false, 'fout' => 'Bericht ontbreekt.']);

    $body = "Nieuw bericht via superschoon.be ($tijd)\n\n"
          . "Naam:     $naam\nE-mail:   $email\nTelefoon: " . ($tel ?: '-') . "\n\n"
          . "Bericht:\n$bericht\n";
    $ok = verstuur(ONTVANGER, "Nieuw bericht van $naam", $body, $email);

    $bev = "Beste $naam,\n\nBedankt voor je bericht. We antwoorden zo snel mogelijk.\n\n"
         . "Je bericht:\n$bericht\n\n"
         . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\nwww.superschoon.be\n";
    if ($ok) verstuur($email, 'We hebben je bericht ontvangen', $bev, ONTVANGER);
    antwoord($ok ? 200 : 500, ['ok' => $ok]);
}

/* ---- offerteaanvraag ---- */
$adres = regel($in['adres'] ?? '', 250);
$m2    = (float)($in['m2'] ?? 0);
$types = array_slice(array_map(fn($x) => regel($x, 40), is_array($in['types'] ?? null) ? $in['types'] : []), 0, 6);
$opm   = tekst($in['opmerking'] ?? '', 2000);
$rein  = prijsReiniging($m2);
if ($rein === null || $adres === '' || !$types) {
    antwoord(422, ['ok' => false, 'fout' => 'Oppervlakte, adres of type verharding ontbreekt.']);
}
$rit   = is_numeric($in['verplaatsing'] ?? null) ? (float)$in['verplaatsing'] : null;
$excl  = $rein + ($rit ?? 0);
$incl  = round($excl * 1.21, 2);

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

$body = "Nieuwe offerteaanvraag via superschoon.be ($tijd)\n\n"
      . "KLANT\n"
      . "Naam:      $naam\nE-mail:    $email\nTelefoon:  $tel\nAdres:     $adres\n\n"
      . "VERHARDING\n"
      . "Type:      " . implode(', ', $types) . "\n"
      . "Oppervlakte: " . number_format($m2, 1, ',', '.') . " m²\n"
      . ($zonesTxt ? "Ingetekend op de kaart:\n$zonesTxt" : "Oppervlakte zelf ingevuld (niet ingetekend).\n")
      . "\nPRIJS (zoals getoond op de website)\n"
      . "Reiniging:    " . eur($rein) . " excl. btw\n"
      . "Verplaatsing: " . ($rit === null ? 'op aanvraag (gemeente niet gevonden)' : eur($rit) . ' excl. btw') . "\n"
      . "Totaal:       " . eur($excl) . " excl. btw – " . eur($incl) . " incl. 21% btw\n\n"
      . "Opmerking:\n" . ($opm ?: '-') . "\n";
$ok = verstuur(ONTVANGER, "Offerteaanvraag: $naam – " . number_format($m2, 0, ',', '.') . ' m²', $body, $email);

$bev = "Beste $naam,\n\nBedankt voor je aanvraag bij Super Schoon. We nemen zo snel mogelijk contact met je op om een datum te plannen.\n\n"
     . "Je aanvraag:\n"
     . "- Verharding: " . implode(', ', $types) . "\n"
     . "- Oppervlakte: " . number_format($m2, 1, ',', '.') . " m²\n"
     . "- Adres: $adres\n"
     . "- Indicatieve prijs: " . eur($incl) . " incl. btw" . ($rit === null ? ' + verplaatsing' : '') . "\n\n"
     . "Dit is een indicatieve prijs. De definitieve prijs bevestigen we na een korte controle ter plaatse of aan de hand van foto's.\n"
     . "Onze algemene voorwaarden: https://www.superschoon.be/algemene-voorwaarden.html\n\n"
     . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\nwww.superschoon.be\n";
if ($ok) verstuur($email, 'Je aanvraag bij Super Schoon', $bev, ONTVANGER);

antwoord($ok ? 200 : 500, ['ok' => $ok]);
