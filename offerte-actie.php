<?php
/** Super Schoon – acties op een online offerte: aanvaarden, weigeren, vraag stellen, herroepen. */
declare(strict_types=1);
date_default_timezone_set('Europe/Brussels');
define('SUPERSCHOON', true);
require __DIR__ . '/dashboard/offerte_lib.php';

const ONTVANGER = 'info@ssprojects.be';
const AFZENDER  = 'noreply@superschoon.be';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function uit(int $code, array $d): void { http_response_code($code); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function tekst($v, int $max): string { return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$v) ?? ''), 0, $max); }
function mail_txt(string $aan, string $onderwerp, string $body, ?string $antwoordAan = null): bool {
    $h = ['From: Super Schoon <' . AFZENDER . '>', 'Reply-To: ' . ($antwoordAan ?: ONTVANGER), 'MIME-Version: 1.0',
          'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: 8bit'];
    return mail($aan, mb_encode_mimeheader($onderwerp, 'UTF-8'), $body, implode("\r\n", $h), '-f' . AFZENDER);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') uit(405, ['ok' => false]);
$in = json_decode((string)file_get_contents('php://input', false, null, 0, 400000), true);
if (!is_array($in)) uit(400, ['ok' => false, 'fout' => 'Ongeldige aanvraag.']);

// eenvoudige limiet per IP-adres (alleen een hash)
$f = sys_get_temp_dir() . '/superschoon_o_' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('YmdH')) . '.cnt';
$n = is_file($f) ? (int)file_get_contents($f) : 0;
if ($n >= 30) uit(429, ['ok' => false, 'fout' => 'Te veel pogingen. Probeer het later opnieuw.']);
@file_put_contents($f, (string)($n + 1));

$o = offerte_laad((string)($in['id'] ?? ''));
if (!$o) uit(404, ['ok' => false, 'fout' => 'Deze offerte bestaat niet.']);
$status = offerte_status($o);
$url = offerte_url($o);
$k = $o['klant'];
$nu = time();
$actie = (string)($in['actie'] ?? '');

if ($actie === 'aanvaarden') {
    if ($status !== 'open') uit(409, ['ok' => false, 'fout' => $status === 'verlopen' ? 'Deze offerte is verlopen. Vraag gerust een nieuwe aan.' : 'Deze offerte kan niet meer aanvaard worden.']);
    $naam = tekst($in['naam'] ?? '', 120);
    if (mb_strlen($naam) < 2) uit(422, ['ok' => false, 'fout' => 'Vul je naam in.']);
    if (empty($in['akkoord'])) uit(422, ['ok' => false, 'fout' => 'Vink aan dat je akkoord gaat met de offerte en de algemene voorwaarden.']);
    $sig = (string)($in['handtekening'] ?? '');
    if (!preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $sig) || strlen($sig) > 300000) uit(422, ['ok' => false, 'fout' => 'Zet je handtekening in het vak.']);
    $png = base64_decode(substr($sig, 22), true);
    $info = $png ? @getimagesizefromstring($png) : false;
    if (!$info || $info[2] !== IMAGETYPE_PNG) uit(422, ['ok' => false, 'fout' => 'De handtekening kon niet gelezen worden. Probeer opnieuw.']);
    $vroeg = !empty($in['vroeg']);
    $o['status'] = 'aanvaard';
    $o['aanvaard'] = ['t' => $nu, 'naam' => $naam, 'vroeg' => $vroeg, 'handtekening' => $sig,
                      'ip_hash' => substr(hash('sha256', stats_geheim() . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 16),
                      'browser' => tekst($_SERVER['HTTP_USER_AGENT'] ?? '', 200)];
    $o['historiek'][] = ['t' => $nu, 'actie' => 'aanvaard'];
    if (!offerte_bewaar($o)) uit(500, ['ok' => false, 'fout' => 'Opslaan is mislukt. Probeer het opnieuw of bel 0468 43 00 53.']);
    stats_log('aanvaard', ['p' => '/offerte.php', 'nr' => $o['nummer'], 'b' => $o['totaal_excl'], 'g' => $k['gemeente'] ?? '']);
    $tot = date('d/m/Y', $nu + OFFERTE_HERROEPING_DAGEN * 86400);
    $bedrag = offerte_eur((float)$o['totaal_incl']);
    mail_txt($k['email'], "Bevestiging: je hebt offerte {$o['nummer']} aanvaard",
        "Beste {$k['naam']},\n\nBedankt! Je hebt offerte {$o['nummer']} van Super Schoon aanvaard op " . date('d/m/Y \o\m H:i', $nu) . ".\n\n"
        . "- Reiniging van " . number_format((float)$o['m2'], 1, ',', '.') . " m² (" . implode(', ', $o['types']) . ")\n"
        . "- Adres: {$k['adres']}\n- Totaal: $bedrag incl. btw\n\n"
        . "We nemen contact met je op om een datum te plannen. Je factuur ontvang je na de uitvoering.\n\n"
        . "Je offerte, met je handtekening en de algemene voorwaarden, blijft beschikbaar via:\n$url\n\n"
        . "Herroepingsrecht: als consument kan je deze overeenkomst tot en met $tot kosteloos en zonder opgave van reden herroepen, via de knop op de offertepagina, per e-mail aan info@ssprojects.be of met het modelformulier onderaan de offerte."
        . ($vroeg ? " Je hebt gevraagd om de reiniging binnen die termijn uit te voeren. Herroep je daarna nog, dan betaal je het deel dat al uitgevoerd is. Is de reiniging volledig uitgevoerd, dan kan je niet meer herroepen." : '') . "\n\n"
        . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\nwww.superschoon.be\n");
    mail_txt(ONTVANGER, "AANVAARD: offerte {$o['nummer']} – {$k['naam']} – $bedrag",
        "Offerte {$o['nummer']} is online aanvaard op " . date('d/m/Y H:i', $nu) . " door $naam.\n\n"
        . "Klant: {$k['naam']}\nE-mail: {$k['email']}\nTelefoon: {$k['tel']}\nAdres: {$k['adres']}\n"
        . "Oppervlakte: " . number_format((float)$o['m2'], 1, ',', '.') . " m² – " . implode(', ', $o['types']) . "\nTotaal: $bedrag incl. btw\n"
        . "Uitvoering binnen de herroepingstermijn gevraagd: " . ($vroeg ? 'JA' : 'nee (niet uitvoeren vóór ' . $tot . ')') . "\n\nOfferte: $url\n", $k['email']);
    uit(200, ['ok' => true]);
}

if ($actie === 'weigeren') {
    if ($status !== 'open') uit(409, ['ok' => false, 'fout' => 'Deze offerte kan niet meer gewijzigd worden.']);
    $reden = tekst($in['reden'] ?? '', 80); $toel = tekst($in['toelichting'] ?? '', 1500);
    $o['status'] = 'geweigerd';
    $o['geweigerd'] = ['t' => $nu, 'reden' => $reden, 'toelichting' => $toel];
    $o['historiek'][] = ['t' => $nu, 'actie' => 'geweigerd'];
    offerte_bewaar($o);
    stats_log('geweigerd', ['p' => '/offerte.php', 'nr' => $o['nummer'], 'reden' => $reden]);
    mail_txt(ONTVANGER, "Geweigerd: offerte {$o['nummer']} – {$k['naam']}",
        "Offerte {$o['nummer']} werd online geweigerd op " . date('d/m/Y H:i', $nu) . ".\n\nReden: " . ($reden ?: '-') . "\nToelichting:\n" . ($toel ?: '-') . "\n\n"
        . "Klant: {$k['naam']} – {$k['email']} – {$k['tel']}\nOfferte: $url\n", $k['email']);
    uit(200, ['ok' => true]);
}

if ($actie === 'vraag') {
    $vraag = tekst($in['vraag'] ?? '', 3000);
    if (mb_strlen($vraag) < 3) uit(422, ['ok' => false, 'fout' => 'Typ je vraag.']);
    $o['historiek'][] = ['t' => $nu, 'actie' => 'vraag', 'tekst' => $vraag];
    offerte_bewaar($o);
    stats_log('offertevraag', ['p' => '/offerte.php', 'nr' => $o['nummer']]);
    $ok = mail_txt(ONTVANGER, "Vraag over offerte {$o['nummer']} – {$k['naam']}",
        "{$k['naam']} stelt een vraag over offerte {$o['nummer']}:\n\n$vraag\n\nAntwoorden kan rechtstreeks op deze mail ({$k['email']}).\nTelefoon: {$k['tel']}\nOfferte: $url\n", $k['email']);
    uit($ok ? 200 : 500, ['ok' => $ok]);
}

if ($actie === 'herroepen') {
    if (!offerte_herroepbaar($o)) uit(409, ['ok' => false, 'fout' => 'De herroepingstermijn is verstreken. Neem contact op via info@ssprojects.be.']);
    $naam = tekst($in['naam'] ?? '', 120);
    if (mb_strlen($naam) < 2) uit(422, ['ok' => false, 'fout' => 'Vul je naam in.']);
    $o['status'] = 'herroepen';
    $o['herroepen'] = ['t' => $nu, 'naam' => $naam];
    $o['historiek'][] = ['t' => $nu, 'actie' => 'herroepen'];
    offerte_bewaar($o);
    stats_log('herroepen', ['p' => '/offerte.php', 'nr' => $o['nummer']]);
    mail_txt($k['email'], "Bevestiging van je herroeping – offerte {$o['nummer']}",
        "Beste {$k['naam']},\n\nWe bevestigen dat je de overeenkomst voor offerte {$o['nummer']} herroepen hebt op " . date('d/m/Y \o\m H:i', $nu) . ".\n"
        . "Er worden geen werken uitgevoerd. Werd er al iets uitgevoerd op jouw uitdrukkelijk verzoek, dan nemen we contact op over dat deel.\n\n"
        . "Met vriendelijke groeten,\nSuper Schoon by S&S Projects\n0468 43 00 53 – info@ssprojects.be\n");
    mail_txt(ONTVANGER, "HERROEPEN: offerte {$o['nummer']} – {$k['naam']}",
        "Offerte {$o['nummer']} werd herroepen op " . date('d/m/Y H:i', $nu) . " door $naam.\nPlanning annuleren!\n\nKlant: {$k['naam']} – {$k['email']} – {$k['tel']}\nOfferte: $url\n", $k['email']);
    uit(200, ['ok' => true]);
}

uit(400, ['ok' => false, 'fout' => 'Onbekende actie.']);
