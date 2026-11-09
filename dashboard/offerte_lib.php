<?php
/**
 * Super Schoon – online offertes: opslag en gedeelde functies.
 * Offertes worden bewaard in dashboard/data/offertes/<token>.json (afgeschermd).
 * De klant opent zijn offerte met een geheime, willekeurige link: /offerte.php?id=<token>.
 */
declare(strict_types=1);
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }
require_once __DIR__ . '/lib.php';

const OFFERTE_GELDIG_MAANDEN = 1;      // AV art. 2.2: een offerte is één maand geldig
const OFFERTE_HERROEPING_DAGEN = 14;   // AV art. 12.1
const OFFERTE_UURTARIEF = 60.0;        // AV art. 6: uurtarief voor extra tijd, excl. btw
const OFFERTE_VOORSCHOT = 0.25; // AV art. 11.1: geen betaling bij aanvaarding; 25% voorschot zodra de datum vastligt, saldo (75%) na uitvoering

function offerte_dir(): string {
    $d = stats_dir() . '/offertes';
    if (!is_dir($d)) @mkdir($d, 0750, true);
    return $d;
}
function offerte_geldig_token(string $t): bool {
    return (bool)preg_match('/^[a-f0-9]{32}$/', $t);
}
function offerte_pad(string $token): string {
    return offerte_dir() . '/' . $token . '.json';
}
function offerte_laad(string $token): ?array {
    if (!offerte_geldig_token($token) || !is_file(offerte_pad($token))) return null;
    $o = json_decode((string)file_get_contents(offerte_pad($token)), true);
    return is_array($o) ? $o : null;
}
function offerte_bewaar(array $o): bool {
    return @file_put_contents(offerte_pad($o['token']), json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX) !== false;
}
/** Volgnummer per jaar: 2026-0001, 2026-0002, ... */
function offerte_volgnummer(): string {
    $f = offerte_dir() . '/teller.json';
    $h = fopen($f, 'c+');
    flock($h, LOCK_EX);
    $d = json_decode((string)stream_get_contents($h), true) ?: [];
    $jaar = date('Y');
    $n = (int)($d[$jaar] ?? 0) + 1;
    $d[$jaar] = $n;
    ftruncate($h, 0); rewind($h); fwrite($h, json_encode($d));
    flock($h, LOCK_UN); fclose($h);
    return sprintf('%s-%04d', $jaar, $n);
}
function offerte_status(array $o): string {
    if (in_array($o['status'], ['aanvaard', 'geweigerd', 'herroepen'], true)) return $o['status'];
    return time() > $o['geldig_tot'] ? 'verlopen' : 'open';
}
function offerte_herroepbaar(array $o): bool {
    return $o['status'] === 'aanvaard' && !empty($o['aanvaard']['t'])
        && time() <= $o['aanvaard']['t'] + OFFERTE_HERROEPING_DAGEN * 86400;
}
function offerte_url(array $o): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'www.superschoon.be';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https || $host === 'www.superschoon.be' ? 'https' : 'http') . '://' . $host . '/offerte.php?id=' . $o['token'];
}
/** Betalingsschema volgens AV art. 11.1. */
function offerte_schema(array $o): array {
    $t = (float)$o['totaal_incl'];
    $v = round($t * OFFERTE_VOORSCHOT, 2);
    return ['voorschot' => $v, 'saldo' => round($t - $v, 2)];
}
function offerte_eur(float $n): string {
    return '€ ' . number_format($n, 2, ',', '.');
}
/** Online offertes: uit | test (alleen e-mailadressen op @ssprojects.be) | aan. Standaard uit. */
function offerte_modus(): string {
    $f = stats_dir() . '/offerte-instellingen.json';
    $c = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    return in_array($c['modus'] ?? '', ['uit', 'test', 'aan'], true) ? $c['modus'] : 'uit';
}
function offerte_zet_modus(string $m): void {
    @file_put_contents(stats_dir() . '/offerte-instellingen.json', json_encode(['modus' => $m]), LOCK_EX);
}
function offerte_actief_voor(string $email): bool {
    $m = offerte_modus();
    return $m === 'aan' || ($m === 'test' && str_ends_with(strtolower($email), '@ssprojects.be'));
}

/** Lijst voor het dashboard (nieuwste eerst). */
function offerte_lijst(int $max = 100): array {
    $uit = [];
    foreach (glob(offerte_dir() . '/*.json') ?: [] as $f) {
        if (str_ends_with($f, 'teller.json')) continue;
        $o = json_decode((string)file_get_contents($f), true);
        if (!is_array($o) || empty($o['token'])) continue;
        $uit[] = ['nummer' => $o['nummer'], 't' => $o['aangemaakt'], 'naam' => $o['klant']['naam'], 'gemeente' => $o['klant']['gemeente'] ?? '', 'bron' => $o['bron'] ?? 'website',
                  'm2' => $o['m2'], 'incl' => $o['totaal_incl'], 'status' => offerte_status($o), 'token' => $o['token'],
                  'aanvaard' => $o['aanvaard']['t'] ?? null, 'vroeg' => $o['aanvaard']['vroeg'] ?? null,
                  'tl' => !empty($o['tl']['fout']) || !empty($o['tl']['fout_aanvaard']) ? 'fout' : (!empty($o['tl']['ok']) ? 'ok' : ''), 'betaald' => !empty($o['tl']['voorschot']['betaald'])];
    }
    usort($uit, fn($a, $b) => $b['t'] <=> $a['t']);
    return array_slice($uit, 0, $max);
}

/* ---------- gedeeld met de offertetool in het dashboard ---------- */

/** Prijs van de reiniging excl. btw (zelfde formule als de prijsberekening op de website). */
function offerte_prijs_reiniging(float $m2): float {
    if ($m2 < 1) return 0.0;
    if ($m2 <= 50) $p = 190 + 210 * ($m2 - 1) / 49;
    elseif ($m2 <= 100) $p = 400 + 7 * ($m2 - 50);
    else $p = 750 + 6 * ($m2 - 100);
    return (float)round($p);
}
/** Gemeenten en zones uit de prijsberekening: [naam => ['zone' => 1..3, 'hoofd' => ..., 'kost' => ...]]. */
function offerte_zones(): array {
    static $z = null;
    if ($z !== null) return $z;
    $z = [];
    $js = @file_get_contents(dirname(__DIR__) . '/assets/js/calculator.js') ?: '';
    $a = strpos($js, 'const ZONELIJST={'); $b = strpos($js, 'const LEZ');
    if ($a === false || $b === false) return $z;
    $txt = rtrim(trim(substr($js, $a + strlen('const ZONELIJST='), $b - $a - strlen('const ZONELIJST='))), ';');
    $txt = preg_replace('/(\d):\[/', '"$1":[', str_replace("'", '"', $txt));
    $lijst = json_decode($txt, true) ?: [];
    $kost = [1 => 0, 2 => 40, 3 => 65];
    foreach ($lijst as $zone => $groep) foreach ($groep as [$hoofd, $deel]) {
        $z[$hoofd] = ['zone' => (int)$zone, 'hoofd' => $hoofd, 'kost' => $kost[(int)$zone]];
        foreach ($deel as $d) $z[$d] = ['zone' => (int)$zone, 'hoofd' => $hoofd, 'kost' => $kost[(int)$zone]];
    }
    ksort($z, SORT_LOCALE_STRING);
    return $z;
}
/** Extra offertelijnen: [['omschrijving','toelichting','aantal','eenheid','prijs']] → met totaal per lijn. */
function offerte_extra(array $o): array {
    $uit = [];
    foreach ($o['extra'] ?? [] as $l) {
        $l['totaal'] = round((float)$l['aantal'] * (float)$l['prijs'], 2);
        $uit[] = $l;
    }
    return $uit;
}
/** Totalen opnieuw berekenen uit de onderdelen. */
function offerte_bereken(array &$o): void {
    $excl = (float)$o['reiniging'] + (float)($o['verplaatsing'] ?? 0) - (float)($o['korting']['bedrag'] ?? 0);
    foreach (offerte_extra($o) as $l) $excl += $l['totaal'];
    $excl = round($excl, 2);
    $incl = round($excl * 1.21, 2);
    $o['totaal_excl'] = $excl; $o['btw'] = round($incl - $excl, 2); $o['totaal_incl'] = $incl;
}
/** Korte omschrijving voor mails en Teamleader. */
function offerte_kort(array $o): string {
    $d = [];
    if ((float)$o['m2'] > 0) $d[] = 'reiniging van ' . number_format((float)$o['m2'], 1, ',', '.') . ' m²' . ($o['types'] ? ' (' . implode(', ', $o['types']) . ')' : '');
    foreach ($o['extra'] ?? [] as $l) $d[] = mb_strtolower(mb_substr($l['omschrijving'], 0, 1)) . mb_substr($l['omschrijving'], 1);
    return $d ? implode(', ', $d) : 'offerte';
}
