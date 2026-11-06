<?php
/**
 * Super Schoon – statistieken zonder cookies (gedeelde functies).
 *
 * Privacy:
 *  - Er wordt niets op het toestel van de bezoeker opgeslagen (geen cookies, geen localStorage).
 *  - Het IP-adres wordt nooit bewaard. Een bezoeker krijgt een korte code: een hash van
 *    IP-adres + browser + een geheime sleutel + de datum (per dag) of de maand (per maand).
 *    Zo kunnen we bezoekers tellen en zien of iemand in dezelfde maand terugkomt,
 *    zonder iemand te kunnen identificeren.
 *  - Bots en beheerders (ingelogd op het dashboard) worden niet meegeteld.
 */
declare(strict_types=1);
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }
date_default_timezone_set('Europe/Brussels');

const STATS_DIR = __DIR__ . '/data';
const STATS_BEWAAR_MAANDEN = 25;

function stats_dir(): string {
    if (!is_dir(STATS_DIR)) {
        @mkdir(STATS_DIR, 0750, true);
    }
    if (!is_file(STATS_DIR . '/.htaccess')) {
        @file_put_contents(STATS_DIR . '/.htaccess', "Require all denied\n");
    }
    return STATS_DIR;
}

function stats_geheim(): string {
    $f = stats_dir() . '/geheim.txt';
    if (!is_file($f)) {
        @file_put_contents($f, bin2hex(random_bytes(32)), LOCK_EX);
    }
    return trim((string)@file_get_contents($f));
}

function stats_ids(): array {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $g  = stats_geheim();
    return [
        substr(hash('sha256', $g . '|' . date('Y-m-d') . '|' . $ip . '|' . $ua), 0, 16),
        substr(hash('sha256', $g . '|' . date('Y-m') . '|' . $ip . '|' . $ua), 0, 16),
    ];
}

function stats_bot(): bool {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $ua === '' || (bool)preg_match('/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|chrome-lighthouse|preview|facebookexternalhit|embedly|curl|wget|python|java\/|httpclient|okhttp|monitor|uptime|scan/i', $ua);
}

function stats_intern(): bool {
    return ($_COOKIE['ss_intern'] ?? '') === '1';
}

function stats_toestel(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/iPad|Tablet|Nexus 7|Nexus 10|SM-T|Kindle|Silk/i', $ua) || (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua))) return 'tablet';
    if (preg_match('/Mobi|iPhone|Android/i', $ua)) return 'gsm';
    return 'computer';
}

function stats_bron(string $host, string $utm): string {
    if ($utm !== '') {
        if (preg_match('/flyer|folder|qr|bus|wagen|auto|kaartje|offline/i', $utm)) return 'Flyer / QR';
        if (preg_match('/cpc|ads|adwords|advert/i', $utm)) return 'Advertentie';
        if (preg_match('/mail|nieuwsbrief|newsletter/i', $utm)) return 'E-mail';
        if (preg_match('/facebook|instagram|^fb|^ig|meta|linkedin|tiktok|youtube/i', $utm)) return 'Social';
        if (preg_match('/ssprojects/i', $utm)) return 'Verwijzing';
        return 'Campagne';
    }
    if ($host === '') return 'Direct';
    if (preg_match('/chatgpt|openai|perplexity|claude\.ai|copilot|gemini\.google|you\.com|phind|mistral/i', $host)) return 'AI-assistent';
    if (preg_match('/(^|\.)(google|bing|duckduckgo|yahoo|ecosia|qwant|startpage|yandex|search\.brave|baidu)\./i', $host)) return 'Zoekmachine';
    if (preg_match('/facebook|instagram|linkedin|tiktok|pinterest|youtube|(^|\.)t\.co$|(^|\.)x\.com$|twitter|whatsapp|messenger|reddit/i', $host)) return 'Social';
    if (preg_match('/mail\.|outlook|gmail/i', $host)) return 'E-mail';
    return 'Verwijzing';
}

function stats_opruimen(): void {
    $merk = stats_dir() . '/opgeruimd.txt';
    if (is_file($merk) && trim((string)@file_get_contents($merk)) === date('Y-m-d')) return;
    @file_put_contents($merk, date('Y-m-d'));
    $grens = date('Y-m', strtotime('-' . STATS_BEWAAR_MAANDEN . ' months'));
    foreach (glob(stats_dir() . '/ev-*.ndjson') ?: [] as $f) {
        if (preg_match('/ev-(\d{4}-\d{2})\.ndjson$/', $f, $m) && $m[1] < $grens) @unlink($f);
    }
    foreach (glob(stats_dir() . '/login-*.json') ?: [] as $f) {
        if (filemtime($f) < time() - 86400) @unlink($f);
    }
}

/** Een gebeurtenis bewaren. $extra bevat nooit namen, e-mailadressen of telefoonnummers. */
function stats_log(string $e, array $extra = []): void {
    try {
        if (stats_bot() || stats_intern()) return;
        [$v, $m] = stats_ids();
        $rij = ['t' => time(), 'e' => $e, 'v' => $v, 'm' => $m, 'd' => stats_toestel()] + $extra;
        @file_put_contents(stats_dir() . '/ev-' . date('Y-m') . '.ndjson',
            json_encode($rij, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
        stats_opruimen();
    } catch (Throwable $t) {
        // statistieken mogen de website nooit breken
    }
}

function stats_kort($s, int $max): string {
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$s) ?? '';
    return mb_substr(trim($s), 0, $max);
}
