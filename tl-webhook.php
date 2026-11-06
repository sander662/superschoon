<?php
/** Super Schoon – ontvangt meldingen van Teamleader (betaling van een factuur geregistreerd). */
declare(strict_types=1);
date_default_timezone_set('Europe/Brussels');
define('SUPERSCHOON', true);
require __DIR__ . '/dashboard/tl_lib.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

// Teamleader ondertekent webhooks niet: we vertrouwen de melding niet blind, maar vragen de factuur
// zelf op bij Teamleader (tl_check_betaald). Een vervalste melding kan dus niets veranderen.
$sleutel = tl_cfg()['webhook_sleutel'] ?? '';
if ($sleutel === '' || !hash_equals($sleutel, (string)($_GET['k'] ?? ''))) { http_response_code(403); exit; }
$in = json_decode((string)file_get_contents('php://input', false, null, 0, 20000), true);
if (is_array($in) && ($in['type'] ?? '') === 'invoice.paymentRegistered' && !empty($in['subject']['id'])) {
    $o = tl_zoek_offerte_voor_factuur((string)$in['subject']['id']);
    if ($o && tl_check_betaald($o)) offerte_bewaar($o);
}
http_response_code(200);
echo 'ok';
