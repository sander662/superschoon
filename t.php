<?php
/** Super Schoon – ontvangt paginaweergaven en kliks (zonder cookies). Zie dashboard/lib.php. */
declare(strict_types=1);
define('SUPERSCHOON', true);
require __DIR__ . '/dashboard/lib.php';

header('Cache-Control: no-store');
http_response_code(204);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') exit;
$herkomst = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($herkomst !== '' && parse_url($herkomst, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '')) exit;

$in = json_decode((string)file_get_contents('php://input', false, null, 0, 4000), true);
if (!is_array($in)) exit;

$e = (string)($in['e'] ?? '');
$pad = (string)(parse_url((string)($in['p'] ?? '/'), PHP_URL_PATH) ?: '/');
$pad = preg_match('#^/[A-Za-z0-9/._-]{0,150}$#', $pad) ? $pad : '/';
if ($pad === '/index.html') $pad = '/';

switch ($e) {
    case 'pv':
        $ref = strtolower((string)(parse_url((string)($in['r'] ?? ''), PHP_URL_HOST) ?? ''));
        $ref = preg_replace('/^www\./', '', $ref) ?? '';
        if ($ref === preg_replace('/^www\./', '', strtolower($_SERVER['HTTP_HOST'] ?? ''))) $ref = '';
        $utm = stats_kort($in['u'] ?? '', 40);
        $rij = ['p' => $pad, 'r' => stats_kort($ref, 80), 's' => stats_bron($ref, $utm)];
        if ($utm !== '') $rij['u'] = $utm;
        if (!empty($in['c'])) $rij['c'] = stats_kort($in['c'], 40);   // campagne
        stats_log('pv', $rij);
        break;
    case 'klik':
        $soort = in_array($in['k'] ?? '', ['tel', 'mail', 'whatsapp', 'extern', 'knop', 'intern'], true) ? $in['k'] : 'intern';
        stats_log('klik', ['p' => $pad, 'k' => $soort, 'x' => stats_kort($in['x'] ?? '', 160), 'l' => stats_kort($in['l'] ?? '', 60)]);
        break;
    case 'calc_start':
    case 'kaart':
    case 'foto':
        stats_log($e, ['p' => $pad]);
        break;
}
