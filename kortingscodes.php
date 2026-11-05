<?php
/**
 * Super Schoon – kortingscodes.
 * Dit bestand is niet rechtstreeks op te vragen (zie .htaccess); bezoekers zien de codes dus niet.
 *
 * Een code toevoegen: zet een regel in de lijst hieronder, bijvoorbeeld
 *   'VOORJAAR10' => ['type' => 'procent', 'waarde' => 10, 'geldig_tot' => '2027-05-31'],
 *   'WELKOM25'   => ['type' => 'bedrag',  'waarde' => 25, 'geldig_tot' => null],
 * - type:       'procent' (percentage) of 'bedrag' (vast bedrag in euro, excl. btw)
 * - waarde:     het percentage of het bedrag
 * - geldig_tot: laatste geldige dag (JJJJ-MM-DD), of null voor onbeperkt
 * De korting geldt op het totaal excl. btw (reiniging + verplaatsing).
 * Codes zijn niet hoofdlettergevoelig.
 */
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }

const KORTINGSCODES = [
    // nog geen actieve codes
];

function zoekKorting(string $code): ?array {
    $code = strtoupper(trim($code));
    if ($code === '' || !isset(KORTINGSCODES[$code])) return null;
    $k = KORTINGSCODES[$code];
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
