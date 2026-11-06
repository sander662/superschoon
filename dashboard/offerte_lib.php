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
const OFFERTE_VOORSCHOTTEN = [0.10, 0.25]; // AV art. 11.1: 10% bij aanvaarding, 25% bij inplanning, saldo na uitvoering

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
    $v1 = round($t * OFFERTE_VOORSCHOTTEN[0], 2); $v2 = round($t * OFFERTE_VOORSCHOTTEN[1], 2);
    return ['voorschot1' => $v1, 'voorschot2' => $v2, 'saldo' => round($t - $v1 - $v2, 2)];
}
function offerte_eur(float $n): string {
    return '€ ' . number_format($n, 2, ',', '.');
}
/** Lijst voor het dashboard (nieuwste eerst). */
function offerte_lijst(int $max = 100): array {
    $uit = [];
    foreach (glob(offerte_dir() . '/*.json') ?: [] as $f) {
        if (str_ends_with($f, 'teller.json')) continue;
        $o = json_decode((string)file_get_contents($f), true);
        if (!is_array($o) || empty($o['token'])) continue;
        $uit[] = ['nummer' => $o['nummer'], 't' => $o['aangemaakt'], 'naam' => $o['klant']['naam'], 'gemeente' => $o['klant']['gemeente'] ?? '',
                  'm2' => $o['m2'], 'incl' => $o['totaal_incl'], 'status' => offerte_status($o), 'token' => $o['token'],
                  'aanvaard' => $o['aanvaard']['t'] ?? null, 'vroeg' => $o['aanvaard']['vroeg'] ?? null,
                  'tl' => !empty($o['tl']['fout']) || !empty($o['tl']['fout_aanvaard']) ? 'fout' : (!empty($o['tl']['ok']) ? 'ok' : ''), 'betaald' => !empty($o['tl']['voorschot']['betaald'])];
    }
    usort($uit, fn($a, $b) => $b['t'] <=> $a['t']);
    return array_slice($uit, 0, $max);
}
