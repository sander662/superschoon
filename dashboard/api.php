<?php
/** Super Schoon – gegevens voor het dashboard (alleen na aanmelden). */
declare(strict_types=1);
define('SUPERSCHOON', true);
require __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

function uit(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if (!dash_ingelogd()) uit(401, ['fout' => 'Niet aangemeld.']);
$_SESSION['tijd'] = time();
dash_intern_cookie();

/* ---------- acties (POST) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals(dash_csrf(), (string)($_SERVER['HTTP_X_CSRF'] ?? ''))) uit(403, ['fout' => 'Ongeldige sessie. Herlaad de pagina.']);
    $in = json_decode((string)file_get_contents('php://input', false, null, 0, 20000), true);
    if (!is_array($in)) uit(400, ['fout' => 'Ongeldige aanvraag.']);
    if (($in['actie'] ?? '') === 'psi') {
        $f = stats_dir() . '/pagespeed.json';
        $lijst = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
        $s = $in['scores'] ?? [];
        $rij = ['t' => time(), 'url' => stats_kort($in['url'] ?? '', 200), 'toestel' => ($in['toestel'] ?? '') === 'desktop' ? 'desktop' : 'gsm'];
        foreach (['performance', 'accessibility', 'best-practices', 'seo'] as $k) $rij[$k] = is_numeric($s[$k] ?? null) ? (int)$s[$k] : null;
        foreach (['lcp', 'cls', 'tbt'] as $k) if (isset($in[$k])) $rij[$k] = stats_kort($in[$k], 20);
        array_unshift($lijst, $rij);
        @file_put_contents($f, json_encode(array_slice($lijst, 0, 60), JSON_UNESCAPED_SLASHES), LOCK_EX);
        uit(200, ['ok' => true]);
    }
    if (($in['actie'] ?? '') === 'wachtwoord') {
        $oud = (string)($in['oud'] ?? ''); $nieuw = (string)($in['nieuw'] ?? '');
        if (!password_verify($oud, dash_hash())) uit(422, ['fout' => 'Het huidige wachtwoord klopt niet.']);
        if (mb_strlen($nieuw) < 12) uit(422, ['fout' => 'Kies een nieuw wachtwoord van minstens 12 tekens.']);
        @file_put_contents(stats_dir() . '/wachtwoord.hash', password_hash($nieuw, PASSWORD_DEFAULT), LOCK_EX);
        uit(200, ['ok' => true]);
    }
    uit(400, ['fout' => 'Onbekende actie.']);
}

/* ---------- periode ---------- */
$datum = fn($s) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$s) ? (string)$s : null;
$tot = $datum($_GET['tot'] ?? '') ?? date('Y-m-d');
$van = $datum($_GET['van'] ?? '') ?? date('Y-m-d', strtotime('-29 days'));
if ($van > $tot) [$van, $tot] = [$tot, $van];
$a = strtotime("$van 00:00:00"); $b = strtotime("$tot 23:59:59");
$dagen = (int)round(($b + 1 - $a) / 86400);
if ($dagen > 800) uit(422, ['fout' => 'Kies een kortere periode.']);
$pa = $a - $dagen * 86400; $pb = $a - 1;

/* ---------- gegevens inlezen ---------- */
$rijen = [];
$maand = date('Y-m', $pa); $laatste = date('Y-m', max($b, time()));
while ($maand <= $laatste) {
    $f = stats_dir() . "/ev-$maand.ndjson";
    if (is_file($f) && ($h = fopen($f, 'r'))) {
        while (($l = fgets($h)) !== false) { $r = json_decode($l, true); if (is_array($r) && isset($r['t'], $r['e'])) $rijen[] = $r; }
        fclose($h);
    }
    $maand = date('Y-m', strtotime("$maand-01 +1 month"));
}

// op welke dagen was een bezoeker (per maandcode) er? -> terugkerende bezoekers
$dagenPerBezoeker = [];
foreach ($rijen as $r) if ($r['e'] === 'pv') $dagenPerBezoeker[date('Y-m', $r['t']) . '|' . $r['m']][date('Y-m-d', $r['t'])] = true;

function telop(array &$lijst, string $k, int $n = 1): void { $lijst[$k] = ($lijst[$k] ?? 0) + $n; }
function top(array $lijst, int $n = 10): array {
    arsort($lijst);
    $uit = [];
    foreach (array_slice($lijst, 0, $n, true) as $k => $v) $uit[] = ['naam' => (string)$k, 'n' => $v];
    return $uit;
}

function periode(array $rijen, int $a, int $b, array $dagenPerBezoeker, bool $details): array {
    $pv = 0; $bezoekers = []; $bezoeken = []; $terug = [];
    $tel = 0; $mail = 0; $wa = 0; $offertes = []; $contact = 0; $kliks = []; $knoppen = [];
    $paginas = []; $paginaBezoekers = []; $calcStart = []; $kaart = []; $calcBezoek = []; $kcodes = []; $perDag = [];
    foreach ($rijen as $r) {
        $t = $r['t']; if ($t < $a || $t > $b) continue;
        $dag = date('Y-m-d', $t);
        switch ($r['e']) {
            case 'pv':
                $pv++;
                $sleutel = date('Y-m', $t) . '|' . $r['m'];
                $bezoekers[$sleutel] = true;
                if (count($dagenPerBezoeker[$sleutel] ?? []) > 1) {
                    $eerste = array_key_first($dagenPerBezoeker[$sleutel]);
                    if ($eerste < $dag || count($dagenPerBezoeker[$sleutel]) > 1) $terug[$sleutel] = true;
                }
                if (!isset($bezoeken[$r['v']]) || $t < $bezoeken[$r['v']]['t']) {
                    $bezoeken[$r['v']] = ['t' => $t, 'p' => $r['p'] ?? '/', 's' => $r['s'] ?? 'Direct', 'd' => $r['d'] ?? 'computer', 'r' => $r['r'] ?? ''];
                }
                $p = $r['p'] ?? '/';
                telop($paginas, $p);
                $paginaBezoekers[$p][$r['v']] = true;
                if ($p === '/prijsberekening.html') $calcBezoek[$r['v']] = true;
                $perDag[$dag]['pv'] = ($perDag[$dag]['pv'] ?? 0) + 1;
                $perDag[$dag]['v'][$r['v']] = true;
                break;
            case 'klik':
                $k = $r['k'] ?? 'intern';
                if ($k === 'tel') $tel++;
                if ($k === 'mail') $mail++;
                if ($k === 'whatsapp') $wa++;
                telop($kliks, $k);
                $label = $k === 'tel' ? 'Telefoon ' . preg_replace('/^tel:/', '', $r['x'] ?? '') : ($k === 'mail' ? 'E-mail ' . preg_replace('/^mailto:/', '', $r['x'] ?? '') : (($r['l'] ?? '') !== '' ? $r['l'] : ($r['x'] ?? '')));
                telop($knoppen, $k . "\t" . $label . "\t" . ($r['x'] ?? ''));
                break;
            case 'calc_start': $calcStart[$r['v']] = true; break;
            case 'kaart': $kaart[$r['v']] = true; break;
            case 'offerte': $offertes[] = $r; $perDag[$dag]['a'] = ($perDag[$dag]['a'] ?? 0) + 1; break;
            case 'contact': $contact++; $perDag[$dag]['a'] = ($perDag[$dag]['a'] ?? 0) + 1; break;
            case 'kcode': $kcodes[] = $r; break;
        }
    }
    $nb = count($bezoekers);
    $kpi = [
        'bezoekers' => $nb, 'bezoeken' => count($bezoeken), 'terugkerend' => count($terug),
        'pv' => $pv, 'offertes' => count($offertes), 'contact' => $contact,
        'conversie' => $nb ? round((count($offertes) + $contact) / $nb * 100, 1) : 0,
        'tel' => $tel, 'mail' => $mail, 'whatsapp' => $wa,
    ];
    if (!$details) return ['kpi' => $kpi];

    // reeks per dag
    $reeks = [];
    for ($d = $a; $d <= $b; $d += 86400) {
        $k = date('Y-m-d', $d);
        $reeks[] = ['dag' => $k, 'bezoekers' => count($perDag[$k]['v'] ?? []), 'pv' => $perDag[$k]['pv'] ?? 0, 'aanvragen' => $perDag[$k]['a'] ?? 0];
    }
    $bronnen = []; $toestellen = []; $instap = []; $verwijzers = [];
    foreach ($bezoeken as $bz) {
        telop($bronnen, $bz['s']); telop($toestellen, $bz['d']); telop($instap, $bz['p']);
        if ($bz['r'] !== '') telop($verwijzers, $bz['r']);
    }
    $top = [];
    arsort($paginas);
    foreach (array_slice($paginas, 0, 15, true) as $p => $n) $top[] = ['naam' => $p, 'n' => $n, 'bezoekers' => count($paginaBezoekers[$p])];
    $gemeentes = [];
    foreach ($paginas as $p => $n) if (str_starts_with($p, '/terrasreiniging-')) $gemeentes[$p] = count($paginaBezoekers[$p]);
    $kliklijst = [];
    arsort($knoppen);
    foreach (array_slice($knoppen, 0, 15, true) as $k => $n) { [$soort, $label, $doel] = explode("\t", $k) + ['', '', '']; $kliklijst[] = ['soort' => $soort, 'naam' => $label, 'doel' => $doel, 'n' => $n]; }

    // aanvragen
    $m2 = 0; $bedrag = 0; $perBron = []; $perGemeente = []; $codes = []; $nbJa = 0; $fotos = 0; $metKaart = 0; $recent = [];
    foreach ($offertes as $o) {
        $m2 += (float)($o['m2'] ?? 0); $bedrag += (float)($o['b'] ?? 0);
        telop($perBron, $bezoeken[$o['v']]['s'] ?? 'Onbekend');
        if (($o['g'] ?? '') !== '') telop($perGemeente, $o['g']);
        if (($o['code'] ?? '') !== '') telop($codes, $o['code']);
        if (!empty($o['nb'])) $nbJa++;
        if (!empty($o['f'])) $fotos++;
        if (!empty($o['kaart'])) $metKaart++;
    }
    foreach (array_slice(array_reverse($offertes), 0, 12) as $o) {
        $recent[] = ['t' => $o['t'], 'g' => $o['g'] ?? '', 'm2' => $o['m2'] ?? null, 'b' => $o['b'] ?? null, 'bron' => $bezoeken[$o['v']]['s'] ?? 'Onbekend',
                     'code' => $o['code'] ?? '', 'f' => $o['f'] ?? 0, 'ty' => $o['ty'] ?? []];
    }
    $no = count($offertes);
    $kc = ['pogingen' => count($kcodes), 'geldig' => count(array_filter($kcodes, fn($k) => !empty($k['ok'])))];
    $kcTop = []; foreach ($kcodes as $k) telop($kcTop, ($k['code'] ?? '') . (!empty($k['ok']) ? '' : ' (ongeldig)'));

    return [
        'kpi' => $kpi, 'reeks' => $reeks,
        'bronnen' => top($bronnen, 10), 'toestellen' => top($toestellen, 3), 'instap' => top($instap, 10), 'verwijzers' => top($verwijzers, 10),
        'paginas' => $top, 'gemeentes' => top($gemeentes, 12), 'kliks' => $kliklijst, 'kliksoorten' => top($kliks, 6),
        'trechter' => ['calculator' => count($calcBezoek), 'gestart' => count($calcStart), 'kaart' => count($kaart), 'verstuurd' => $no],
        'aanvragen' => [
            'aantal' => $no, 'gem_m2' => $no ? round($m2 / $no, 1) : null, 'gem_bedrag' => $no ? round($bedrag / $no, 2) : null,
            'totaal_bedrag' => round($bedrag, 2), 'per_bron' => top($perBron, 10), 'per_gemeente' => top($perGemeente, 10),
            'codes' => top($codes, 10), 'nieuwsbrief' => $nbJa, 'met_fotos' => $fotos, 'met_kaart' => $metKaart, 'recent' => $recent,
        ],
        'kortingscodes' => $kc + ['top' => top($kcTop, 10)],
    ];
}

$nu = periode($rijen, $a, $b, $dagenPerBezoeker, true);
$vorig = periode($rijen, $pa, $pb, $dagenPerBezoeker, false);
$live = [];
foreach ($rijen as $r) if ($r['e'] === 'pv' && $r['t'] > time() - 300) $live[$r['v']] = true;

$seo = is_file(__DIR__ . '/seo.json') ? json_decode((string)file_get_contents(__DIR__ . '/seo.json'), true) : null;
$psi = is_file(stats_dir() . '/pagespeed.json') ? json_decode((string)file_get_contents(stats_dir() . '/pagespeed.json'), true) : [];
$eerste = null;
foreach (glob(stats_dir() . '/ev-*.ndjson') ?: [] as $f) { if (preg_match('/ev-(\d{4}-\d{2})/', $f, $m) && ($eerste === null || $m[1] < $eerste)) $eerste = $m[1]; }

uit(200, [
    'van' => $van, 'tot' => $tot, 'dagen' => $dagen, 'live' => count($live), 'sinds' => $eerste,
    'nu' => $nu, 'vorig' => $vorig['kpi'], 'seo' => $seo, 'pagespeed' => $psi,
]);
