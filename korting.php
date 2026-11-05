<?php
/** Super Schoon – controleert een kortingscode voor de prijsberekening. */
declare(strict_types=1);
date_default_timezone_set('Europe/Brussels');
define('SUPERSCHOON', true);
require __DIR__ . '/kortingscodes.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }

// beperk het aantal pogingen per IP-adres (alleen een hash wordt tijdelijk bewaard)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'onbekend';
$f = sys_get_temp_dir() . '/superschoon_k_' . hash('sha256', $ip . date('YmdH')) . '.cnt';
$n = is_file($f) ? (int)file_get_contents($f) : 0;
if ($n >= 20) { http_response_code(429); echo json_encode(['ok' => false, 'fout' => 'Te veel pogingen. Probeer het later opnieuw.']); exit; }
@file_put_contents($f, (string)($n + 1));

$in = json_decode((string)file_get_contents('php://input', false, null, 0, 2000), true);
$k = zoekKorting(is_array($in) ? (string)($in['code'] ?? '') : '');
echo json_encode($k ? ['ok' => true, 'type' => $k['type'], 'waarde' => $k['waarde'], 'label' => $k['label']]
                    : ['ok' => false, 'fout' => 'Deze kortingscode is niet geldig of verlopen.'], JSON_UNESCAPED_UNICODE);
