<?php
/** Super Schoon – aanmelden voor het interne dashboard. */
declare(strict_types=1);
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');

const SESSIE_DUUR = 12 * 3600;

function dash_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}
function dash_hash(): string {
    $f = stats_dir() . '/wachtwoord.hash';
    return is_file($f) ? trim((string)file_get_contents($f)) : DASH_WACHTWOORD_HASH;
}

session_name('ssdash');
session_set_cookie_params(['lifetime' => 0, 'path' => '/dashboard/', 'secure' => dash_https(), 'httponly' => true, 'samesite' => 'Strict']);
session_start();

function dash_ingelogd(): bool {
    return !empty($_SESSION['ok']) && ($_SESSION['tijd'] ?? 0) > time() - SESSIE_DUUR;
}
/** Beheerders niet meetellen in de statistieken: functionele cookie, alleen op hun eigen browser. */
function dash_intern_cookie(): void {
    setcookie('ss_intern', '1', ['expires' => time() + 400 * 86400, 'path' => '/', 'secure' => dash_https(), 'httponly' => true, 'samesite' => 'Lax']);
}
function dash_csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function dash_pogingbestand(): string {
    return stats_dir() . '/login-' . substr(hash('sha256', stats_geheim() . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 16) . '.json';
}
function dash_mag_proberen(): bool {
    $f = dash_pogingbestand();
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    if (!is_array($d) || time() - ($d['t'] ?? 0) > 900) return true;
    return ($d['n'] ?? 0) < 5;
}
function dash_poging(bool $gelukt): void {
    $f = dash_pogingbestand();
    if ($gelukt) { @unlink($f); return; }
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    if (!is_array($d) || time() - ($d['t'] ?? 0) > 900) $d = ['n' => 0, 't' => time()];
    $d['n']++;
    @file_put_contents($f, json_encode($d), LOCK_EX);
}
function dash_aanmelden(string $wachtwoord): bool {
    if (!dash_mag_proberen()) return false;
    $ok = password_verify($wachtwoord, dash_hash());
    dash_poging($ok);
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['ok'] = true;
        $_SESSION['tijd'] = time();
        dash_intern_cookie();
    }
    return $ok;
}
