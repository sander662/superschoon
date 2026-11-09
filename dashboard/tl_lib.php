<?php
/**
 * Super Schoon – koppeling met Teamleader Focus (API v2, OAuth 2).
 * Instellingen en tokens staan in dashboard/data/teamleader.json (afgeschermd, niet in Git).
 * Fouten mogen de website nooit breken: alles wordt gelogd in dashboard/data/teamleader.log.
 */
declare(strict_types=1);
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }
require_once __DIR__ . '/offerte_lib.php';

const TL_AUTH  = 'https://focus.teamleader.eu/oauth2/authorize';
const TL_TOKEN = 'https://focus.teamleader.eu/oauth2/access_token';
const TL_API   = 'https://api.focus.teamleader.eu';

function tl_cfg(): array {
    $f = stats_dir() . '/teamleader.json';
    $c = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    return $c + ['modus' => 'uit', 'client_id' => '', 'client_secret' => '', 'api' => TL_API, 'token_url' => TL_TOKEN,
                 'department_id' => '', 'pipeline_id' => '', 'fase_nieuw' => '', 'fase_aanvaard' => '', 'fase_betaald' => '',
                 'tax_rate_id' => '', 'responsible_user_id' => '', 'source_id' => ''];
}
function tl_bewaar_cfg(array $c): void {
    @file_put_contents(stats_dir() . '/teamleader.json', json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function tl_log(string $m): void {
    @file_put_contents(stats_dir() . '/teamleader.log', date('Y-m-d H:i:s') . "  $m\n", FILE_APPEND | LOCK_EX);
}
function tl_gekoppeld(?array $c = null): bool {
    $c ??= tl_cfg();
    return !empty($c['refresh_token']) && $c['department_id'] && $c['pipeline_id'] && $c['fase_nieuw'] && $c['tax_rate_id'];
}
/**
 * Moet deze offerte naar Teamleader?  modus: uit | test | aan
 * In "test" alleen voor aanvragen van een e-mailadres op @ssprojects.be.
 */
function tl_voor(array $o): bool {
    $c = tl_cfg();
    if (!tl_gekoppeld($c)) return false;
    if ($c['modus'] === 'aan') return true;
    return $c['modus'] === 'test' && str_ends_with(strtolower($o['klant']['email']), '@ssprojects.be');
}

function tl_http(string $url, array $body, ?string $token = null, bool $form = false): array {
    $ch = curl_init($url);
    $hdr = ['Accept: application/json', 'Content-Type: ' . ($form ? 'application/x-www-form-urlencoded' : 'application/json')];
    if ($token) $hdr[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $hdr,
        CURLOPT_POSTFIELDS => $form ? http_build_query($body) : json_encode($body ?: new stdClass, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException("Geen verbinding met Teamleader: $err");
    $j = $raw === '' ? [] : json_decode((string)$raw, true);
    return [$code, is_array($j) ? $j : ['raw' => mb_substr((string)$raw, 0, 300)]];
}

/** OAuth: code of refresh token omzetten naar tokens. */
function tl_tokens(array $extra): void {
    $c = tl_cfg();
    [$code, $j] = tl_http($c['token_url'], ['client_id' => $c['client_id'], 'client_secret' => $c['client_secret']] + $extra, null, true);
    if ($code !== 200 || empty($j['access_token'])) throw new RuntimeException('Aanmelden bij Teamleader mislukt (' . $code . '): ' . json_encode($j));
    $c['access_token'] = $j['access_token'];
    $c['refresh_token'] = $j['refresh_token'] ?? $c['refresh_token'] ?? '';
    $c['verloopt'] = time() + (int)($j['expires_in'] ?? 3600) - 60;
    tl_bewaar_cfg($c);
}
function tl_token(): string {
    $c = tl_cfg();
    if (empty($c['refresh_token'])) throw new RuntimeException('Teamleader is niet gekoppeld.');
    if (empty($c['access_token']) || ($c['verloopt'] ?? 0) < time()) {
        tl_tokens(['grant_type' => 'refresh_token', 'refresh_token' => $c['refresh_token']]);
        $c = tl_cfg();
    }
    return $c['access_token'];
}
function tl_api(string $endpoint, array $body = []): array {
    $c = tl_cfg();
    [$code, $j] = tl_http(rtrim($c['api'], '/') . '/' . $endpoint, $body, tl_token());
    if ($code === 401) {
        $c['verloopt'] = 0; tl_bewaar_cfg($c);
        [$code, $j] = tl_http(rtrim($c['api'], '/') . '/' . $endpoint, $body, tl_token());
    }
    if ($code >= 300) throw new RuntimeException("$endpoint gaf $code: " . mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 400));
    return $j;
}

function tl_naam(string $volledig): array {
    $d = preg_split('/\s+/', trim($volledig), 2);
    return count($d) === 2 ? [$d[0], $d[1]] : ['', $d[0]];
}
function tl_adres(string $adres): array {
    // "Kerkstraat 5, 1982 Elewijt (Zemst)"
    $straat = $adres; $pc = ''; $stad = '';
    if (preg_match('/^(.*?),\s*(\d{4})\s+(.+)$/u', $adres, $m)) { $straat = $m[1]; $pc = $m[2]; $stad = trim(preg_replace('/\s*\(.*\)$/', '', $m[3])); }
    return ['line_1' => $straat ?: '-', 'postal_code' => $pc ?: '-', 'city' => $stad ?: '-', 'country' => 'BE'];
}
function tl_lijnen(array $o, array $c): array {
    $lijnen = [[
        'quantity' => 1,
        'description' => 'Ecologische reiniging buitenverharding – ' . number_format((float)$o['m2'], 1, ',', '.') . ' m²',
        'extended_description' => 'Verharding: ' . implode(', ', $o['types']) . "\nReiniging met een gepatenteerd borstelsysteem en water, zonder chemicaliën, zeep, stoom of hogedruk.",
        'unit_price' => ['amount' => round((float)$o['reiniging'], 2), 'tax' => 'excluding'],
        'tax_rate_id' => $c['tax_rate_id'],
    ]];
    if ($o['verplaatsing'] !== null) $lijnen[] = ['quantity' => 1, 'description' => 'Verplaatsing' . (!empty($o['klant']['gemeente']) ? ' naar ' . $o['klant']['gemeente'] : ''),
        'unit_price' => ['amount' => round((float)$o['verplaatsing'], 2), 'tax' => 'excluding'], 'tax_rate_id' => $c['tax_rate_id']];
    if (!empty($o['korting'])) $lijnen[] = ['quantity' => 1, 'description' => 'Korting ' . $o['korting']['code'] . ': ' . $o['korting']['label'],
        'unit_price' => ['amount' => -round((float)$o['korting']['bedrag'], 2), 'tax' => 'excluding'], 'tax_rate_id' => $c['tax_rate_id']];
    return $lijnen;
}

/** Nieuwe offerte: contact zoeken of aanmaken, deal en offerte aanmaken. */
function tl_sync_nieuw(array &$o): void {
    if (!tl_voor($o)) return;
    $c = tl_cfg(); $k = $o['klant'];
    $test = $c['modus'] === 'test';
    try {
        $o['tl'] = $o['tl'] ?? [];
        $res = tl_api('contacts.list', ['filter' => ['email' => ['type' => 'primary', 'email' => $k['email']]], 'page' => ['size' => 1]]);
        if (!empty($res['data'][0]['id'])) {
            $o['tl']['contact_id'] = $res['data'][0]['id']; $o['tl']['contact_nieuw'] = false;
        } else {
            [$voor, $achter] = tl_naam($k['naam']);
            $nieuw = ['first_name' => $voor, 'last_name' => $achter, 'emails' => [['type' => 'primary', 'email' => $k['email']]],
                      'addresses' => [['type' => 'primary', 'address' => tl_adres($k['adres'])]], 'language' => 'nl',
                      'tags' => ['Super Schoon'], 'marketing_mails_consent' => !empty($o['nieuwsbrief'])];
            if ($k['tel']) $nieuw['telephones'] = [['type' => preg_match('/^(\+32\s?|0)4/', preg_replace('/\s/', '', $k['tel'])) ? 'mobile' : 'phone', 'number' => $k['tel']]];
            $o['tl']['contact_id'] = tl_api('contacts.add', $nieuw)['data']['id']; $o['tl']['contact_nieuw'] = true;
        }
        $deal = ['lead' => ['customer' => ['type' => 'contact', 'id' => $o['tl']['contact_id']]],
                 'title' => ($test ? '[TEST] ' : '') . 'Super Schoon – reiniging ' . number_format((float)$o['m2'], 0, ',', '.') . ' m² – offerte ' . $o['nummer'],
                 'summary' => 'Online offerte: ' . offerte_url($o) . ($o['opmerking'] ? "\nOpmerking klant: " . $o['opmerking'] : ''),
                 'department_id' => $c['department_id'], 'phase_id' => $c['fase_nieuw'],
                 'estimated_value' => ['amount' => round((float)$o['totaal_excl'], 2), 'currency' => 'EUR'],
                 'estimated_closing_date' => date('Y-m-d', $o['geldig_tot'])];
        if ($c['responsible_user_id']) $deal['responsible_user_id'] = $c['responsible_user_id'];
        if ($c['source_id']) $deal['source_id'] = $c['source_id'];
        $o['tl']['deal_id'] = tl_api('deals.create', $deal)['data']['id'];
        $o['tl']['quotation_id'] = tl_api('quotations.create', [
            'deal_id' => $o['tl']['deal_id'], 'currency' => ['code' => 'EUR', 'exchange_rate' => 1],
            'name' => 'Offerte ' . $o['nummer'] . ' – Super Schoon',
            'grouped_lines' => [['line_items' => tl_lijnen($o, $c)]],
            'text' => 'Online offerte van Super Schoon. Bekijken en aanvaarden: ' . offerte_url($o),
        ])['data']['id'];
        $o['tl']['ok'] = true;
        tl_log("offerte {$o['nummer']}: contact " . ($o['tl']['contact_nieuw'] ? 'aangemaakt' : 'gevonden') . ", deal {$o['tl']['deal_id']}, offerte {$o['tl']['quotation_id']}");
    } catch (Throwable $t) {
        $o['tl']['fout'] = $t->getMessage();
        tl_log("FOUT offerte {$o['nummer']} (nieuw): " . $t->getMessage());
    }
}

/** Aanvaarding: offerte aanvaard en deal verplaatsen. Geen factuur: het voorschot van 25% stuurt Sander zelf zodra de datum vastligt. */
function tl_sync_aanvaard(array &$o): void {
    if (empty($o['tl']['deal_id']) || !tl_voor($o)) return;
    $c = tl_cfg();
    try {
        try { tl_api('quotations.accept', ['id' => $o['tl']['quotation_id']]); } catch (Throwable $t) { tl_log("waarschuwing {$o['nummer']}: quotations.accept: " . $t->getMessage()); }
        if ($c['fase_aanvaard']) tl_api('deals.move', ['id' => $o['tl']['deal_id'], 'phase_id' => $c['fase_aanvaard']]);
        $o['tl']['aanvaard_ok'] = true;
        tl_log("offerte {$o['nummer']}: aanvaard in Teamleader, deal verplaatst");
    } catch (Throwable $t) {
        $o['tl']['fout_aanvaard'] = $t->getMessage();
        tl_log("FOUT offerte {$o['nummer']} (aanvaard): " . $t->getMessage());
    }
}

/** Is het voorschot betaald? Zo ja: deal naar de volgende fase. Geeft true als de status veranderde. */
function tl_check_betaald(array &$o): bool {
    $v = $o['tl']['voorschot'] ?? null;
    if (!$v || !empty($v['betaald']) || empty($v['geboekt'])) return false;
    try {
        $info = tl_api('invoices.info', ['id' => $v['id']])['data'];
        if (empty($info['paid'])) return false;
        $o['tl']['voorschot']['betaald'] = true;
        $o['tl']['voorschot']['betaald_op'] = $info['paid_at'] ?? date('c');
        $o['historiek'][] = ['t' => time(), 'actie' => 'voorschot betaald'];
        $c = tl_cfg();
        if ($c['fase_betaald']) tl_api('deals.move', ['id' => $o['tl']['deal_id'], 'phase_id' => $c['fase_betaald']]);
        tl_log("offerte {$o['nummer']}: voorschot betaald");
        stats_log('voorschot_betaald', ['p' => '/offerte.php', 'nr' => $o['nummer'], 'b' => $v['bedrag']]);
        return true;
    } catch (Throwable $t) {
        tl_log("FOUT offerte {$o['nummer']} (betaling controleren): " . $t->getMessage());
        return false;
    }
}
function tl_zoek_offerte_voor_factuur(string $factuurId): ?array {
    foreach (glob(offerte_dir() . '/*.json') ?: [] as $f) {
        if (str_ends_with($f, 'teller.json')) continue;
        $o = json_decode((string)file_get_contents($f), true);
        if (($o['tl']['voorschot']['id'] ?? '') === $factuurId) return $o;
    }
    return null;
}
