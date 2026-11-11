<?php
/**
 * Super Schoon – kortingscodes.
 * Dit bestand is niet rechtstreeks op te vragen (zie .htaccess); bezoekers zien de codes dus niet.
 *
 * Formaat van een code (in dashboard/data/kortingscodes.json):
 *   "VOORJAAR10": {"type": "procent", "waarde": 10, "geldig_tot": "2027-05-31"}
 * - type:       'procent' (percentage) of 'bedrag' (vast bedrag in euro, excl. btw)
 * - waarde:     het percentage of het bedrag
 * - geldig_tot: laatste geldige dag (JJJJ-MM-DD), of null voor onbeperkt
 * De korting geldt op het totaal excl. btw (reiniging + verplaatsing).
 * Codes zijn niet hoofdlettergevoelig.
 */
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }

/**
 * De codes zelf staan NIET in dit bestand (de GitHub-repo is openbaar), maar op de server in
 * dashboard/data/kortingscodes.json. Beheren via het dashboard: /dashboard/kortingscodes.php
 */
function kortingscodes_bestand(): string {
    return __DIR__ . '/dashboard/data/kortingscodes.json';
}
function kortingscodes(): array {
    $f = kortingscodes_bestand();
    $c = is_file($f) ? json_decode((string)file_get_contents($f), true) : [];
    return is_array($c) ? $c : [];
}

function zoekKorting(string $code): ?array {
    $code = strtoupper(trim($code));
    $alle = kortingscodes();
    if ($code === '' || !isset($alle[$code])) return null;
    $k = $alle[$code];
    if (!empty($k['geldig_tot']) && date('Y-m-d') > $k['geldig_tot']) return null;
    $type = ($k['type'] ?? '') === 'bedrag' ? 'bedrag' : 'procent';
    $waarde = (float)($k['waarde'] ?? 0);
    if ($waarde <= 0) return null;
    $label = $type === 'procent'
        ? rtrim(rtrim(number_format($waarde, 2, ',', ''), '0'), ',') . '% korting'
        : '€ ' . rtrim(rtrim(number_format($waarde, 2, ',', '.'), '0'), ',') . ' korting (excl. btw)';
    return ['code' => $code, 'type' => $type, 'waarde' => $waarde, 'label' => $label];
}

function kortingBedrag(?array $k, float $excl): float {
    if (!$k) return 0.0;
    $b = $k['type'] === 'procent' ? $excl * $k['waarde'] / 100 : $k['waarde'];
    return min($excl, round($b, 2));
}
