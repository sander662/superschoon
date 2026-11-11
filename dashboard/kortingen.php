<?php
/** Super Schoon – kortingscodes beheren (opgeslagen op de server, niet in de openbare GitHub-repo). */
declare(strict_types=1);
define('SUPERSCHOON', true);
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/kortingscodes.php';

if (!dash_ingelogd()) { header('Location: ./'); exit; }
$_SESSION['tijd'] = time();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$v = fn($f) => $f . '?v=' . substr(md5((string)@filemtime(__DIR__ . '/' . $f)), 0, 8);
$melding = ''; $fout = '';
stats_dir(); // zorgt dat data/ (met afscherming) bestaat

function kc_bewaar(array $c): bool {
    ksort($c);
    return @file_put_contents(kortingscodes_bestand(), json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals(dash_csrf(), (string)($_POST['csrf'] ?? ''))) $fout = 'Sessie verlopen. Herlaad de pagina.';
    else {
        $alle = kortingscodes();
        if (($_POST['actie'] ?? '') === 'toevoegen') {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['code'] ?? '')));
            $type = ($_POST['type'] ?? '') === 'bedrag' ? 'bedrag' : 'procent';
            $waarde = round((float)str_replace(',', '.', (string)($_POST['waarde'] ?? 0)), 2);
            $tot = (string)($_POST['geldig_tot'] ?? '');
            if (strlen($code) < 4 || strlen($code) > 30) $fout = 'Een code heeft 4 tot 30 tekens (letters, cijfers, - of _).';
            elseif ($waarde <= 0 || ($type === 'procent' && $waarde > 100)) $fout = 'Kies een geldige waarde (procent: 1 tot 100).';
            elseif ($tot !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tot)) $fout = 'De datum klopt niet.';
            else {
                $alle[$code] = ['type' => $type, 'waarde' => $waarde, 'geldig_tot' => $tot ?: null];
                $melding = kc_bewaar($alle) ? "Code $code bewaard." : 'Opslaan is mislukt.';
            }
        } elseif (($_POST['actie'] ?? '') === 'verwijderen') {
            $code = (string)($_POST['code'] ?? '');
            if (isset($alle[$code])) { unset($alle[$code]); $melding = kc_bewaar($alle) ? "Code $code verwijderd." : 'Opslaan is mislukt.'; }
        }
    }
}
$alle = kortingscodes();
$csrf = $h(dash_csrf());
?><!doctype html>
<html lang="nl-BE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Kortingscodes | Dashboard Super Schoon</title><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="<?= $v('dashboard.css') ?>"></head>
<body>
<header class="kop"><div class="kop-in">
  <a class="merk" href="./"><img src="/assets/img/logo-superschoon-wit.svg" alt="Super Schoon" width="180" height="31"></a>
  <span class="kop-titel">Kortingscodes</span>
  <nav class="kop-links" style="margin-left:auto"><a href="./">Terug naar dashboard</a><a href="./?uit=1">Afmelden</a></nav>
</div></header>
<main class="wrap om">
  <?php if ($melding): ?><p class="paneel" role="status"><span class="status goed">✓</span> <?= $h($melding) ?></p><?php endif; ?>
  <?php if ($fout): ?><p class="paneel" role="alert"><span class="status slecht">✕</span> <?= $h($fout) ?></p><?php endif; ?>
  <section class="paneel">
    <h2>Actieve en verlopen codes</h2>
    <p class="zacht">Klanten vullen de code in bij de prijsberekening. De korting geldt op het totaal excl. btw (reiniging + verplaatsing). Codes staan alleen op de server, niet in de openbare code van de website.</p>
    <?php if (!$alle): ?><p class="leeg">Nog geen kortingscodes.</p><?php else: ?>
    <div class="tabel-wrap"><table class="om-tot" style="width:100%">
      <tr><th>Code</th><th>Korting</th><th>Geldig tot</th><th></th></tr>
      <?php foreach ($alle as $code => $k): $verlopen = !empty($k['geldig_tot']) && date('Y-m-d') > $k['geldig_tot']; ?>
      <tr><td><b><?= $h($code) ?></b><?= $verlopen ? ' <span class="status let">verlopen</span>' : '' ?></td>
        <td style="text-align:left"><?= $k['type'] === 'procent' ? $h($k['waarde']) . '%' : '€ ' . $h(number_format((float)$k['waarde'], 2, ',', '.')) . ' excl. btw' ?></td>
        <td style="text-align:left"><?= $k['geldig_tot'] ? $h(date('d/m/Y', strtotime($k['geldig_tot']))) : 'onbeperkt' ?></td>
        <td><form method="post" onsubmit="return confirm('Code <?= $h($code) ?> verwijderen?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="verwijderen"><input type="hidden" name="code" value="<?= $h($code) ?>"><button class="link">Verwijderen</button></form></td></tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </section>
  <section class="paneel">
    <h2>Code toevoegen of wijzigen</h2>
    <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="toevoegen">
      <div class="om-grid">
        <label>Code<input name="code" required minlength="4" maxlength="30" pattern="[A-Za-z0-9_-]+" placeholder="bv. VOORJAAR10" style="text-transform:uppercase"></label>
        <label>Soort<select name="type"><option value="procent">Percentage</option><option value="bedrag">Vast bedrag (€, excl. btw)</option></select></label>
        <label>Waarde<input name="waarde" type="number" step="0.01" min="0.01" required></label>
        <label>Geldig tot (leeg = onbeperkt)<input name="geldig_tot" type="date"></label>
      </div>
      <p style="margin-top:14px"><button class="knop">Bewaren</button></p>
    </form>
  </section>
</main>
</body></html>
